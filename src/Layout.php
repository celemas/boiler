<?php

declare(strict_types=1);

namespace Celema\Boiler;

use Override;

/** @internal */
final class Layout extends BaseTemplate
{
	/**
	 * @param non-empty-string $path
	 */
	public function __construct(
		string $path,
		private readonly string $body,
		Sections $sections,
		Engine $engine,
	) {
		parent::__construct($path, $sections, $engine);
	}

	/**
	 * Used in the layout template to get the content of the wrapped template.
	 */
	public function body(): string
	{
		return $this->body;
	}

	#[Override]
	protected function context(array $context, array $trusted, bool $autoescape): Context
	{
		return new LayoutContext($this, $context, $trusted, $autoescape);
	}
}
