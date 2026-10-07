<?php

declare(strict_types=1);

namespace Celema\Boiler;

use Celema\Boiler\Exception\LogicException;
use Closure;

/**
 * The capture blocks that a template's current render has opened and not
 * yet closed, innermost last: sections and components.
 *
 * Every template render has its own stack, so a template can only close the
 * blocks it opened itself. Blocks may nest: a section can contain a component,
 * another section, or an insert whose template opens and closes its own blocks.
 *
 * @internal
 */
final class Blocks
{
	/** @var list<Block> */
	private array $open = [];

	/** @param Closure(string): void $close receives the captured output */
	public function open(string $kind, string $name, Location $location, Closure $close): void
	{
		ob_start();
		$this->open[] = new Block($kind, $name, $location, $close);
	}

	/**
	 * Closes the innermost block. With a name, it must be that block's name.
	 */
	public function close(?string $name = null): void
	{
		$block = end($this->open);

		if ($block === false) {
			throw new LogicException('No open section or component to close');
		}

		if ($name !== null && $name !== $block->name) {
			throw new LogicException("`end('{$name}')` does not match the open {$block->kind} `{$block->name}`");
		}

		array_pop($this->open);
		($block->close)((string) ob_get_clean());
	}

	public function assertClosed(): void
	{
		$block = $this->open[0] ?? null;

		if ($block === null) {
			return;
		}

		throw new LogicException(
			"Unclosed {$block->kind} `{$block->name}` at {$block->location}",
			location: $block->location,
		);
	}
}
