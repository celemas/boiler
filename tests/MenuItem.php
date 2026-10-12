<?php

declare(strict_types=1);

namespace Celema\Boiler\Tests;

use ArrayIterator;
use IteratorAggregate;
use Override;
use Stringable;

/**
 * A traversable object with methods, like a menu item that iterates its
 * children and prints as its title.
 *
 * @implements IteratorAggregate<int, MenuItem>
 */
final class MenuItem implements IteratorAggregate, Stringable
{
	/** @param list<MenuItem> $children */
	public function __construct(
		private readonly string $title,
		private readonly array $children = [],
	) {}

	#[Override]
	public function __toString(): string
	{
		return $this->title;
	}

	public function title(): string
	{
		return $this->title;
	}

	/** @return ArrayIterator<int, MenuItem> */
	#[Override]
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->children);
	}
}
