<?php

declare(strict_types=1);

namespace Celema\Boiler;

use Celema\Boiler\Exception\LookupException;

/** @api */
final class Template
{
	public readonly Engine $engine;
	private readonly Methods $methods;

	public function __construct(
		public readonly string $path,
		?Engine $engine = null,
	) {
		if ($engine === null) {
			$dir = dirname($path);

			if ($dir === '' || $path === '') {
				throw new LookupException('No directory given or empty path');
			}

			$engine = new Engine(new Resolver($dir), new Environment(), true);

			if (!is_file($path)) {
				throw new LookupException('Template not found: ' . $path);
			}
		}

		$this->engine = $engine;
		// Layered over the engine's, so engine methods stay available, including
		// ones registered later, unless this template registers the same name.
		$this->methods = new Methods($engine->methods());
	}

	/** @param list<class-string> $trusted */
	public function render(array $context = [], array $trusted = []): string
	{
		return $this->renderWith($context, $trusted, $this->engine->autoescape);
	}

	/** @param list<class-string> $trusted */
	public function renderEscaped(array $context = [], array $trusted = []): string
	{
		return $this->renderWith($context, $trusted, true);
	}

	/** @param list<class-string> $trusted */
	public function renderUnescaped(array $context = [], array $trusted = []): string
	{
		return $this->renderWith($context, $trusted, false);
	}

	/** @param non-empty-string $name */
	public function method(string $name, callable $callable, bool $safe = false): static
	{
		$this->methods->add($name, $callable, $safe);

		return $this;
	}

	/** @param list<class-string> $trusted */
	private function renderWith(array $context, array $trusted, bool $autoescape): string
	{
		$rendering = new Rendering($this->path, $this->engine, $this->methods, new Sections(), $trusted, $autoescape);

		return $rendering->render($context);
	}
}
