<?php

declare(strict_types=1);

namespace Celema\Boiler\Proxy;

use ArrayAccess;
use Celema\Boiler\Contract\Wrapper;
use Celema\Boiler\Exception\OutOfBoundsException;
use Celema\Boiler\Exception\RuntimeException;
use Celema\Boiler\Exception\UnexpectedValueException;
use Countable;
use Generator;
use IteratorAggregate;
use Override;

/**
 * @api
 *
 * @psalm-type ArrayCallable = callable(mixed, mixed):int
 * @psalm-type FilterCallable = callable(mixed):mixed
 * @psalm-type MapCallable = callable(mixed):mixed
 * @psalm-type ReduceCallable = callable(mixed, mixed):mixed
 *
 * @template-implements ArrayAccess<array-key|StringProxy, mixed>
 * @template-implements IteratorAggregate<mixed, mixed>
 * @implements Proxy<array<array-key, mixed>>
 */
final class ArrayProxy implements ArrayAccess, IteratorAggregate, Countable, Proxy
{
	/**
	 * @param array<array-key, mixed> $array
	 */
	public function __construct(
		private array $array,
		private readonly Wrapper $wrapper,
	) {}

	#[Override]
	public function unwrap(): array
	{
		return $this->array;
	}

	#[Override]
	public function is(mixed $other): bool
	{
		return $this->array === ($other instanceof Proxy ? $other->unwrap() : $other);
	}

	/** @param self|array<array-key, mixed> $haystack */
	#[Override]
	public function in(self|array $haystack): bool
	{
		if ($haystack instanceof self) {
			$haystack = $haystack->unwrap();
		}

		/** @var mixed $item */
		foreach ($haystack as $item) {
			if ($this->is($item)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Every loop gets its own generator, so nested loops over the same proxy
	 * do not share a cursor. Keys are wrapped like values: a string key is
	 * template output as much as the value is.
	 *
	 * @return Generator<mixed, mixed>
	 */
	#[Override]
	public function getIterator(): Generator
	{
		/** @var mixed $value */
		foreach ($this->array as $key => $value) {
			yield $this->wrapper->wrap($key) => $this->wrapper->wrap($value);
		}
	}

	/**
	 * Backs `isset()` and `??`, so a null value counts as missing, as with a
	 * plain array. Use `exists()` to test for the key alone.
	 *
	 * @param array-key|StringProxy $offset
	 */
	#[Override]
	public function offsetExists(mixed $offset): bool
	{
		return isset($this->array[self::key($offset)]);
	}

	/** @param array-key|StringProxy $offset */
	#[Override]
	public function offsetGet(mixed $offset): mixed
	{
		$offset = self::key($offset);

		if (array_key_exists($offset, $this->array)) {
			return $this->wrapper->wrap($this->array[$offset]);
		}

		$key = is_numeric($offset) ? (string) $offset : "'{$offset}'";

		throw new OutOfBoundsException("Undefined array key {$key}");
	}

	/** @param array-key|StringProxy|null $offset */
	#[Override]
	public function offsetSet(mixed $offset, mixed $value): void
	{
		if ($offset === null) {
			$this->array[] = $this->wrapper->unwrap($value);
		} else {
			$this->array[self::key($offset)] = $this->wrapper->unwrap($value);
		}
	}

	/** @param array-key|StringProxy $offset */
	#[Override]
	public function offsetUnset(mixed $offset): void
	{
		unset($this->array[self::key($offset)]);
	}

	#[Override]
	public function count(): int
	{
		return count($this->array);
	}

	/** @param array-key|StringProxy $key */
	public function exists(mixed $key): bool
	{
		return array_key_exists(self::key($key), $this->array);
	}

	public function contains(mixed $value): bool
	{
		return in_array($value instanceof Proxy ? $value->unwrap() : $value, $this->array, true);
	}

	public function merge(array|self $array): self
	{
		return new self(array_merge(
			$this->array,
			$array instanceof self ? $array->unwrap() : $array,
		), $this->wrapper);
	}

	/** @psalm-param MapCallable $callable */
	public function map(callable $callable): self
	{
		return new self(array_map($callable, $this->array), $this->wrapper);
	}

	/** @psalm-param FilterCallable $callable */
	public function filter(callable $callable): self
	{
		return new self(array_filter($this->array, $callable), $this->wrapper);
	}

	/** @psalm-param ReduceCallable $callable */
	public function reduce(callable $callable, mixed $initial = null): mixed
	{
		return $this->wrapper->wrap(array_reduce($this->array, $callable, $initial));
	}

	/** @psalm-param ArrayCallable $callable */
	public function sorted(string $mode = '', ?callable $callable = null): self
	{
		$mode = strtolower(trim($mode));

		if (str_starts_with($mode, 'u')) {
			if (!is_callable($callable)) {
				throw new RuntimeException('No callable provided for user defined sorting');
			}

			return $this->usort($this->array, $mode, $callable);
		}

		return $this->sort($this->array, $mode);
	}

	private function sort(array $array, string $mode): self
	{
		match ($mode) {
			'' => sort($array),
			'ar' => arsort($array),
			'a' => asort($array),
			'kr' => krsort($array),
			'k' => ksort($array),
			'r' => rsort($array),
			default => throw new UnexpectedValueException("Sort mode '{$mode}' not supported"),
		};

		return new self($array, $this->wrapper);
	}

	/** @psalm-param ArrayCallable $callable */
	private function usort(array $array, string $mode, callable $callable): self
	{
		match ($mode) {
			'ua' => uasort($array, $callable),
			'u' => usort($array, $callable),
			default => throw new UnexpectedValueException("Sort mode '{$mode}' not supported"),
		};

		return new self($array, $this->wrapper);
	}

	/**
	 * Iteration hands out string keys wrapped, so accept them back as offsets.
	 *
	 * @param array-key|StringProxy $offset
	 * @return array-key
	 */
	private static function key(mixed $offset): mixed
	{
		return $offset instanceof StringProxy ? $offset->unwrap() : $offset;
	}
}
