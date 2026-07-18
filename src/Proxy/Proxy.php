<?php

declare(strict_types=1);

namespace Celema\Boiler\Proxy;

/** @template-covariant TValue */
interface Proxy
{
	/** @return TValue */
	public function unwrap(): mixed;
}
