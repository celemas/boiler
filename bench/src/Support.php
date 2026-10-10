<?php

declare(strict_types=1);

namespace Celema\Boiler\Bench;

final readonly class Support
{
	public function __construct(
		public string $email,
		public string $phone,
		public string $hours,
	) {}
}
