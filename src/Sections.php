<?php

declare(strict_types=1);

namespace Celema\Boiler;

use Celema\Boiler\Exception\LogicException;
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

	/**
	 * Where the main content of each section was captured.
	 *
	 * @var array<string, Location>
	 */
	private array $captured = [];

	/**
	 * Sets only the main content; prepended and appended content stays.
	 *
	 * A second capture fails instead of replacing the first. As a layout
	 * renders after the page, it would otherwise override the page's section
	 * where it meant a fallback.
	 *
	 * @param Location $location where `section()` opened the capture
	 */
	public function assign(string $name, string $content, Location $location): void
	{
		if (isset($this->captured[$name])) {
			throw new LogicException(
				"Section `{$name}` was already captured at {$this->captured[$name]}; "
					. 'add to it with append() or prepend(), or pass a fallback to yield()',
				location: $location,
			);
		}

		$this->captured[$name] = $location;
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
