<?php

declare(strict_types=1);

namespace Celema\Boiler\Filter;

use Celema\Boiler\Contract;
use Override;

/** @api */
final class Trim implements Contract\Filter, Contract\PreservesSafety
{
	#[Override]
	public function apply(string $value, mixed ...$args): string
	{
		if (!array_key_exists(0, $args) || $args[0] === null) {
			return trim($value);
		}

		return trim($value, (string) $args[0]);
	}

	#[Override]
	public function safe(): bool
	{
		return false;
	}
}
