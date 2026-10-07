<?php

declare(strict_types=1);

namespace Celema\Boiler;

use Celema\Boiler\Exception\LogicException;
use Closure;
use Generator;
use Override;

/**
 * Fills the slot of an inserted template from the body of the caller's
 * `foreach` loop, in two passes.
 *
 * The first pass renders the inserted template completely. Every
 * `$this->slot()` call records its data and returns a placeholder. The second
 * pass yields the recorded data to the loop, captures the output of each
 * iteration, and replaces the placeholders, in the inserted template's output
 * and in sections, before the result is printed. Only the caller's own
 * generator is suspended in between, never the inserted template.
 *
 * @internal
 */
final class SlotLoop implements Slot
{
	/** @var list<array<array-key, mixed>> the data of every `slot()` call, in order */
	private array $calls = [];

	/**
	 * Random, so that placeholders of nested loops and in user data never
	 * match. Hex digits only, so that placeholders survive escaping and,
	 * matched case-insensitively, case filters.
	 */
	private readonly string $token;

	/** Whether the inserted template is rendering, which is the first pass. */
	private bool $rendering = false;

	/** Whether an iteration's output is being captured. */
	private bool $capturing = false;
	private bool $finished = false;
	private bool $closed = false;

	/** @param Blocks $blocks the open blocks of the template that runs the loop */
	public function __construct(
		private readonly Location $location,
		private readonly Blocks $blocks,
		private readonly Sections $sections,
	) {
		$this->token = bin2hex(random_bytes(8));
	}

	/** The loop body is the slot, even when it prints nothing. */
	#[Override]
	public function filled(): bool
	{
		return true;
	}

	/**
	 * Called through `$this->slot()` in the inserted template.
	 *
	 * @param array<array-key, mixed> $data
	 */
	#[Override]
	public function render(array $data): string
	{
		if (!$this->rendering) {
			throw new LogicException(
				'The slot of an `each()` loop can only be rendered while its template renders',
			);
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
				$depth = $this->blocks->depth();
				ob_start();
				$this->capturing = true;

				yield $wrap($data);

				$parts[] = $this->capture($depth);
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
		$this->rendering = true;

		try {
			return $render();
		} finally {
			$this->rendering = false;
		}
	}

	private function capture(int $depth): string
	{
		$this->assertOpen();
		// Otherwise the section's buffer would be taken for the output.
		$this->blocks->assertClosed($depth);
		$this->capturing = false;

		return (string) ob_get_clean();
	}

	/**
	 * Prints the inserted template with the captured output in place of its
	 * slot calls, which sections it captured get as well. Iterations that were
	 * not reached stay empty.
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
			$this->sections->map(fn(string $section): string => $this->fill($section, $parts));
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

	/**
	 * Letters, digits, and hyphens only, which `trim()`, HTML, URL, and
	 * JavaScript escaping leave alone.
	 */
	private function placeholder(int $index): string
	{
		return "boiler-slot-{$this->token}-{$index}-";
	}

	/** @param list<string> $parts */
	private function fill(string $content, array $parts): string
	{
		return (
			preg_replace_callback(
				"/boiler-slot-{$this->token}-(\\d+)-/i",
				static fn(array $match): string => $parts[(int) $match[1]] ?? '',
				$content,
			) ?? $content
		);
	}
}
