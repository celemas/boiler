<?php

declare(strict_types=1);

namespace Celema\Boiler;

use Celema\Boiler\Exception\LookupException;

/** @internal */
final class Path
{
	private bool $isValid = false;
	private string $path = '';
	private string $error = '';

	/**
	 * @param non-empty-string $dir
	 * @param non-empty-string $file
	 */
	public function __construct(
		private string $dir,
		string $file,
	) {
		if (strlen(trim($dir)) === 0) {
			$this->error = 'Template directory must not be an empty string';

			return;
		}

		$dir = realpath($this->dir);

		if ($dir === false) {
			$this->error = "Template directory not found: '{$this->dir}'";

			return;
		}

		assert($dir !== '', 'Resolved template directory path must not be empty');

		$this->dir = $dir;
		$this->validateFile($dir, $file);
	}

	/** @return non-empty-string */
	public function path(): string
	{
		if (!$this->isValid || $this->path === '') {
			throw new LookupException("Error while accessing path of invalid template: `{$this->path}`");
		}

		return $this->path;
	}

	public function error(): string
	{
		return $this->error;
	}

	public function isValid(): bool
	{
		return $this->isValid;
	}

	private function validateFile(string $dir, string $file): void
	{
		$fullPath = $dir . DIRECTORY_SEPARATOR . $file;

		if (str_ends_with($fullPath, '.php')) {
			$this->validatePath($fullPath);
		} else {
			$this->validatePath("{$fullPath}.php");

			if (!$this->isValid) {
				$this->validatePath($fullPath);
			}
		}

		if ($this->isValid && !$this->isWithinRoot($this->path)) {
			$this->error = "Template resides outside of root directory ({$this->dir}): {$this->path}";
			$this->isValid = false;
		}
	}

	/** @param non-empty-string $path */
	private function validatePath(string $path): void
	{
		$realpath = realpath($path);

		if ($realpath === false || strlen($realpath) === 0) {
			$this->error = "Template not found: {$path}";

			return;
		}

		assert($realpath !== '', 'Resolved template file path must not be empty');

		$this->isValid = true;
		$this->path = $realpath;
		$this->error = '';
	}

	private function isWithinRoot(string $path): bool
	{
		$root = rtrim($this->dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

		if (DIRECTORY_SEPARATOR === '\\') {
			// Windows-only branch
			// @codeCoverageIgnoreStart

			return strncasecmp($path, $root, strlen($root)) === 0;

			// @codeCoverageIgnoreEnd
		}

		return str_starts_with($path, $root);
	}
}
