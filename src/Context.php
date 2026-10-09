<?php

declare(strict_types=1);

namespace Celema\Boiler;

use Celema\Boiler\Exception\LogicException;
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
	public function include(string $path, array $context = []): void
	{
		echo $this->rendering->include($this->rendering->engine->resolve($path), $this->get($context));
	}

	/**
	 * Includes another template with the output up to the matching `end()`
	 * as its slot, which it prints with `$this->slot()`.
	 *
	 * The block runs at the call site, before the included template renders.
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
			echo $this->rendering->include($file, $context, slot: $content);
		});
	}

	/**
	 * Returns what this template wraps: the page in a layout, or the block
	 * passed with `component()`.
	 *
	 * Throws when the template has no slot, such as one included with `include()`.
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
		$this->rendering->capture('section', $name, $this->rendering->sections->capture($name));
	}

	public function append(string $name): void
	{
		$this->rendering->capture('section', $name, $this->rendering->sections->append($name));
	}

	public function prepend(string $name): void
	{
		$this->rendering->capture('section', $name, $this->rendering->sections->prepend($name));
	}

	/**
	 * Captures the output up to the matching `end()` as the new content of a
	 * section, replacing what it held so far, including appended and prepended
	 * content. Inside, `yield()` returns that content, so a layout can build
	 * on what the page wrote: `<?= $this->yield('title') ?> – Blog`.
	 */
	public function rewrite(string $name): void
	{
		$this->rendering->capture('section', $name, $this->rendering->sections->rewrite($name));
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
	 * Returns the content of a section: what was prepended, the main content
	 * or else the default, and what was appended.
	 *
	 * Without a default the section is required and a missing section throws;
	 * pass a default, even `''`, when it is optional. A closure default prints
	 * its content, such as an include, and runs only when no main content was
	 * captured.
	 *
	 * @param string|Closure(): mixed|null $default
	 */
	public function yield(string $name, string|Closure|null $default = null): string
	{
		$sections = $this->rendering->sections;

		if ($sections->writing($name)) {
			throw new LogicException(
				"Section `{$name}` cannot be printed inside a block that writes it; build on its content with rewrite()",
				location: $this->rendering->location(),
			);
		}

		if ($default === null && !$sections->has($name)) {
			throw new LookupException(
				"Section `{$name}` is not defined; pass a default, even '', when it is optional",
				location: $this->rendering->location(),
			);
		}

		$content = $sections->getOr(
			$name,
			$default instanceof Closure ? fn(): string => $this->rendering->output($default) : $default ?? '',
		);

		// Like slot(), content of only whitespace counts as none, so the result
		// tells whether there is anything to print.
		return trim($content) === '' ? '' : $content;
	}
}
