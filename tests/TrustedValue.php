<?php

declare(strict_types=1);

namespace Celema\Boiler\Tests;

class TrustedValue extends TrustedBase
{
	public function __toString(): string
	{
		return '<h1>headline</h1>';
	}

	public function paragraph(string $content): string
	{
		return "<p>{$content}</p>";
	}
}
