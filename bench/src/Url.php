<?php

declare(strict_types=1);

namespace Celema\Boiler\Bench;

use Override;
use Stringable;

final readonly class Url implements Stringable
{
	/** @param array<string, int|string> $query */
	public function __construct(
		public string $path,
		public array $query = [],
	) {}

	#[Override]
	public function __toString(): string
	{
		return $this->query === [] ? $this->path : $this->path . '?' . http_build_query($this->query);
	}
}
