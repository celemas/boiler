<?php

declare(strict_types=1);

namespace Celema\Boiler;

use Celema\Boiler\Contract\Wrapper;
use Celema\Boiler\Exception\RuntimeException;
use Celema\Boiler\Proxy\StringProxy;
use Stringable;

/**
 * The values a template's code receives: its render context, wrapped in an
 * escaped render, and the results of its template methods.
 *
 * Kept apart from Context, because templates run in the scope of their
 * context object, where every method of Context would shadow a registered
 * template method of the same name.
 *
 * @internal
 */
final class Values
{
	/** @var array<array-key, mixed>|null */
	private ?array $wrapped = null;

	public function __construct(
		private array $context,
		public readonly Wrapper $wrapper,
		private readonly bool $autoescape,
	) {}

	public function get(array $values = []): array
	{
		if (!$this->autoescape) {
			return $values === []
				? $this->context
				: array_merge($this->context, $values);
		}

		if ($values === []) {
			return $this->wrapped();
		}

		return array_merge($this->wrapped(), $this->wrapAll($values));
	}

	public function add(string $key, mixed $value): mixed
	{
		$this->context[$key] = $value;
		$this->wrapped = null;

		return $this->output($value);
	}

	/** What a template receives for a value, such as the result of a template method. */
	public function output(mixed $value, bool $safe = false): mixed
	{
		if (!$this->autoescape) {
			return $this->wrapper->unwrap($value);
		}

		if (!$safe) {
			return $this->wrapper->wrap($value);
		}

		if ($value instanceof StringProxy) {
			return StringProxy::safe($value->unwrap(), $this->wrapper);
		}

		if (is_string($value) || $value instanceof Stringable) {
			return StringProxy::safe((string) $value, $this->wrapper);
		}

		throw new RuntimeException('Safe template methods must return string or Stringable values');
	}

	/** @return array<array-key, mixed> */
	private function wrapped(): array
	{
		return $this->wrapped ??= $this->wrapAll($this->context);
	}

	/**
	 * @param array<array-key, mixed> $values
	 * @return array<array-key, mixed>
	 */
	private function wrapAll(array $values): array
	{
		$wrapped = [];

		/** @var mixed $value */
		foreach ($values as $key => $value) {
			/** @psalm-suppress MixedAssignment wrapper returns mixed by design */
			$wrapped[$key] = $this->wrapper->wrap($value);
		}

		return $wrapped;
	}
}
