<?php

declare(strict_types=1);

namespace Celema\Boiler;

use Celema\Boiler\Proxy\ArrayProxy;

/** @internal */
final readonly class LayoutSpec
{
	public function __construct(
		public string $path,
		public Location $location,
		public ArrayProxy|array $context = [],
	) {}
}
