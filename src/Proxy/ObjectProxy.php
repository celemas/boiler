<?php

declare(strict_types=1);

namespace Celema\Boiler\Proxy;

use Celema\Boiler\Contract\Wrapper;
use Celema\Boiler\Exception\UnexpectedValueException;
use Override;
use Traversable;

/**
 * @api
 *
 * @implements Proxy<object>
 */
final class ObjectProxy implements Proxy
{
	use ObjectAccess;

	public function __construct(
		private readonly object $value,
		private readonly Wrapper $wrapper,
	) {
		if ($this->value instanceof Traversable) {
			throw new UnexpectedValueException('Traversable objects must be wrapped as iterator proxies');
		}
	}

	#[Override]
	public function unwrap(): object
	{
		return $this->value;
	}

	#[Override]
	public function is(mixed $other): bool
	{
		return $this->value === ($other instanceof Proxy ? $other->unwrap() : $other);
	}

	/** @param ArrayProxy|array<array-key, mixed> $haystack */
	#[Override]
	public function in(ArrayProxy|array $haystack): bool
	{
		if ($haystack instanceof ArrayProxy) {
			$haystack = $haystack->unwrap();
		}

		/** @var mixed $item */
		foreach ($haystack as $item) {
			if ($this->is($item)) {
				return true;
			}
		}

		return false;
	}
}
