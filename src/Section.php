<?php

declare(strict_types=1);

namespace Celema\Boiler;

/** @internal */
final class Section
{
	/** @var list<string> */
	private array $prepended = [];

	/** @var list<string> */
	private array $appended = [];

	public function __construct(
		private string $value,
	) {}

	public function prepend(string $content): self
	{
		$this->prepended[] = $content;

		return $this;
	}

	public function append(string $content): self
	{
		array_unshift($this->appended, $content);

		return $this;
	}

	public function empty(): bool
	{
		return $this->value === '';
	}

	public function get(): string
	{
		return implode('', array_merge($this->prepended, [$this->value], $this->appended));
	}

	public function setValue(string $value): void
	{
		$this->value = $value;
	}
}
