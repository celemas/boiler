<?php

declare(strict_types=1);

namespace Celema\Boiler\Proxy;

use Celema\Boiler\Contract\PreservesSafety;
use Celema\Boiler\Contract\Wrapper;
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
}
