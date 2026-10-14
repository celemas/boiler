<?php

declare(strict_types=1);

namespace Celema\Boiler;

use Celema\Boiler\Proxy\ArrayProxy;

/** @internal */
final readonly class LayoutSpec
{
	/** @param ArrayProxy|array<array-key, mixed> $context */
	public function __construct(
		public string $path,
		public Location $location,
		public ArrayProxy|array $context = [],
	) {}
}
