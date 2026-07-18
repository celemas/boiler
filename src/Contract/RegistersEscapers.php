<?php

declare(strict_types=1);

namespace Celema\Boiler\Contract;

/** @api */
interface RegistersEscapers extends Escapers
{
	/** @psalm-assert non-empty-string $name */
	public function register(string $name, Escaper $escaper): void;
}
