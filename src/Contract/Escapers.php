<?php

declare(strict_types=1);

namespace Celema\Boiler\Contract;

/** @api */
interface Escapers
{
	public string $default { get; }

	public function get(string $name): Escaper;
}
