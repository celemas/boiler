<?php

declare(strict_types=1);

namespace Celema\Boiler;

use Celema\Boiler\Proxy\ArrayProxy;
use Celema\Boiler\Proxy\IteratorProxy;
use Celema\Boiler\Proxy\ObjectProxy;
use Celema\Boiler\Proxy\Proxy;
use Celema\Boiler\Proxy\StringProxy;
use Traversable;

/**
 * Returns the original of a value that Boiler wrapped for a template, for
 * PHP code that gets values from templates. Unwraps the items of a plain
 * array and returns other values as they are. Templates use `u()`.
 *
 * @api
 *
 * @template T
 *
 * @param T $value
 *
 * @return (T is StringProxy ? string : (T is ArrayProxy ? array<array-key, mixed> : (T is IteratorProxy ? Traversable<mixed, mixed> : (T is ObjectProxy ? object : (T is array ? array<key-of<T>, mixed> : T)))))
 */
function unwrap(mixed $value): mixed
{
	if ($value instanceof Proxy) {
		/** @var ArrayProxy|IteratorProxy|ObjectProxy|StringProxy $value */
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
