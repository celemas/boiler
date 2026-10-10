<?php

declare(strict_types=1);

namespace Celema\Boiler\Bench;

use Override;
use Stringable;

/** Markup the application trusts, such as rich text from an editor. */
final readonly class Html implements Stringable
{
	public function __construct(
		private string $html,
	) {}

	#[Override]
	public function __toString(): string
	{
		return $this->html;
	}
}
