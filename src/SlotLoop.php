<?php

declare(strict_types=1);

namespace Celema\Boiler;

use Celema\Boiler\Exception\LogicException;
use Closure;
use Generator;

/**
 * Fills the slot of an inserted template from the body of the caller's
 * `foreach` loop, in two passes.
 *
 * The first pass renders the inserted template completely. Every
 * `$this->slot()` call records its data and prints a placeholder. The second
 * pass yields the recorded data to the loop, captures the output of each
 * iteration, and replaces the placeholders before the result is printed.
 * Only the caller's own generator is suspended in between, never the
 * inserted template.
 *
 * @internal
 */
final class SlotLoop
{
	/** @var list<array<array-key, mixed>> the data of every `slot()` call, in order */
	private array $calls = [];

	/** Random, so that placeholders of nested loops and in user data never match. */
	private readonly string $token;

	/**
	 * The section state when the first pass started; null outside of it.
	 *
	 * @var array{mode: SectionMode, name: string|null, level: int|null, location: Location|null}|null
	 */
	private ?array $checkpoint = null;

	/** Whether an iteration's output is being captured. */
	private bool $capturing = false;
	private bool $finished = false;
	private bool $closed = false;

	public function __construct(
		private readonly Location $location,
		private readonly Sections $sections,
	) {
		$this->token = bin2hex(random_bytes(8));
	}

	/**
	 * Called through `$this->slot()` in the inserted template.
	 *
	 * @param array<array-key, mixed> $data
	 */
	public function render(array $data): string
	{
		if ($this->checkpoint === null) {
			throw new LogicException(
				'The slot of an `each()` loop can only be rendered while its template renders',
			);
		}

		// A section would keep the placeholder beyond the loop.
		if ($this->sections->checkpoint() !== $this->checkpoint) {
			throw new LogicException('The slot of an `each()` loop cannot be rendered inside a section capture');
		}

		$placeholder = $this->placeholder(count($this->calls));
		$this->calls[] = $data;

		return $placeholder;
	}

	/**
	 * @param Closure(): string $render renders the inserted template
	 * @param Closure(array<array-key, mixed>): array<array-key, mixed> $wrap
	 *
	 * @return Generator<int, array<array-key, mixed>, mixed, void>
	 */
	public function run(Closure $render, Closure $wrap): Generator
	{
		$this->assertOpen();
		$content = null;

		/** @var list<string> $parts */
		$parts = [];

		try {
			$content = $this->renderTemplate($render);

			foreach ($this->calls as $data) {
				$checkpoint = $this->sections->checkpoint();
				ob_start();
				$this->capturing = true;

				yield $wrap($data);

				$parts[] = $this->capture($checkpoint);
			}
		} finally {
			// PHP destroys a generator that is left with break, return, or an
			// exception, which runs this block at that point.
			$this->finish($content, $parts);
		}
	}

	/**
	 * Fails a loop that has not finished by the time its template's code ends:
	 * one that was never iterated, or one kept in a variable after a break.
	 * In the latter case the template's later output went into the capture
	 * buffer of the pending iteration.
	 */
	public function assertFinished(): void
	{
		if ($this->finished) {
			return;
		}

		throw new LogicException(
			'The `each()` loop did not finish within its template; iterate it with foreach, and do not break out of a loop kept in a variable',
			location: $this->location,
		);
	}

	/**
	 * Called when the caller's render ends. A generator kept beyond it must
	 * not touch the output buffers of whatever renders when it is resumed or
	 * destroyed later.
	 */
	public function close(): void
	{
		$this->closed = true;
		$this->calls = [];
	}

	/** @param Closure(): string $render */
	private function renderTemplate(Closure $render): string
	{
		$this->checkpoint = $this->sections->checkpoint();

		try {
			return $render();
		} finally {
			$this->checkpoint = null;
		}
	}

	/** @param array{mode: SectionMode, name: string|null, level: int|null, location: Location|null} $checkpoint */
	private function capture(array $checkpoint): string
	{
		$this->assertOpen();
		// Otherwise the section's buffer would be taken for the output.
		$this->sections->assertClosed($checkpoint);
		$this->capturing = false;

		return (string) ob_get_clean();
	}

	/**
	 * Prints the inserted template with the captured output in place of its
	 * slot calls. Iterations that were not reached stay empty.
	 *
	 * @param list<string> $parts
	 */
	private function finish(?string $content, array $parts): void
	{
		if ($this->closed) {
			return;
		}

		if ($this->capturing) {
			$parts[] = (string) ob_get_clean();
		}

		if ($content !== null) {
			echo $this->fill($content, $parts);
		}

		$this->finished = true;
		$this->calls = [];
	}

	private function assertOpen(): void
	{
		if ($this->closed) {
			throw new LogicException(
				'The `each()` loop was resumed after its template finished',
				location: $this->location,
			);
		}
	}

	private function placeholder(int $index): string
	{
		return "\0{$this->token}:{$index}\0";
	}

	/** @param list<string> $parts */
	private function fill(string $content, array $parts): string
	{
		$replacements = [];

		foreach (array_keys($this->calls) as $index) {
			$replacements[$this->placeholder($index)] = $parts[$index] ?? '';
		}

		return strtr($content, $replacements);
	}
}
