<?php

declare(strict_types=1);

namespace Celema\Boiler\Proxy;

use Celema\Boiler\Contract\Wrapper;
use Generator;
use IteratorAggregate;
use Override;
use Traversable;

/**
 * @api
 *
 * @template-implements IteratorAggregate<mixed, mixed>
 * @implements Proxy<Traversable<mixed, mixed>>
 */
final class IteratorProxy implements IteratorAggregate, Proxy
{
	/** @param Traversable<mixed, mixed> $value */
	public function __construct(
		private readonly Traversable $value,
		private readonly Wrapper $wrapper,
	) {}

	/**
	 * Traverses the wrapped value anew on every loop, like a native foreach:
	 * an IteratorAggregate hands out a fresh iterator each time, while an
	 * Iterator or a generator keeps its single cursor.
	 *
	 * @return Generator<mixed, mixed>
	 */
	#[Override]
	public function getIterator(): Generator
	{
		/**
		 * @var mixed $key
		 * @var mixed $item
		 */
		foreach ($this->value as $key => $item) {
			yield $key => $this->wrapper->wrap($item);
		}
	}

	/** @return Traversable<mixed, mixed> */
	#[Override]
	public function unwrap(): Traversable
	{
		return $this->value;
	}

	#[Override]
	public function is(mixed $other): bool
	{
		return $this->value === ($other instanceof Proxy ? $other->unwrap() : $other);
	}

	/** @param ArrayProxy|array<array-key, mixed> $haystack */
	#[Override]
	public function in(ArrayProxy|array $haystack): bool
	{
		if ($haystack instanceof ArrayProxy) {
			$haystack = $haystack->unwrap();
		}

		/** @var mixed $item */
		foreach ($haystack as $item) {
			if ($this->is($item)) {
				return true;
			}
		}

		return false;
	}

	public function toArray(): ArrayProxy
	{
		return new ArrayProxy(iterator_to_array($this->value), $this->wrapper);
	}
}
