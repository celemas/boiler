<?php

declare(strict_types=1);

namespace Celema\Boiler\Proxy;

use Celema\Boiler\Contract\PreservesSafety;
use Celema\Boiler\Contract\Wrapper;
use Celema\Boiler\Exception\UnexpectedValueException;
use Override;

/**
 * @api
 *
 * @implements Proxy<string>
 */
final class StringProxy implements Proxy
{
	private ?string $escaped = null;
	private bool $safe = false;

	public function __construct(
		private readonly string $value,
		private readonly Wrapper $wrapper,
	) {}

	public static function safe(string $value, Wrapper $wrapper): self
	{
		$proxy = new self($value, $wrapper);
		$proxy->safe = true;

		return $proxy;
	}

	/**
	 * Dispatch filters as virtual methods: $title->sanitize(), $title->stripTags('<b>'), etc.
	 *
	 * @param array<array-key, mixed> $args
	 */
	public function __call(string $name, array $args): self
	{
		$filter = $this->wrapper->filter($name);
		$filtered = $filter->apply($this->value, ...$args);
		$proxy = new self($filtered, $this->wrapper);
		$proxy->safe = $filter->safe() || $this->safe && $filter instanceof PreservesSafety;

		return $proxy;
	}

	public function __toString(): string
	{
		if ($this->safe) {
			return $this->value;
		}

		return $this->escaped ??= $this->wrapper->escape($this->value);
	}

	public function escape(?string $escaper = null): string
	{
		return $this->wrapper->escape($this->value, $escaper);
	}

	#[Override]
	public function unwrap(): string
	{
		return $this->value;
	}

	#[Override]
	public function is(mixed $other): bool
	{
		return $this->value === ($other instanceof Proxy ? $other->unwrap() : $other);
	}

	public function matches(string|self $pattern): bool
	{
		$pattern = $pattern instanceof self ? $pattern->value : $pattern;

		if ($pattern === '') {
			throw new UnexpectedValueException('Empty regex pattern');
		}

		// @mago-expect lint:no-error-control-operator The failure surfaces as an exception, not template output.
		$result = @preg_match($pattern, $this->value);

		if ($result === false) {
			throw new UnexpectedValueException(
				"Regex error for pattern '{$pattern}': " . preg_last_error_msg(),
			);
		}

		return $result === 1;
	}

	/** @param ArrayProxy|array<array-key, mixed> $haystack */
	#[Override]
	public function in(ArrayProxy|array $haystack): bool
	{
		if ($haystack instanceof ArrayProxy) {
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
}
