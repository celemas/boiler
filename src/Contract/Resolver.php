<?php

declare(strict_types=1);

namespace Celema\Boiler\Contract;

/** @api */
interface Resolver
{
	/** @return non-empty-string */
	public function resolve(string $path): string;
}
