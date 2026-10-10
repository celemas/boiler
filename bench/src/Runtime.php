<?php

declare(strict_types=1);

namespace Celema\Boiler\Bench;

/** Where the pages are rendered: a server the benchmark starts, or this process. */
interface Runtime
{
	/** Names the runtime and its PHP version for the report. */
	public function label(): string;

	/**
	 * Makes the candidate the one that sample() renders with.
	 *
	 * @param Candidate<object> $candidate
	 */
	public function serve(Candidate $candidate): void;

	public function sample(string $page, bool $html = false): Sample;

	public function stop(): void;
}
