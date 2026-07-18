<?php

declare(strict_types=1);

namespace Celema\Boiler;

use Celema\Boiler\Exception\LookupException;
use Celema\Boiler\Exception\UnexpectedValueException;

/**
 * @api
 * @psalm-type DirsInput = non-empty-string|list<non-empty-string>|array<non-empty-string, non-empty-string>
 */
final class Engine
{
	private readonly Contract\Environment $environment;
	private readonly Contract\Resolver $resolver;
	private Methods $methods;
	private ?Contract\Wrapper $wrapper = null;

	/** @param list<class-string> $trusted */
	public function __construct(
		Contract\Resolver $resolver,
		Contract\Environment $environment,
		public readonly bool $autoescape,
		private readonly array $defaults = [],
		private readonly array $trusted = [],
	) {
		$this->resolver = $resolver;
		$this->environment = $environment;
		$this->methods = new Methods();
	}

	/**
	 * @psalm-param DirsInput $dirs
	 * @param list<class-string> $trusted
	 */
	public static function create(
		array|string $dirs,
		array $defaults = [],
		array $trusted = [],
	): self {
		return new self(new Resolver($dirs), new Environment(), true, $defaults, $trusted);
	}

	/**
	 * @psalm-param DirsInput $dirs
	 * @param list<class-string> $trusted
	 */
	public static function unescaped(
		array|string $dirs,
		array $defaults = [],
		array $trusted = [],
	): self {
		return new self(new Resolver($dirs), new Environment(), false, $defaults, $trusted);
	}

	/** @param non-empty-string $name */
	public function method(string $name, callable $callable, bool $safe = false): static
	{
		$this->methods->add($name, $callable, $safe);

		return $this;
	}

	public function wrapper(): Contract\Wrapper
	{
		return $this->wrapper ??= $this->environment->wrapper();
	}

	public function escape(string $name, Contract\Escaper $with): static
	{
		$this->environment->registerEscaper($name, $with);

		return $this;
	}

	public function filter(string $name, Contract\Filter $with): static
	{
		$this->environment->registerFilter($name, $with);

		return $this;
	}

	/** @param non-empty-string $path */
	public function template(string $path): Template
	{
		$file = $this->resolve($path);
		$template = new Template($file, engine: $this);
		$template->setMethods($this->methods);

		return $template;
	}

	/** @param non-empty-string $path */
	public function render(
		string $path,
		array $context = [],
	): string {
		return $this->renderTemplate($path, $context, $this->autoescape);
	}

	/** @param non-empty-string $path */
	public function renderEscaped(
		string $path,
		array $context = [],
	): string {
		return $this->renderTemplate($path, $context, true);
	}

	/** @param non-empty-string $path */
	public function renderUnescaped(
		string $path,
		array $context = [],
	): string {
		return $this->renderTemplate($path, $context, false);
	}

	/** @param non-empty-string $path */
	private function renderTemplate(
		string $path,
		array $context,
		bool $autoescape,
	): string {
		$template = $this->template($path);
		$context = $this->defaults === []
			? $context
			: array_merge($this->defaults, $context);

		return $autoescape
			? $template->renderEscaped($context, $this->trusted)
			: $template->renderUnescaped($context, $this->trusted);
	}

	/**
	 * @param non-empty-string $path
	 *
	 * @return non-empty-string
	 */
	public function resolve(string $path): string
	{
		return $this->resolver->resolve($path);
	}

	/** @param non-empty-string $path */
	public function exists(string $path): bool
	{
		try {
			$this->resolve($path);

			return true;
		} catch (LookupException|UnexpectedValueException) {
			return false;
		}
	}
}
