<?php

declare(strict_types=1);

namespace Celema\Boiler\Tests;

class TrustedValue extends TrustedBase
{
	public string $note = '<i>note</i>';

	public function __toString(): string
	{
		return '<h1>headline</h1>';
	}

	public function paragraph(string $content): string
	{
		return "<p>{$content}</p>";
	}

	public function copy(): self
	{
		return new self();
	}
}
