<?php

declare(strict_types=1);

namespace Celema\Boiler;

use Celema\Boiler\Exception\LookupException;
use Celema\Boiler\Exception\UnexpectedValueException;
use Override;

/**
 * @psalm-type DirsInput = non-empty-string|list<non-empty-string>|array<non-empty-string, non-empty-string>
 * @psalm-type Dirs = non-empty-list<non-empty-string>|non-empty-array<non-empty-string, non-empty-string>
 */
final class Resolver implements Contract\Resolver
{
	/** @psalm-var Dirs */
	private readonly array $dirs;
	/** @var array<string, non-empty-string> */
	private array $pathCache = [];

	/** @psalm-param DirsInput $dirs */
	public function __construct(
		array|string $dirs,
	) {
		$this->dirs = $this->prepareDirs($dirs);
	}

	/** @return non-empty-string */
	#[Override]
	public function resolve(string $path): string
	{
		if (isset($this->pathCache[$path])) {
			return $this->pathCache[$path];
		}

		if (!preg_match('/^[\w\.\/:_-]+$/u', $path)) {
			throw new UnexpectedValueException('The template path is invalid or empty');
		}

		[$namespace, $file] = $this->segments($path);
		$candidate = $this->path($namespace, $file);

		if (!$candidate->isValid()) {
			throw new LookupException($candidate->error());
		}

		return $this->pathCache[$path] = $candidate->path();
	}

	/** @return list{null|non-empty-string, non-empty-string} */
	private function segments(string $path): array
	{
		if (!str_contains($path, ':')) {
			$path = trim($path);
			assert($path !== '', 'Template path must not be empty after trimming');

			return [null, $path];
		}

		$segments = array_map(static fn($seg) => trim($seg), explode(':', $path));

		if (count($segments) === 2) {
			[$namespace, $file] = $segments;

			if ($namespace !== '' && $file !== '') {
				return [$namespace, $file];
			}

			throw new LookupException(
				"Invalid template format: '{$path}'. " . "Use 'namespace:template/path or template/path'.",
			);
		}

		throw new LookupException(
			"Invalid template format: '{$path}'. " . "Use 'namespace:template/path or template/path'.",
		);
	}

	/** @param non-empty-string $file */
	private function path(?string $namespace, string $file): Path
	{
		if ($namespace !== null) {
			if (array_key_exists($namespace, $this->dirs)) {
				return new Path($this->dirs[$namespace], $file);
			}

			throw new LookupException("Template namespace `{$namespace}` does not exist");
		}

		foreach ($this->dirs as $dir) {
			$candidate = new Path($dir, $file);

			if ($candidate->isValid()) {
				return $candidate;
			}
		}

		return $candidate;
	}

	/**
	 * @psalm-param DirsInput $dirs
	 *
	 * @psalm-return Dirs
	 */
	private function prepareDirs(array|string $dirs): array
	{
		$preparePath = static function (string $dir): string {
			$realpath = realpath($dir);

			if ($realpath === false) {
				throw new LookupException(
					'Template directory does not exist ' . $dir,
				);
			}

			assert($realpath !== '', 'Resolved template directory path must not be empty');

			return $realpath;
		};

		if (is_string($dirs)) {
			return [$preparePath($dirs)];
		}

		if ($dirs === []) {
			throw new LookupException('At least one template directory must be configured');
		}

		return array_map(
			static function ($dir) use ($preparePath) {
				return $preparePath($dir);
			},
			$dirs,
		);
	}
}
