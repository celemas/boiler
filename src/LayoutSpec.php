<?php

declare(strict_types=1);

namespace Celema\Boiler;

/** @internal */
final readonly class LayoutSpec
{
	/**
	 * @param non-empty-string $path
	 */
	public function __construct(
		public string $path,
		public Location $location,
		public array $context = [],
	) {}
}
