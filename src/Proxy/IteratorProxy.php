<?php

declare(strict_types=1);

namespace Celema\Boiler\Proxy;

use Celema\Boiler\Contract\Wrapper;
use Iterator;
use IteratorIterator;
use Override;
use Traversable;

/**
 * @api
 *
 * @template-covariant TKey
 * @template-covariant TValue
 *
 * @template TIterator as \Traversable<TKey, TValue>
 *
 * @template-extends IteratorIterator<TKey, TValue, TIterator>
 * @implements Proxy<Iterator<TKey, TValue>|null>
 */
final class IteratorProxy extends IteratorIterator implements Proxy
{
	/** @param TIterator $iterator */
	public function __construct(
		Traversable $iterator,
		private readonly Wrapper $wrapper,
	) {
		parent::__construct($iterator);
	}

	#[Override]
	public function current(): mixed
	{
		$value = parent::current();

		/** @psalm-suppress MixedReturnStatement see above */
		return $this->wrapper->wrap($value);
	}

	#[Override]
	public function unwrap(): ?Iterator
	{
		return $this->getInnerIterator();
	}

	#[Override]
	public function is(mixed $other): bool
	{
		return $this->getInnerIterator() === ($other instanceof Proxy ? $other->unwrap() : $other);
	}

	public function toArray(): ArrayProxy
	{
		$inner = $this->getInnerIterator();

		return new ArrayProxy($inner ? iterator_to_array($inner) : [], $this->wrapper);
	}
}
