<?php

declare(strict_types=1);

namespace Celema\Boiler\Proxy;

use Celema\Boiler\Exception\RuntimeException;
use Stringable;

/**
 * Template access to a wrapped object: properties, methods, invocation, and
 * string conversion, each wrapping what it returns. Shared by the proxies of
 * plain and traversable objects, so a collection or a menu item with children
 * offers the same access as any other object.
 *
 * Requires the using class to hold the object in `$value` and its wrapper in
 * `$wrapper`.
 *
 * @internal
 */
trait ObjectAccess
{
	public function __toString(): string
	{
		if (!$this->value instanceof Stringable) {
			throw new RuntimeException('Wrapped object is not stringable');
		}

		return $this->wrapper->escape((string) $this->value);
	}

	public function __get(string $name): mixed
	{
		if ($this->hasPublicProperty($name)) {
			return $this->wrapper->wrap($this->value->{$name});
		}

		throw new RuntimeException('No such property');
	}

	/**
	 * Backs `isset()`, `empty()`, and `??` on properties. Delegates to the
	 * object, so its own `__isset()` applies and null counts as missing.
	 */
	public function __isset(string $name): bool
	{
		return isset($this->value->{$name});
	}

	public function __set(string $name, mixed $value): void
	{
		if ($this->hasPublicProperty($name)) {
			$this->value->{$name} = $this->wrapper->unwrap($value);

			return;
		}

		throw new RuntimeException('No such property');
	}

	public function __call(string $name, array $args): mixed
	{
		if (is_callable([$this->value, $name])) {
			return $this->wrapper->wrap($this->value->{$name}(...$this->unwrapArgs($args)));
		}

		throw new RuntimeException('No such method');
	}

	public function __invoke(mixed ...$args): mixed
	{
		$object = $this->object();

		if (is_callable($object)) {
			return $this->wrapper->wrap($object(...$this->unwrapArgs($args)));
		}

		throw new RuntimeException('No such method');
	}

	/**
	 * The wrapped object, typed loosely: Psalm does not consider that a
	 * traversable class may define `__invoke()`.
	 */
	private function object(): object
	{
		return $this->value;
	}

	private function hasPublicProperty(string $name): bool
	{
		return array_key_exists($name, get_object_vars($this->value));
	}

	/**
	 * @param array<array-key, mixed> $args
	 * @return array<array-key, mixed>
	 */
	private function unwrapArgs(array $args): array
	{
		$unwrapped = $this->wrapper->unwrap($args);
		assert(is_array($unwrapped), 'Wrapper::unwrap must return an array for array input');

		return $unwrapped;
	}
}
