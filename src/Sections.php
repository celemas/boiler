<?php

declare(strict_types=1);

namespace Celema\Boiler;

use Celema\Boiler\Exception\LogicException;

/** @internal */
final class Sections
{
	/** @var array<string, Section> */
	private array $sections = [];
	/** @var list<string> */
	private array $capture = [];
	private SectionMode $sectionMode = SectionMode::Closed;
	private ?int $captureLevel = null;
	private ?Location $captureLocation = null;

	public function begin(string $name, ?Location $location = null): void
	{
		$this->open($name, SectionMode::Assign, $location);
	}

	public function append(string $name, ?Location $location = null): void
	{
		$this->open($name, SectionMode::Append, $location);
	}

	public function prepend(string $name, ?Location $location = null): void
	{
		$this->open($name, SectionMode::Prepend, $location);
	}

	public function end(): void
	{
		if ($this->sectionMode === SectionMode::Closed) {
			throw new LogicException('No section started');
		}

		$name = $this->name();

		if ($this->captureLevel !== ob_get_level()) {
			throw new LogicException("Section capture block `{$name}` is not the active output buffer");
		}

		$content = (string) ob_get_clean();
		array_pop($this->capture);

		$this->sections[$name] = match ($this->sectionMode) {
			SectionMode::Assign => new Section($content),
			SectionMode::Append => ($this->sections[$name] ?? new Section(''))->append($content),
			SectionMode::Prepend => ($this->sections[$name] ?? new Section(''))->prepend($content),
		};

		$this->sectionMode = SectionMode::Closed;
		$this->captureLevel = null;
		$this->captureLocation = null;
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

	/** @return array{mode: SectionMode, name: string|null, level: int|null, location: Location|null} */
	public function checkpoint(): array
	{
		return [
			'mode' => $this->sectionMode,
			'name' => $this->sectionMode === SectionMode::Closed ? null : $this->name(),
			'level' => $this->captureLevel,
			'location' => $this->captureLocation,
		];
	}

	/** @param array{mode: SectionMode, name: string|null, level: int|null, location: Location|null} $checkpoint */
	public function assertClosed(array $checkpoint): void
	{
		if ($this->checkpoint() === $checkpoint) {
			return;
		}

		if ($this->sectionMode === SectionMode::Closed) {
			$name = $checkpoint['name'] ?? 'unknown';
			$location = $checkpoint['location'] ?? null;

			throw new LogicException(
				"Section capture block `{$name}` was closed unexpectedly" . $this->at($location),
				location: $location,
			);
		}

		throw new LogicException(
			"Unclosed section capture block `{$this->name()}`" . $this->at($this->captureLocation),
			location: $this->captureLocation,
		);
	}

	private function open(string $name, SectionMode $mode, ?Location $location): void
	{
		if ($this->sectionMode !== SectionMode::Closed) {
			throw new LogicException('Nested sections are not allowed');
		}

		$this->sectionMode = $mode;
		$this->capture[] = $name;
		$this->captureLocation = $location;
		ob_start();
		$this->captureLevel = ob_get_level();
	}

	private function at(?Location $location): string
	{
		return $location === null ? '' : " at {$location}";
	}

	private function name(): string
	{
		$last = array_key_last($this->capture);

		if ($last === null) {
			// This check is mostly necessary to satisfy Psalm.
			//
			// It serves as a defensive guard against corrupted internal state,
			// which should never occur when Sections is used internally;
			// public API calls cannot reach this branch.
			// @codeCoverageIgnoreStart
			throw new LogicException('No section started');

			// @codeCoverageIgnoreEnd
		}

		return $this->capture[$last];
	}
}
