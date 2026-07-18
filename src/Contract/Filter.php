<?php

declare(strict_types=1);

namespace Celema\Boiler\Contract;

/** @api */
interface Filter
{
	public function apply(string $value, mixed ...$args): string;

	public function safe(): bool;
}
