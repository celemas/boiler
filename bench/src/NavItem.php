<?php

declare(strict_types=1);

namespace Celema\Boiler\Bench;

use ArrayIterator;
use IteratorAggregate;
use Override;

/**
 * A menu entry that is looped over for its children, like the menu items of
 * a CMS.
 *
 * @implements IteratorAggregate<int, NavItem>
 */
final readonly class NavItem implements IteratorAggregate
{
	/** @param list<NavItem> $children */
	public function __construct(
		private string $title,
		private string $url,
		private bool $active = false,
		private array $children = [],
	) {}

	public function title(): string
	{
		return $this->title;
	}

	public function url(): string
	{
		return $this->url;
	}

	public function active(): bool
	{
		return $this->active;
	}

	public function hasChildren(): bool
	{
		return $this->children !== [];
	}

	/** @return ArrayIterator<int, NavItem> */
	#[Override]
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->children);
	}
}
