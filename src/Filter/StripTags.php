<?php

declare(strict_types=1);

namespace Celema\Boiler\Filter;

use Celema\Boiler\Contract;
use Override;

/** @api */
final class StripTags implements Contract\Filter, Contract\PreservesSafety
{
	#[Override]
	public function apply(string $value, mixed ...$args): string
	{
		/** @var array<array-key, string>|null|string $allowed */
		$allowed = $args[0] ?? null;

		return strip_tags($value, $allowed);
	}

	#[Override]
	public function safe(): bool
	{
		return false;
	}
}
