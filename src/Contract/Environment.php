<?php

declare(strict_types=1);

namespace Celema\Boiler\Contract;

/** @api */
interface Environment
{
	public function wrapper(): Wrapper;

	/** @psalm-assert non-empty-string $name */
	public function registerFilter(string $name, Filter $filter): void;

	/** @psalm-assert non-empty-string $name */
	public function registerEscaper(string $name, Escaper $escaper): void;
}
