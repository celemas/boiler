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
	 * Where and at which position the main content of each section was
	 * captured.
	 *
	 * @var array<string, array{Location, list<int>}>
	 */
	private array $captured = [];

	/**
	 * How many discarded defaults are open. While one is, nothing reaches the
	 * sections, so a default that is not used adds nothing, not even through
	 * the templates it inserts.
	 */
	private int $muted = 0;

	/**
	 * Opens a capture of the main content and returns what closes it with the
	 * captured output and where `section()` opened it.
	 *
	 * A layout's capture is a default: a template it wraps renders first, so
	 * when one of those captured the section already, the layout's capture is
	 * discarded. Any other second capture fails instead of replacing the
	 * first.
	 *
	 * @return Closure(string, Location): void
	 */
	public function open(string $name): Closure
	{
		if ($this->muted > 0) {
			return static function (): void {};
		}

		$position = $this->position();
		$captured = $this->captured[$name] ?? null;

		if ($captured !== null && Section::wraps($position, $captured[1])) {
			$this->muted++;

			return function (): void {
				$this->muted--;
			};
		}

		return function (string $content, Location $location) use ($name, $position): void {
			$this->assign($name, $content, $location, $position);
		};
	}

	public function append(string $name, string $content): void
	{
		if ($this->muted > 0) {
			return;
		}

		($this->sections[$name] ??= new Section())->append($content, $this->position());
	}

	public function prepend(string $name, string $content): void
	{
		if ($this->muted > 0) {
			return;
		}

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
		$muted = $this->muted;
		$this->insert = [...$insert, $level, ++$this->calls];

		try {
			return $render();
		} finally {
			$this->insert = $insert;
			$this->level = $level;
			// A failed insert that the caller catches may leave a default open.
			$this->muted = $muted;
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

	/**
	 * The default stands in for main content that was never captured. A
	 * closure runs only then, and before the additions are collected, so
	 * those it makes to this section count.
	 *
	 * @param string|Closure(): string $default
	 */
	public function getOr(string $name, string|Closure $default): string
	{
		if (isset($this->captured[$name])) {
			return $this->sections[$name]->get();
		}

		$default = is_string($default) ? $default : $default();

		return ($this->sections[$name] ?? null)?->get($default) ?? $default;
	}

	public function has(string $name): bool
	{
		return isset($this->sections[$name]);
	}

	/**
	 * Sets only the main content; prepended and appended content stays.
	 *
	 * @param list<int> $position
	 */
	private function assign(string $name, string $content, Location $location, array $position): void
	{
		if (isset($this->captured[$name])) {
			throw new LogicException(
				"Section `{$name}` was already captured at {$this->captured[$name][0]}; "
					. 'add to it with append() or prepend(), or capture defaults in a layout',
				location: $location,
			);
		}

		$this->captured[$name] = [$location, $position];
		($this->sections[$name] ??= new Section())->setValue($content);
	}

	/** @return list<int> */
	private function position(): array
	{
		return [...$this->insert, $this->level, ++$this->calls];
	}
}
