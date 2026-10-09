<?php

declare(strict_types=1);

namespace Celema\Boiler\Tests;

use ArrayIterator;
use IteratorAggregate;
use Override;

/**
 * A traversable object with methods, like a menu item that iterates its
 * children.
 *
 * @implements IteratorAggregate<int, MenuItem>
 */
final class MenuItem implements IteratorAggregate
{
	/** @param list<MenuItem> $children */
	public function __construct(
		private readonly string $title,
		private readonly array $children = [],
	) {}

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
