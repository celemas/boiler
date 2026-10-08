<?php

declare(strict_types=1);

namespace Celema\Boiler;

use Celema\Boiler\Exception\LookupException;

/** @api */
final class Template
{
	/** the resolved file path */
	public readonly string $path;
	public readonly Engine $engine;
	private readonly Methods $methods;

	public function __construct(string $path, ?Engine $engine = null)
	{
		if ($engine === null) {
			if ($path === '') {
				throw new LookupException('No directory given or empty path');
			}

			$engine = new Engine(new Resolver(dirname($path)), new Environment(), true);
		}

		$file = realpath($path);

		// realpath() also resolves directories, which include() cannot load.
		if ($file === false || !is_file($file)) {
			throw new LookupException('Template not found: ' . $path);
		}

		// PHP reports an included file under its resolved path, so error
		// locations only find the template's lines under that path.
		$this->path = $file;
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
		// Applied here rather than in Engine::render(), so that a template from
		// Engine::template() renders like Engine::render().
		$defaults = $this->engine->defaults;
		$context = $defaults === [] ? $context : array_merge($defaults, $context);
		$trusted = [...$this->engine->trusted, ...$trusted];
		$rendering = new Rendering($this->path, $this->engine, $this->methods, new Sections(), $trusted, $autoescape);

		return $rendering->render($context);
	}
}
