<?php

declare(strict_types=1);

namespace Celema\Boiler\Bench;

use Closure;

/**
 * One engine setup in the comparison.
 *
 * @template TEngine of object
 */
final readonly class Candidate
{
	/**
	 * @param Closure(): TEngine $create
	 * @param Closure(TEngine, string, array<string, mixed>): string $render
	 */
	public function __construct(
		public string $id,
		public string $name,
		public bool $escapes,
		private Closure $create,
		private Closure $render,
	) {}

	/** @return TEngine */
	public function engine(): object
	{
		return ($this->create)();
	}

	/**
	 * @param TEngine $engine
	 * @param array<string, mixed> $context
	 */
	public function render(object $engine, string $page, array $context): string
	{
		return ($this->render)($engine, $page, $context);
	}
}
