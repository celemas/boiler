<?php

declare(strict_types=1);

namespace Celema\Boiler;

use Override;

/** @api */
final class Template extends BaseTemplate
{
	/** @param non-empty-string $name */
	public function method(string $name, callable $callable, bool $safe = false): static
	{
		$this->methods()->add($name, $callable, $safe);

		return $this;
	}

	/** @param list<class-string> $trusted */
	#[Override]
	protected function context(array $context, array $trusted, bool $autoescape): Context
	{
		return new TemplateContext($this, $context, $trusted, $autoescape);
	}
}
