<?php

declare(strict_types=1);

namespace Celema\Boiler\Proxy;

use Celema\Boiler\Exception\RuntimeException;
use Override;

/**
 * Fails `json_encode()` for a proxy. Without it, a proxy is encoded as `{}`:
 * the encoder sees an object without public properties instead of the
 * wrapped value.
 *
 * Requires the using class to implement `JsonSerializable`.
 *
 * @internal
 */
trait RefusesJson
{
	#[Override]
	public function jsonSerialize(): never
	{
		throw new RuntimeException(
			'A wrapped value cannot be encoded as JSON; encode the original: json_encode($this->unwrap($value))',
		);
	}
}
