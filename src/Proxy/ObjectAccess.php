<?php

declare(strict_types=1);

namespace Celema\Boiler\Proxy;

use Celema\Boiler\Exception\RuntimeException;
use ReflectionClass;
use ReflectionProperty;
use Stringable;

/**
 * Template access to a wrapped object: properties, methods, invocation, and
 * string conversion, each wrapping what it returns. Shared by the proxies of
 * plain and traversable objects, so a collection or a menu item with children
 * offers the same access as any other object.
 *
 * Requires the using class to hold the object in `$value`, its wrapper in
 * `$wrapper`, and whether its string conversion skips escaping in `$safe`.
 *
 * @internal
 */
trait ObjectAccess
{
	/** @var array<class-string, array<string, ReflectionProperty>> public instance properties by class */
	private static array $declared = [];

	public function __toString(): string
	{
		if (!$this->value instanceof Stringable) {
			throw new RuntimeException('Wrapped object is not stringable');
		}

		$string = (string) $this->value;

		return $this->safe ? $string : $this->wrapper->escape($string);
	}

	public function __get(string $name): mixed
	{
		// Reflection spares the full lookup, which copies all properties and runs
		// their get hooks. Dynamic properties and lazy proxies still need it.
		$declared = self::$declared[$this->value::class] ?? $this->declaredProperties();
		$readable = isset($declared[$name]) && $declared[$name]->isInitialized($this->value);

		// An object with `__get()` answers for the properties it does not expose,
		// as it does outside a template.
		if ($readable || method_exists($this->value, '__get') || $this->hasPublicProperty($name)) {
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

	/** @param array<array-key, mixed> $args */
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

	/** @return array<string, ReflectionProperty> */
	private function declaredProperties(): array
	{
		return self::$declared[$this->value::class] = array_column(
			array_filter(
				new ReflectionClass($this->value)->getProperties(ReflectionProperty::IS_PUBLIC),
				static fn(ReflectionProperty $property): bool => !$property->isStatic(),
			),
			null,
			'name',
		);
	}

	/**
	 * @param array<array-key, mixed> $args
	 * @return array<array-key, mixed>
	 */
	private function unwrapArgs(array $args): array
	{
		// Spares the common call without arguments the pass through the wrapper.
		$unwrapped = $args === [] ? $args : $this->wrapper->unwrap($args);
		assert(is_array($unwrapped), 'Wrapper::unwrap must return an array for array input');

		return $unwrapped;
	}
}
