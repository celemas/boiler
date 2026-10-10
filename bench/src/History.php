<?php

declare(strict_types=1);

namespace Celema\Boiler\Bench;

use Closure;
use RuntimeException;

/** The saved runs of a directory: one JSON file each, named so that they sort by time. */
final readonly class History
{
	public function __construct(
		private string $dir,
	) {}

	/**
	 * @param array<string, mixed> $run
	 *
	 * @return string the file the run was saved as
	 */
	public function save(array $run, string $time, string $revision): string
	{
		is_dir($this->dir) || mkdir($this->dir, 0o755, true);
		$file = "{$this->dir}/{$time}-{$revision}.json";
		$json = json_encode($run, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

		if (file_put_contents($file, $json . "\n") === false) {
			throw new RuntimeException("Could not write {$file}");
		}

		return $file;
	}

	/** @return array<string, mixed> */
	public function load(string $file): array
	{
		$run = json_decode((string) file_get_contents($file), true);

		if (!is_array($run) || !is_array($run['lifecycles'] ?? null)) {
			throw new RuntimeException("{$file} is not a saved run");
		}

		/** @var array<string, mixed> $run */
		return $run;
	}

	/** Finds a saved run by its path, its file name, or a part of the name such as the commit. */
	public function find(string $reference): ?string
	{
		foreach ([$reference, $this->dir . '/' . basename($reference)] as $file) {
			if (is_file($file)) {
				return $file;
			}
		}

		return array_find(
			$this->newestFirst(),
			static fn(string $file): bool => str_contains(basename($file), $reference),
		);
	}

	/**
	 * The newest saved run that the callback accepts.
	 *
	 * @param Closure(array<string, mixed>): bool $accept
	 */
	public function newest(Closure $accept): ?string
	{
		return array_find($this->newestFirst(), fn(string $file): bool => $accept($this->load($file)));
	}

	/** @return list<string> */
	private function newestFirst(): array
	{
		$files = glob($this->dir . '/*.json') ?: [];
		rsort($files);

		return $files;
	}
}
