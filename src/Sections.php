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

	public function assign(string $name, string $content): void
	{
		$this->sections[$name] = new Section($content);
	}

	public function append(string $name, string $content): void
	{
		$this->sections[$name] = ($this->sections[$name] ?? new Section(''))->append($content);
	}

	public function prepend(string $name, string $content): void
	{
		$this->sections[$name] = ($this->sections[$name] ?? new Section(''))->prepend($content);
	}

	public function get(string $name): string
	{
		return $this->sections[$name]->get();
	}

	public function getOr(string $name, string $default): string
	{
		$section = $this->sections[$name] ?? null;

		if ($section === null) {
			return $default;
		}

		if ($section->empty()) {
			$section->setValue($default);
		}

		return $section->get();
	}

	public function has(string $name): bool
	{
		return isset($this->sections[$name]);
	}
}
