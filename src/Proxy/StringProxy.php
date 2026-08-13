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

		// preg_match() warns on compile errors instead of throwing; capture the
		// message, which is more specific than preg_last_error_msg() there.
		$warning = null;
		set_error_handler(static function (int $severity, string $message) use (&$warning): bool {
			$warning = $message;

			return true;
		});

		try {
			$result = preg_match($pattern, $this->value);
		} finally {
			restore_error_handler();
		}

		if ($result === false) {
			throw new UnexpectedValueException(
				"Regex error for pattern '{$pattern}': " . ($warning ?? preg_last_error_msg()),
			);
		}

		return $result === 1;
	}

	public function contains(string|self $needle): bool
	{
		return str_contains($this->value, $needle instanceof self ? $needle->value : $needle);
	}

	public function startsWith(string|self $needle): bool
	{
		return str_starts_with($this->value, $needle instanceof self ? $needle->value : $needle);
	}

	public function endsWith(string|self $needle): bool
	{
		return str_ends_with($this->value, $needle instanceof self ? $needle->value : $needle);
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
