<?php

declare(strict_types=1);

namespace Celema\Boiler\Bench;

final readonly class Image
{
	public function __construct(
		public string $src,
		public string $alt,
		public int $width,
		public int $height,
	) {}
}
