<?php

declare(strict_types=1);

namespace Celema\Boiler;

use Celema\Boiler\Proxy\Proxy;

/**
 * Returns the original of a value that Boiler wrapped for a template, for
 * PHP code that gets values from templates. Unwraps the items of a plain
 * array and returns other values as they are. Templates use `u()`.
 *
 * @api
 */
function unwrap(mixed $value): mixed
{
	if ($value instanceof Proxy) {
		return $value->unwrap();
	}

	if (!is_array($value)) {
		return $value;
	}

	$unwrapped = [];

	/** @var mixed $item */
	foreach ($value as $key => $item) {
		/** @psalm-suppress MixedAssignment unwrapping returns mixed by design */
		$unwrapped[$key] = unwrap($item);
	}

	return $unwrapped;
}
