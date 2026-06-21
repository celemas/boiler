<?php

declare(strict_types=1);

namespace Celemas\Boiler;

use Celemas\Boiler\Contract\Wrapper;
use Celemas\Boiler\Exception\RuntimeException;
use Celemas\Boiler\Proxy\ObjectProxy;
use Celemas\Boiler\Proxy\Proxy;
use Celemas\Boiler\Proxy\StringProxy;
use Closure;
use Stringable;

/** @api */
abstract class Context
{
	/** @var array<array-key, mixed>|null */
	private ?array $wrappedContext = null;
	protected readonly Wrapper $wrapper;
	private readonly bool $hasTrusted;

	/**
	 * @param list<class-string> $trusted
	 */
	public function __construct(
		protected readonly BaseTemplate $template,
		protected array $context,
		public readonly array $trusted,
		public readonly bool $autoescape,
	) {
		$this->wrapper = $template->engine->wrapper();
		$this->hasTrusted = $trusted !== [];
	}

	public function __call(string $name, array $args): mixed
	{
		$method = $this->template->methods()->get($name);

		/** @var array<array-key, mixed> $args */
		$args = $this->unwrap($args);

		return $this->templateValue(($method->callable)(...$args), safe: $method->safe);
	}

	public function get(array $values = []): array
	{
		if (!$this->autoescape) {
			return $values === []
				? $this->context
				: array_merge($this->context, $values);
		}

		if ($values === []) {
			return $this->wrappedContext();
		}

		return array_merge($this->wrappedContext(), $this->wrapAll($values));
	}

	/**
	 * @param array<array-key, mixed> $values
	 * @return array<array-key, mixed>
	 */
	protected function wrapAll(array $values): array
	{
		$wrapped = [];

		/** @var mixed $value */
		foreach ($values as $key => $value) {
			if ($value instanceof Proxy) {
				$wrapped[$key] = $value;

				continue;
			}

			if ($this->hasTrusted && is_object($value)) {
				foreach ($this->trusted as $trustedClass) {
					if (!$value instanceof $trustedClass) {
						continue;
					}

					$wrapped[$key] = $value;

					continue 2;
				}
			}

			/** @psalm-suppress MixedAssignment wrapper returns mixed by design */
			$wrapped[$key] = $this->wrapper->wrap($value);
		}

		return $wrapped;
	}

	public function unwrap(mixed $value): mixed
	{
		return $this->wrapper->unwrap($value);
	}

	public function add(string $key, mixed $value): mixed
	{
		$this->context[$key] = $value;
		$this->wrappedContext = null;

		return $this->templateValue($value);
	}

	public function escape(
		StringProxy|ObjectProxy|string|Stringable $value,
		?string $escaper = null,
	): string {
		if ($value instanceof StringProxy) {
			return $this->wrapper->escape($value->unwrap(), $escaper);
		}

		if ($value instanceof ObjectProxy) {
			$value = $value->unwrap();

			if (!$value instanceof Stringable) {
				throw new RuntimeException('Value cannot be escaped as string');
			}
		}

		return $this->wrapper->escape((string) $value, $escaper);
	}

	public function wrap(mixed $value): mixed
	{
		return $this->wrapper->wrap($value);
	}

	/** @return array<array-key, mixed> */
	private function wrappedContext(): array
	{
		return $this->wrappedContext ??= $this->wrapAll($this->context);
	}

	private function templateValue(mixed $value, bool $safe = false): mixed
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

	/**
	 * @param non-empty-string $path
	 */
	public function layout(string $path, array $context = []): void
	{
		$this->template->setLayout(new LayoutSpec($path, $this->location(), $context));
	}

	/**
	 * Includes another template into the current template.
	 *
	 * If no context is passed it shares the context of the calling template.
	 *
	 * The optional slot is a block of markup the inserted template can place,
	 * and repeat, by calling `$this->slot([...])`. A closure receives the per-call
	 * data as its argument and either echoes or returns markup. A `Slot::template()`
	 * slot renders another template with the per-call data merged into its context.
	 * Slot values are raw, so escape them like any other template data.
	 *
	 * @param non-empty-string $path
	 */
	public function insert(string $path, array $context = [], Closure|Slot|null $slot = null): void
	{
		$path = $this->template->engine->resolve($path);
		$template = new Template(
			$path,
			sections: $this->template->sections,
			engine: $this->template->engine,
		);

		$template->setMethods($this->template->methods());
		$template->setSlot($slot, $this, $this->location());

		echo
			$this->autoescape
				? $template->renderEscaped($this->get($context), $this->trusted)
				: $template->renderUnescaped($this->get($context), $this->trusted)
		;
	}

	/**
	 * Renders the slot passed to this template via `insert(..., slot: ...)`.
	 *
	 * Call it once for a simple slot, or once per row to repeat the block with
	 * different data. Throws when the template was inserted without a slot;
	 * guard with `hasSlot()` when a slot is optional.
	 *
	 * @param array<array-key, mixed> $data
	 */
	public function slot(array $data = []): void
	{
		echo
			($this->template->slot() ?? throw new RuntimeException(
				'No slot was provided for this template',
				location: $this->location(),
			))->render($data)
		;
	}

	public function hasSlot(): bool
	{
		return $this->template->slot() !== null;
	}

	public function begin(string $name): void
	{
		$this->template->sections->begin($name, $this->location());
	}

	public function append(string $name): void
	{
		$this->template->sections->append($name, $this->location());
	}

	public function prepend(string $name): void
	{
		$this->template->sections->prepend($name, $this->location());
	}

	public function end(): void
	{
		$this->template->sections->end();
	}

	public function section(string $name, string $default = ''): string
	{
		if (func_num_args() > 1) {
			return $this->template->sections->getOr($name, $default);
		}

		return $this->template->sections->get($name);
	}

	public function has(string $name): bool
	{
		return $this->template->sections->has($name);
	}

	private function location(): Location
	{
		return Location::fromBacktrace($this->template->path);
	}
}
