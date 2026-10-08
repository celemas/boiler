<?php

declare(strict_types=1);

namespace Celema\Boiler;

/** @internal */
final class Section
{
	/** @var array<int, list<string>> per layout level, each in call order */
	private array $prepended = [];

	/** @var array<int, list<string>> per layout level, each in call order */
	private array $appended = [];

	/** The main content, null until `section()` captures it, even when empty. */
	private ?string $value = null;

	public function prepend(string $content, int $level): void
	{
		$this->prepended[$level][] = $content;
	}

	public function append(string $content, int $level): void
	{
		$this->appended[$level][] = $content;
	}

	/**
	 * The additions of a layout stay closer to the main content than those of
	 * the template it wraps: page prepends, layout prepends, main content,
	 * layout appends, page appends. The default stands in for main content
	 * that was never captured.
	 */
	public function get(string $default = ''): string
	{
		$prepended = $this->prepended;
		$appended = $this->appended;
		ksort($prepended);
		krsort($appended);

		return (
			implode('', array_merge(...$prepended))
				. ($this->value ?? $default)
				. implode('', array_merge(...$appended))
		);
	}

	public function setValue(string $value): void
	{
		$this->value = $value;
	}
}
