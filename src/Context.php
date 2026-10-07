<?php

declare(strict_types=1);

namespace Celema\Boiler;

use Celema\Boiler\Contract\Wrapper;
use Celema\Boiler\Exception\LookupException;
use Celema\Boiler\Exception\RuntimeException;
use Celema\Boiler\Proxy\ObjectProxy;
use Celema\Boiler\Proxy\StringProxy;
use Closure;
use Stringable;

/** @api */
final class Context
{
	/** @var array<array-key, mixed>|null */
	private ?array $wrappedContext = null;

	private readonly Wrapper $wrapper;

	/**
	 * @param list<class-string> $trusted
	 */
	public function __construct(
		private readonly Template $template,
		private array $context,
		public readonly array $trusted,
		public readonly bool $autoescape,
	) {
		$this->wrapper = $template->engine->wrapper()->withTrusted($trusted);
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
		// Explicit wrapping bypasses the trusted list.
		// Deliberately not `$this->wrapper`: that one honors trust, which would
		// turn this call into a no-op for an instance of a trusted class and
		// leave the template no way to get a proxy at all.
		return $this->template->engine->wrapper()->wrap($value);
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
	 * @param non-empty-string $path
	 */
	public function insert(string $path, array $context = []): void
	{
		echo $this->renderInserted($this->inserted($path), $this->get($context));
	}

	/**
	 * Includes another template with the output up to the matching `end()`
	 * as its slot, which it prints with `$this->slot()`.
	 *
	 * The block runs at the call site, before the inserted template renders.
	 * If no context is passed it shares the context of the calling template.
	 *
	 * @param non-empty-string $path
	 */
	public function component(string $path, array $context = []): void
	{
		$template = $this->inserted($path);
		$context = $this->get($context);

		$this->template->blocks->open(
			'component',
			$path,
			$this->location(),
			function (string $content) use ($template, $context): void {
				$template->setSlot($content);

				echo $this->renderInserted($template, $context);
			},
		);
	}

	/**
	 * Returns what this template wraps: the page in a layout, or the block
	 * passed with `component()`.
	 *
	 * Throws when the template has no slot, such as one inserted with `insert()`.
	 */
	public function slot(): string
	{
		$slot = $this->template->slot() ?? throw new RuntimeException(
			'No slot was provided for this template',
			location: $this->location(),
		);

		return $this->hasSlot() ? $slot : '';
	}

	/**
	 * Whether there is a slot to print. Content of only whitespace counts as
	 * none, and `slot()` returns `''` for it.
	 */
	public function hasSlot(): bool
	{
		return trim($this->template->slot() ?? '') !== '';
	}

	/**
	 * Captures the output up to the matching `end()` as the content of a
	 * section, which `yield()` prints.
	 */
	public function section(string $name): void
	{
		$this->openSection($name, $this->template->sections->assign(...));
	}

	public function append(string $name): void
	{
		$this->openSection($name, $this->template->sections->append(...));
	}

	public function prepend(string $name): void
	{
		$this->openSection($name, $this->template->sections->prepend(...));
	}

	/**
	 * Closes the innermost open section or component.
	 *
	 * With a name, the render fails unless that is the section or component
	 * being closed, as passed to `section()` or `component()`.
	 */
	public function end(?string $name = null): void
	{
		$this->template->blocks->close($name);
	}

	/**
	 * Returns the captured content of a section.
	 *
	 * Without a default the section is required and a missing section throws;
	 * pass a default, even `''`, or guard with `hasSection()` when it is optional.
	 */
	public function yield(string $name, ?string $default = null): string
	{
		if ($default !== null) {
			return $this->template->sections->getOr($name, $default);
		}

		if (!$this->template->sections->has($name)) {
			throw new LookupException(
				"Section `{$name}` is not defined; pass a default or check it with hasSection()",
				location: $this->location(),
			);
		}

		return $this->template->sections->get($name);
	}

	public function hasSection(string $name): bool
	{
		return $this->template->sections->has($name);
	}

	private function location(): Location
	{
		return Location::fromBacktrace($this->template->path);
	}

	/** @param Closure(string, string): void $store */
	private function openSection(string $name, Closure $store): void
	{
		$this->template->blocks->open(
			'section',
			$name,
			$this->location(),
			static fn(string $content) => $store($name, $content),
		);
	}

	/** @param non-empty-string $path */
	private function inserted(string $path): Template
	{
		$template = new Template(
			$this->template->engine->resolve($path),
			sections: $this->template->sections,
			engine: $this->template->engine,
		);
		$template->setMethods($this->template->methods());

		return $template;
	}

	private function renderInserted(Template $template, array $context): string
	{
		return $this->autoescape
			? $template->renderEscaped($context, $this->trusted)
			: $template->renderUnescaped($context, $this->trusted);
	}
}
