<?php

declare(strict_types=1);

namespace Celema\Boiler\Proxy;

use Celema\Boiler\Contract\Wrapper;
use Celema\Boiler\Exception\RuntimeException;
use Celema\Boiler\Exception\UnexpectedValueException;
use Override;
use Stringable;
use Traversable;

/**
 * @api
 *
 * @implements Proxy<object>
 */
final class ObjectProxy implements Proxy
{
	public function __construct(
		private readonly object $value,
		private readonly Wrapper $wrapper,
	) {
		if ($this->value instanceof Traversable) {
			throw new UnexpectedValueException('Traversable objects must be wrapped as iterator proxies');
		}
	}

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
		if (is_callable($this->value)) {
			return $this->wrapper->wrap(($this->value)(...$this->unwrapArgs($args)));
		}

		throw new RuntimeException('No such method');
	}

	#[Override]
	public function unwrap(): object
	{
		return $this->value;
	}

	#[Override]
	public function is(mixed $other): bool
	{
		return $this->value === ($other instanceof Proxy ? $other->unwrap() : $other);
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
