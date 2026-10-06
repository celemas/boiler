<?php

declare(strict_types=1);

namespace Celema\Boiler;

use Celema\Boiler\Exception\LogicException;
use Celema\Boiler\Exception\RuntimeException;
use Closure;
use Stringable;
use Throwable;

/** @internal */
final class SlotRenderer
{
	public function __construct(
		private readonly Closure $slot,
		private readonly Location $location,
	) {}

	/** @param array<array-key, mixed> $data */
	public function render(array $data): string
	{
		$level = ob_get_level();

		try {
			ob_start();

			try {
				/** @psalm-suppress MixedAssignment slot may echo, return markup, or both */
				$returned = ($this->slot)($data);
			} catch (RuntimeException|LogicException $e) {
				// Located errors, including nested render errors, pass through unchanged.
				if ($e->location() !== null) {
					throw $e;
				}

				throw $this->wrapException($e);
			} catch (Throwable $e) {
				throw $this->wrapException($e);
			}

			$captured = (string) ob_get_clean();

			return is_string($returned) || $returned instanceof Stringable
				? $captured . (string) $returned
				: $captured;
		} finally {
			while (ob_get_level() > $level) {
				ob_end_clean();
			}
		}
	}

	private function wrapException(Throwable $exception): RuntimeException
	{
		$code = $exception->getCode();
		$location = Location::fromThrowable($this->location->path, $exception);

		return new RuntimeException(
			$exception->getMessage(),
			is_int($code) ? $code : 0,
			$exception,
			$location->line === null ? $this->location : $location,
		);
	}
}
