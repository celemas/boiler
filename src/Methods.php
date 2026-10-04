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
}
