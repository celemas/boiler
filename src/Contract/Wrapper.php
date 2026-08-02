<?php

declare(strict_types=1);

namespace Celema\Boiler\Contract;

/** @api */
interface Wrapper
{
	/**
	 * A wrapper that leaves instances of the given classes unwrapped, at
	 * any depth of the value it wraps.
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
