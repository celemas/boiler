<?php

declare(strict_types=1);

namespace Celema\Boiler\Contract;

/** @api */
interface Wrapper
{
	/**
	 * A wrapper that treats the string conversion of instances of the given
	 * classes as safe HTML and prints it unescaped. It wraps the instances
	 * and what their properties, methods, and items return like other values.
	 *
	 * @param list<class-string> $trusted
	 */
	public function withTrusted(array $trusted): static;

	public function wrap(mixed $value): mixed;

	public function unwrap(mixed $value): mixed;

	public function escape(
		string $value,
		?string $escaper = null,
	): string;

	public function filter(string $name): Filter;
}
