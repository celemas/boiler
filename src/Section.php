<?php

declare(strict_types=1);

namespace Celema\Boiler;

use Closure;

/** @internal */
final class Section
{
	/** @var array<int, list<string>> per layout level, each in call order */
	private array $prepended = [];

	/** @var array<int, list<string>> per layout level, each in call order */
	private array $appended = [];

	public function __construct(
		private string $value = '',
	) {}

	public function prepend(string $content, int $level): void
	{
		$this->prepended[$level][] = $content;
	}

	public function append(string $content, int $level): void
	{
		$this->appended[$level][] = $content;
	}

	/** @param Closure(string): string $map */
	public function map(Closure $map): void
	{
		$this->value = $map($this->value);
		$this->prepended = array_map(static fn(array $parts): array => array_map($map, $parts), $this->prepended);
		$this->appended = array_map(static fn(array $parts): array => array_map($map, $parts), $this->appended);
	}

	public function empty(): bool
	{
		return $this->value === '';
	}

	/**
	 * The additions of a layout stay closer to the main content than those of
	 * the template it wraps: page prepends, layout prepends, main content,
	 * layout appends, page appends.
	 */
	public function get(): string
	{
		$prepended = $this->prepended;
		$appended = $this->appended;
		ksort($prepended);
		krsort($appended);

		return implode('', array_merge(...$prepended)) . $this->value . implode('', array_merge(...$appended));
	}

	public function setValue(string $value): void
	{
		$this->value = $value;
	}
}
