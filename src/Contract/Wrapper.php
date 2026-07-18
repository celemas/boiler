<?php

declare(strict_types=1);

namespace Celema\Boiler\Contract;

/** @api */
interface Wrapper
{
	public function wrap(mixed $value): mixed;

	public function unwrap(mixed $value): mixed;

	public function escape(
		string $value,
		?string $escaper = null,
	): string;

	public function filter(string $name): Filter;
}
