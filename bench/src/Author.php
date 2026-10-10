<?php

declare(strict_types=1);

namespace Celema\Boiler\Bench;

final readonly class Author
{
	public function __construct(
		public string $name,
		public string $role,
		public string $bio,
		public string $url,
	) {}
}
