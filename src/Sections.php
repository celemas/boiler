<?php

declare(strict_types=1);

namespace Celema\Boiler;

/**
 * The sections captured during a render, shared by the rendered template,
 * its inserts, and its layouts.
 *
 * @internal
 */
final class Sections
{
	/** @var array<string, Section> */
	private array $sections = [];

	/**
	 * How many layouts deep the rendering template is: 0 for the rendered
	 * template and its inserts, 1 for its layout, and so on.
	 */
	private int $level = 0;

	/** Sets only the main content; prepended and appended content stays. */
	public function assign(string $name, string $content): void
	{
		($this->sections[$name] ??= new Section())->setValue($content);
	}

	public function append(string $name, string $content): void
	{
		($this->sections[$name] ??= new Section())->append($content, $this->level);
	}

	public function prepend(string $name, string $content): void
	{
		($this->sections[$name] ??= new Section())->prepend($content, $this->level);
	}

	public function level(): int
	{
		return $this->level;
	}

	public function setLevel(int $level): void
	{
		$this->level = $level;
	}

	public function get(string $name): string
	{
		return $this->sections[$name]->get();
	}

	public function getOr(string $name, string $default): string
	{
		return ($this->sections[$name] ?? null)?->get($default) ?? $default;
	}

	public function has(string $name): bool
	{
		return isset($this->sections[$name]);
	}
}
