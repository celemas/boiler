<?php

declare(strict_types=1);

namespace Celema\Boiler\Bench;

use LogicException;
use Override;

/** Renders in this process with an engine that is reused, as a worker does. */
final class Loop implements Runtime
{
	/** @var array<string, array<string, mixed>> */
	private readonly array $pages;

	/** @var Candidate<object>|null */
	private ?Candidate $candidate = null;
	private ?object $engine = null;

	public function __construct(int $scale)
	{
		$this->pages = Data::pages($scale);
	}

	#[Override]
	public function label(): string
	{
		return 'this process, PHP ' . PHP_VERSION;
	}

	#[Override]
	public function serve(Candidate $candidate): void
	{
		$this->candidate = $candidate;
		$this->engine = $candidate->engine();
	}

	#[Override]
	public function sample(string $page, bool $html = false): Sample
	{
		$candidate = $this->candidate;
		$engine = $this->engine;

		if ($candidate === null || $engine === null) {
			throw new LogicException('No candidate to render with');
		}

		$context = $this->pages[$page];

		return Sample::take(static fn(): string => $candidate->render($engine, $page, $context));
	}

	#[Override]
	public function stop(): void
	{
		$this->candidate = null;
		$this->engine = null;
	}
}
