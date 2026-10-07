<?php

declare(strict_types=1);

namespace Celema\Boiler;

use Celema\Boiler\Exception\UnexpectedValueException;

/** @internal */
final class Methods
{
	/** @var array<non-empty-string, Method> */
	private array $methods = [];

	/**
	 * A template's registry layers over its engine's: methods added here stay
	 * on the template, while methods added to the engine, even later, remain
	 * visible unless the template overrides them.
	 */
	public function __construct(
		private readonly ?self $parent = null,
	) {}

	/** @param non-empty-string $name */
	public function add(string $name, callable $callable, bool $safe = false): void
	{
		if (self::isReserved($name)) {
			throw new UnexpectedValueException("Method name `{$name}` is reserved by a template helper");
		}

		$this->methods[$name] = new Method($callable, $safe);
	}

	public function get(string $name): Method
	{
		if (array_key_exists($name, $this->methods)) {
			return $this->methods[$name];
		}

		if ($this->parent !== null) {
			return $this->parent->get($name);
		}

		throw new UnexpectedValueException("Method '{$name}' does not exist");
	}

	/**
	 * Templates reach registered methods through Context::__call(), which PHP
	 * only calls for names the context does not define itself, ignoring case.
	 */
	private static function isReserved(string $name): bool
	{
		return in_array(
			strtolower($name),
			array_map(strtolower(...), get_class_methods(TemplateContext::class)),
			true,
		);
	}
}
