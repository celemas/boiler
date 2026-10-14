<?php

declare(strict_types=1);

namespace Celema\Boiler\Contract;

use Celema\Boiler\Proxy\ArrayProxy;
use Celema\Boiler\Proxy\IteratorProxy;
use Celema\Boiler\Proxy\ObjectProxy;
use Celema\Boiler\Proxy\Proxy;
use Celema\Boiler\Proxy\StringProxy;
use Traversable;

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

	/**
	 * @template T
	 *
	 * @param T $value
	 *
	 * @return (T is string ? StringProxy : (T is array ? ArrayProxy : (T is Proxy ? T : (T is Traversable ? IteratorProxy : (T is object ? ObjectProxy : T)))))
	 */
	public function wrap(mixed $value): mixed;

	/**
	 * @template T
	 *
	 * @param T $value
	 *
	 * @return (T is StringProxy ? string : (T is ArrayProxy ? array<array-key, mixed> : (T is IteratorProxy ? Traversable<mixed, mixed> : (T is ObjectProxy ? object : (T is array ? array<key-of<T>, mixed> : T)))))
	 */
	public function unwrap(mixed $value): mixed;

	public function escape(
		string $value,
		?string $escaper = null,
	): string;

	public function filter(string $name): Filter;
}
