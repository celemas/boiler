<?php

declare(strict_types=1);

namespace Celema\Boiler\Contract;

/** @api */
interface Filters
{
	public function get(string $name): Filter;
}
