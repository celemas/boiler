<?php

declare(strict_types=1);

namespace Celema\Boiler;

use Throwable;

/** @api */
final readonly class Location
{
	/** Covers the template helpers, whose call from the template is at most the fourth frame. */
	private const int NEAREST_FRAMES = 5;

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
		// A full backtrace costs more with every nested include, so the nearest
		// frames are searched first; 0 lifts the limit.
		foreach ([self::NEAREST_FRAMES, 0] as $limit) {
			foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $limit) as $frame) {
				if (($frame['file'] ?? null) !== $path) {
					continue;
				}

				return new self($path, self::normalizeLine($frame['line'] ?? null));
			}
		}

		return new self($path);
	}

	public function __toString(): string
	{
		return $this->line === null
			? $this->path
			: "{$this->path}:{$this->line}";
	}

	private static function normalizeLine(?int $line): ?int
	{
		return $line !== null && $line > 0 ? $line : null;
	}
}
