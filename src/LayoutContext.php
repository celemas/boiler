<?php

declare(strict_types=1);

namespace Celema\Boiler;

/** @api */
final class LayoutContext extends Context
{
	/** @param list<class-string> $trusted */
	public function __construct(
		private readonly Layout $layout,
		array $context,
		array $trusted,
		bool $autoescape,
	) {
		parent::__construct($layout, $context, $trusted, $autoescape);
	}

	public function body(): string
	{
		return $this->layout->body();
	}
}
