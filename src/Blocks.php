<?php

declare(strict_types=1);

namespace Celema\Boiler;

use Celema\Boiler\Exception\LogicException;
use Closure;

/**
 * The capture blocks that a template's current render has opened and not
 * yet closed, innermost last.
 *
 * Every template render has its own stack, so a template can only close the
 * blocks it opened itself. Blocks may nest: a section can contain another
 * section, or an insert whose template opens and closes its own blocks.
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
		$this->open[] = new Block($kind, $name, $location, ob_get_level(), $close);
	}

	/**
	 * Closes the innermost block. With a name, it must be that block's name.
	 */
	public function close(?string $name = null): void
	{
		$block = end($this->open);

		if ($block === false) {
			throw new LogicException('No open section to close');
		}

		if ($name !== null && $name !== $block->name) {
			throw new LogicException("`end('{$name}')` does not match the open {$block->kind} `{$block->name}`");
		}

		// Another buffer is active, for example the one of an `each()` loop
		// body, so the captured output would not be the block's own.
		if ($block->level !== ob_get_level()) {
			throw new LogicException(
				ucfirst($block->kind)
					. " `{$block->name}` cannot be closed here: it was opened outside the current"
					. ' output buffer or `each()` loop body',
			);
		}

		array_pop($this->open);
		($block->close)((string) ob_get_clean());
	}

	/** The number of open blocks, to check later that the blocks opened since are closed. */
	public function depth(): int
	{
		return count($this->open);
	}

	public function assertClosed(int $depth = 0): void
	{
		// The outermost block opened since the given depth.
		$block = $this->open[$depth] ?? null;

		if ($block === null) {
			return;
		}

		throw new LogicException(
			"Unclosed {$block->kind} `{$block->name}` at {$block->location}",
			location: $block->location,
		);
	}
}
