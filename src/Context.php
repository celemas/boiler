<?php

declare(strict_types=1);

namespace Celema\Boiler;

use Celema\Boiler\Exception\LookupException;
use Celema\Boiler\Exception\RuntimeException;
use Celema\Boiler\Proxy\ObjectProxy;
use Celema\Boiler\Proxy\StringProxy;
use Closure;
use Stringable;

/**
 * The `$this` of template code: the template helpers and the registered
 * template methods.
 *
 * Templates run in the scope of this object, so a method of any visibility
 * here would shadow a registered template method of the same name. Keep
 * helper code in Rendering and Values instead of private methods.
 *
 * @api
 */
final class Context
{
	/** @var list<class-string> */
	public readonly array $trusted;
	public readonly bool $autoescape;
	private readonly Values $values;

	/** @internal Templates receive their context; they never create one. */
	public function __construct(
		private readonly Rendering $rendering,
		array $context,
	) {
		$this->trusted = $rendering->trusted;
		$this->autoescape = $rendering->autoescape;
		$this->values = new Values(
			$context,
			$rendering->engine->wrapper()->withTrusted($rendering->trusted),
			$rendering->autoescape,
		);
	}

	public function __call(string $name, array $args): mixed
	{
		$method = $this->rendering->methods->get($name);

		/** @var array<array-key, mixed> $args */
		$args = $this->unwrap($args);

		return $this->values->output(($method->callable)(...$args), safe: $method->safe);
	}

	public function get(array $values = []): array
	{
		return $this->values->get($values);
	}

	public function unwrap(mixed $value): mixed
	{
		return $this->values->wrapper->unwrap($value);
	}

	public function add(string $key, mixed $value): mixed
	{
		return $this->values->add($key, $value);
	}

	public function escape(
		StringProxy|ObjectProxy|string|Stringable $value,
		?string $escaper = null,
	): string {
		if ($value instanceof StringProxy) {
			return $this->values->wrapper->escape($value->unwrap(), $escaper);
		}

		if ($value instanceof ObjectProxy) {
			$value = $value->unwrap();

			if (!$value instanceof Stringable) {
				throw new RuntimeException('Value cannot be escaped as string');
			}
		}

		return $this->values->wrapper->escape((string) $value, $escaper);
	}

	public function wrap(mixed $value): mixed
	{
		// Explicit wrapping bypasses the trusted list. Deliberately not the
		// wrapper of the values: that one honors trust, which would turn this
		// call into a no-op for an instance of a trusted class and leave the
		// template no way to get a proxy at all.
		return $this->rendering->engine->wrapper()->wrap($value);
	}

	/**
	 * @param non-empty-string $path
	 */
	public function layout(string $path, array $context = []): void
	{
		$this->rendering->setLayout(new LayoutSpec($path, $this->rendering->location(), $context));
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
		echo $this->rendering->insert($this->rendering->engine->resolve($path), $this->get($context));
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
		// Resolved before the block runs, so a missing template fails at this call.
		$file = $this->rendering->engine->resolve($path);
		$context = $this->get($context);

		$this->rendering->capture('component', $path, function (string $content) use ($file, $context): void {
			echo $this->rendering->insert($file, $context, slot: $content);
		});
	}

	/**
	 * Returns what this template wraps: the page in a layout, or the block
	 * passed with `component()`.
	 *
	 * Throws when the template has no slot, such as one inserted with `insert()`.
	 */
	public function slot(): string
	{
		$slot = $this->rendering->slot ?? throw new RuntimeException(
			'No slot was provided for this template',
			location: $this->rendering->location(),
		);

		return $this->hasSlot() ? $slot : '';
	}

	/**
	 * Whether there is a slot to print. Content of only whitespace counts as
	 * none, and `slot()` returns `''` for it.
	 */
	public function hasSlot(): bool
	{
		return trim($this->rendering->slot ?? '') !== '';
	}

	/**
	 * Captures the output up to the matching `end()` as the content of a
	 * section, which `yield()` prints.
	 *
	 * In a layout, the capture is a default for the templates it wraps. Any
	 * other second capture fails the render at `end()`.
	 */
	public function section(string $name): void
	{
		$this->rendering->capture('section', $name, $this->rendering->sections->open($name));
	}

	public function append(string $name): void
	{
		$sections = $this->rendering->sections;

		$this->rendering->capture('section', $name, static fn(string $content) => $sections->append($name, $content));
	}

	public function prepend(string $name): void
	{
		$sections = $this->rendering->sections;

		$this->rendering->capture('section', $name, static fn(string $content) => $sections->prepend($name, $content));
	}

	/**
	 * Closes the innermost open section or component.
	 *
	 * With a name, the render fails unless that is the section or component
	 * being closed, as passed to `section()` or `component()`.
	 */
	public function end(?string $name = null): void
	{
		$this->rendering->blocks->close($name);
	}

	/**
	 * Returns the captured content of a section.
	 *
	 * Without a default the section is required and a missing section throws;
	 * pass a default, even `''`, or guard with `hasSection()` when it is optional.
	 * A closure default prints its content, such as an insert, and runs only
	 * when no main content was captured.
	 *
	 * @param string|Closure(): mixed|null $default
	 */
	public function yield(string $name, string|Closure|null $default = null): string
	{
		if ($default instanceof Closure) {
			return $this->rendering->sections->getOr($name, fn(): string => $this->rendering->output($default));
		}

		if ($default !== null) {
			return $this->rendering->sections->getOr($name, $default);
		}

		if (!$this->rendering->sections->has($name)) {
			throw new LookupException(
				"Section `{$name}` is not defined; pass a default or check it with hasSection()",
				location: $this->rendering->location(),
			);
		}

		return $this->rendering->sections->get($name);
	}

	public function hasSection(string $name): bool
	{
		return $this->rendering->sections->has($name);
	}
}
