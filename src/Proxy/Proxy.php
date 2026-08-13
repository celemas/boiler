<?php

declare(strict_types=1);

namespace Celema\Boiler\Proxy;

/**
 * @api
 *
 * @template-covariant TValue
 */
interface Proxy
{
	/** @return TValue */
	public function unwrap(): mixed;

	/**
	 * Strict comparison of the wrapped value against `$other`, unwrapping
	 * `$other` first when it is a proxy itself. `===` compares the proxy
	 * object and is therefore always false against a plain value.
	 */
	public function is(mixed $other): bool;
}
