<?php

declare(strict_types=1);

namespace Celema\Boiler\Bench;

/** The measured render times of one candidate in one lifecycle. */
final class Result
{
	/** @var array<string, string> the rendered pages, for the output check */
	public array $output = [];

	/** @var list<array<string, float>> milliseconds per render of each page, one entry per iteration */
	private array $iterations = [];

	/** @param Candidate<object> $candidate */
	public function __construct(
		public readonly Candidate $candidate,
	) {}

	/** @param array<string, int> $nanoseconds the summed render time of each page */
	public function add(array $nanoseconds, int $runs): void
	{
		$this->iterations[] = array_map(
			static fn(int $time): float => ($time / $runs) / 1e6,
			$nanoseconds,
		);
	}

	/**
	 * The fastest iteration, which is the one least disturbed by other load.
	 *
	 * @return array<string, float>
	 */
	public function best(): array
	{
		$best = [];

		foreach ($this->iterations as $iteration) {
			if ($best === [] || array_sum($iteration) < array_sum($best)) {
				$best = $iteration;
			}
		}

		return $best;
	}

	/** How much slower the slowest iteration was than the fastest: a measure of noise. */
	public function spread(): float
	{
		$totals = array_map(array_sum(...), $this->iterations);

		return $totals === [] ? 0.0 : (max($totals) - min($totals)) / min($totals);
	}
}
