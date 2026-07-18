<?php

declare(strict_types=1);

namespace Celema\Boiler;

use Throwable;

/** @api */
final readonly class Location
{
	public function __construct(
		public string $path,
		public ?int $line = null,
	) {}

	public static function fromThrowable(string $path, Throwable $throwable): self
	{
		if ($throwable->getFile() === $path) {
			return new self($path, self::normalizeLine($throwable->getLine()));
		}

		foreach ($throwable->getTrace() as $frame) {
			if (($frame['file'] ?? null) !== $path) {
				continue;
			}

			return new self($path, self::normalizeLine($frame['line'] ?? null));
		}

		return new self($path);
	}

	public static function fromBacktrace(string $path): self
	{
		foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $frame) {
			if (($frame['file'] ?? null) !== $path) {
				continue;
			}

			return new self($path, self::normalizeLine($frame['line'] ?? null));
		}

		return new self($path);
	}

	public function __toString(): string
	{
		return $this->line === null
			? $this->path
			: "{$this->path}:{$this->line}";
	}

	private static function normalizeLine(mixed $line): ?int
	{
		return is_int($line) && $line > 0 ? $line : null;
	}
}
