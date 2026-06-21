<?php

declare(strict_types=1);

namespace Celemas\Boiler;

use Closure;
use Stringable;

/** @internal */
final class Slot
{
	public function __construct(
		private readonly Closure $slot,
	) {}

	/** @param array<array-key, mixed> $data */
	public function render(array $data): string
	{
		$level = ob_get_level();

		try {
			ob_start();

			/** @psalm-suppress MixedAssignment slot may echo, return markup, or both */
			$returned = ($this->slot)($data);
			$captured = (string) ob_get_clean();

			return is_string($returned) || $returned instanceof Stringable
				? $captured . (string) $returned
				: $captured;
		} finally {
			while (ob_get_level() > $level) {
				ob_end_clean();
			}
		}
	}
}
