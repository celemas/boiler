<?php

declare(strict_types=1);

namespace Celema\Boiler\Proxy;

use ArrayAccess;
use Celema\Boiler\Contract\Wrapper;
use Celema\Boiler\Exception\OutOfBoundsException;
use Celema\Boiler\Exception\RuntimeException;
use Celema\Boiler\Exception\UnexpectedValueException;
use Countable;
use Iterator;
use Override;

/**
 * @api
 *
 * @psalm-type ArrayCallable = callable(mixed, mixed):int
 * @psalm-type FilterCallable = callable(mixed):mixed
 *
 * @template-implements ArrayAccess<array-key, mixed>
 * @template-implements Iterator<mixed>
 * @implements Proxy<array<array-key, mixed>>
 */
final class ArrayProxy implements ArrayAccess, Iterator, Countable, Proxy
{
	/** @var list<array-key> */
	private array $keys;
	private int $position;

	/**
	 * @param array<array-key, mixed> $array
	 */
	public function __construct(
		private array $array,
		private readonly Wrapper $wrapper,
	) {
		$this->array = $array;
		$this->keys = array_keys($array);
		$this->position = 0;
	}

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

	#[Override]
	public function rewind(): void
	{
		$this->position = 0;
	}

	#[Override]
	public function current(): mixed
	{
		$key = $this->keys[$this->position];

		return $this->wrapper->wrap($this->array[$key]);
	}

	/**
	 * @return array-key
	 */
	#[Override]
	public function key(): mixed
	{
		return $this->keys[$this->position];
	}

	#[Override]
	public function next(): void
	{
		$this->position++;
	}

	#[Override]
	public function valid(): bool
	{
		return isset($this->keys[$this->position]);
	}

	/** @param array-key $offset */
	#[Override]
	public function offsetExists(mixed $offset): bool
	{
		return array_key_exists($offset, $this->array);
	}

	/** @param array-key $offset */
	#[Override]
	public function offsetGet(mixed $offset): mixed
	{
		if (array_key_exists($offset, $this->array)) {
			return $this->wrapper->wrap($this->array[$offset]);
		}

		$key = is_numeric($offset) ? (string) $offset : "'{$offset}'";

		throw new OutOfBoundsException("Undefined array key {$key}");
	}

	#[Override]
	public function offsetSet(mixed $offset, mixed $value): void
	{
		if ($offset === null) {
			$this->array[] = $this->wrapper->unwrap($value);
		} else {
			$this->array[$offset] = $this->wrapper->unwrap($value);
		}

		$this->keys = array_keys($this->array);
	}

	#[Override]
	public function offsetUnset(mixed $offset): void
	{
		unset($this->array[$offset]);
		$this->keys = array_keys($this->array);
	}

	#[Override]
	public function count(): int
	{
		return count($this->array);
	}

	/** @param array-key $key */
	public function exists(mixed $key): bool
	{
		return array_key_exists($key, $this->array);
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

	/** @psalm-param ArrayCallable $callable */
	public function map(callable $callable): self
	{
		return new self(array_map($callable, $this->array), $this->wrapper);
	}

	/** @psalm-param FilterCallable $callable */
	public function filter(callable $callable): self
	{
		return new self(array_filter($this->array, $callable), $this->wrapper);
	}

	/** @psalm-param ArrayCallable $callable */
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
}
