<?php

declare(strict_types=1);

namespace Celema\Boiler\Filter;

use Celema\Boiler\Contract;
use Override;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/** @api */
final class Sanitize implements Contract\Filter
{
	private ?HtmlSanitizer $sanitizer = null;

	#[Override]
	public function apply(string $value, mixed ...$args): string
	{
		return $this->sanitizer()->sanitize($value);
	}

	#[Override]
	public function safe(): bool
	{
		return true;
	}

	private function sanitizer(): HtmlSanitizer
	{
		return $this->sanitizer ??= new HtmlSanitizer(
			new HtmlSanitizerConfig()->allowSafeElements(),
		);
	}
}
