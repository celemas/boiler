<?php

declare(strict_types=1);

namespace Celema\Boiler\Bench;

use Closure;
use RuntimeException;

/** One timed render, as a runtime reports it to the benchmark. */
final readonly class Sample
{
	public function __construct(
		public int $nanoseconds,
		public int $memory,
		public ?string $html = null,
		public ?int $held = null,
	) {}

	/**
	 * @param Closure(): string $render
	 * @param int|null $held what the engine kept in memory before this render
	 */
	public static function take(Closure $render, ?int $held = null): self
	{
		memory_reset_peak_usage();
		$before = memory_get_usage();
		$start = hrtime(true);
		$html = $render();
		$nanoseconds = hrtime(true) - $start;

		return new self($nanoseconds, memory_get_peak_usage() - $before, $html, $held);
	}

	public function json(bool $html): string
	{
		return json_encode([
			'nanoseconds' => $this->nanoseconds,
			'memory' => $this->memory,
			'html' => $html ? $this->html : null,
			'held' => $this->held,
		], JSON_THROW_ON_ERROR);
	}

	public static function fromJson(string $json): self
	{
		$data = json_decode($json, true);

		if (!is_array($data) || !is_int($data['nanoseconds'] ?? null) || !is_int($data['memory'] ?? null)) {
			throw new RuntimeException('Unexpected response: ' . substr($json, 0, 500));
		}

		$html = $data['html'] ?? null;
		$held = $data['held'] ?? null;

		return new self(
			$data['nanoseconds'],
			$data['memory'],
			is_string($html) ? $html : null,
			is_int($held) ? $held : null,
		);
	}
}
