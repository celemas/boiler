<?php

declare(strict_types=1);

// Global helpers, as an application would register them with each engine.

function money(float $amount): string
{
	return '$' . number_format($amount, 2);
}

/** Returns safe markup: the name is a literal in the templates. */
function icon(string $name): string
{
	return '<svg class="icon icon-' . $name . '" aria-hidden="true"><use href="/icons.svg#' . $name . '"></use></svg>';
}
