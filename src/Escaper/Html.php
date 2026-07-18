<?php

declare(strict_types=1);

namespace Celema\Boiler\Escaper;

use Celema\Boiler\Contract\Escaper;
use Override;

/** @api */
final class Html implements Escaper
{
	#[Override]
	public function escape(string $value): string
	{
		return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	}
}
