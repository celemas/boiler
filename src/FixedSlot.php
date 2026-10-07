<?php

declare(strict_types=1);

namespace Celema\Boiler;

use Override;

/**
 * Markup that is rendered before the template that prints it, such as the
 * page a layout wraps.
 *
 * @internal
 */
final readonly class FixedSlot implements Slot
{
	public function __construct(
		private string $content,
	) {}

	/** Whitespace alone counts as no content. */
	#[Override]
	public function filled(): bool
	{
		return trim($this->content) !== '';
	}

	/**
	 * The data is ignored, so that a partial written for `each()` also works
	 * with fixed content.
	 */
	#[Override]
	public function render(array $data): string
	{
		return $this->filled() ? $this->content : '';
	}
}
