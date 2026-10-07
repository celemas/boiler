<?php

declare(strict_types=1);

namespace Celema\Boiler;

use Closure;

/** @internal */
final readonly class Block
{
	/**
	 * @param string $kind what the block is, for error messages, e.g. `section`
	 * @param Closure(string): void $close receives the captured output
	 */
	public function __construct(
		public string $kind,
		public string $name,
		public Location $location,
		public Closure $close,
	) {}
}
