<?php

declare(strict_types=1);

namespace Celema\Boiler;

use Closure;

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
	 * The position of the insert that is rendering, as the layout level and
	 * call number of each enclosing insert; empty for the rendered template.
	 *
	 * @var list<int>
	 */
	private array $insert = [];

	/**
	 * How many layouts deep the rendering template is, counted on from the
	 * level of the template that inserted it.
	 */
	private int $level = 0;

	/** Numbers appends and inserts in call order. */
	private int $calls = 0;

	/** Sets only the main content; prepended and appended content stays. */
	public function assign(string $name, string $content): void
	{
		($this->sections[$name] ??= new Section())->setValue($content);
	}

	public function append(string $name, string $content): void
	{
		($this->sections[$name] ??= new Section())->append($content, $this->position());
	}

	public function prepend(string $name, string $content): void
	{
		($this->sections[$name] ??= new Section())->prepend($content);
	}

	/**
	 * Renders an inserted template. Its appends, including those of its own
	 * layouts, stay together at the place of the insert among the caller's.
	 *
	 * @param Closure(): string $render
	 */
	public function nest(Closure $render): string
	{
		$insert = $this->insert;
		$level = $this->level;
		$this->insert = [...$insert, $level, ++$this->calls];

		try {
			return $render();
		} finally {
			$this->insert = $insert;
			$this->level = $level;
		}
	}

	/** Moves on to the next layout of the rendering template. */
	public function enterLayout(): void
	{
		$this->level++;
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

	/** @return list<int> */
	private function position(): array
	{
		return [...$this->insert, $this->level, ++$this->calls];
	}
}
