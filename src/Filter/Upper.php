<?php

declare(strict_types=1);

namespace Celema\Boiler\Filter;

use Celema\Boiler\Contract;
use Override;

/** @api */
final class Upper implements Contract\Filter, Contract\PreservesSafety
{
	#[Override]
	public function apply(string $value, mixed ...$args): string
	{
		return mb_strtoupper($value);
	}

	#[Override]
	public function safe(): bool
	{
		return false;
	}
}
