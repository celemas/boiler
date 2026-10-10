<?php

declare(strict_types=1);

namespace Celema\Boiler\Bench;

final readonly class Site
{
	public function __construct(
		public string $name,
		public string $tagline,
		public string $locale,
		public int $year,
		public Support $support,
	) {}
}
