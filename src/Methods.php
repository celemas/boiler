<?php

declare(strict_types=1);

namespace Celema\Boiler;

use Celema\Boiler\Exception\UnexpectedValueException;

/** @internal */
final class Methods
{
	/** @var array<non-empty-string, Method> */
	private array $methods = [];

	/** @param non-empty-string $name */
	public function add(string $name, callable $callable, bool $safe = false): void
	{
		$this->methods[$name] = new Method($callable, $safe);
	}

	public function get(string $name): Method
	{
		return array_key_exists($name, $this->methods)
			? $this->methods[$name]
			: throw new UnexpectedValueException("Method '{$name}' does not exist");
	}
}
