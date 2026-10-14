<?php

declare(strict_types=1);

use Celema\Boiler\Exception\LogicException;
use Celema\Boiler\TemplateFunctions;

use function Celema\Boiler\unwrap;

/**
 * Returns the original of a wrapped value, for what PHP does with the value
 * itself: conditions, comparisons, `match`, and native functions. Unwraps the
 * items of a plain array and returns other values as they are, like
 * `Celema\Boiler\unwrap()`, which PHP code uses.
 */
function u(mixed $value): mixed
{
	return unwrap($value);
}

/**
 * Wraps a value as an escaped render passes it to a template, with the
 * escapers, filters, and trusted classes of the render in progress, also in
 * an unescaped render.
 *
 * @psalm-suppress InternalMethod the template functions are part of Boiler
 */
function w(mixed $value): mixed
{
	$wrapper = TemplateFunctions::wrapper() ?? throw new LogicException('w() works only while a template renders');

	return $wrapper->wrap($value);
}
