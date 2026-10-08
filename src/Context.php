<?php

declare(strict_types=1);

namespace Celema\Boiler;

use Celema\Boiler\Exception\LookupException;
use Celema\Boiler\Exception\RuntimeException;
use Celema\Boiler\Proxy\ObjectProxy;
use Celema\Boiler\Proxy\StringProxy;
use Stringable;

/**
 * The `$this` of template code: the template helpers and the registered
 * template methods.
 *
 * Templates run in the scope of this object, so a method of any visibility
 * here would shadow a registered template method of the same name. Keep
 * helper code in Template and Values instead of private methods.
 *
 * @api
 */
final class Context
{
	private readonly Values $values;

	/**
	 * @param list<class-string> $trusted
	 */
	public function __construct(
		private readonly Template $template,
		array $context,
		public readonly array $trusted,
		public readonly bool $autoescape,
	) {
		$this->values = new Values($context, $template->engine->wrapper()->withTrusted($trusted), $autoescape);
	}

	public function __call(string $name, array $args): mixed
	{
		$method = $this->template->methods()->get($name);

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
		return $this->template->engine->wrapper()->wrap($value);
	}

	/**
	 * @param non-empty-string $path
	 */
	public function layout(string $path, array $context = []): void
	{
		$this->template->setLayout(new LayoutSpec($path, $this->template->location(), $context));
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
		echo $this->template->partial($path)->renderPartial($this->get($context), $this->trusted, $this->autoescape);
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
		$template = $this->template->partial($path);
		$context = $this->get($context);

		$this->template->capture('component', $path, function (string $content) use ($template, $context): void {
			$template->setSlot($content);

			echo $template->renderPartial($context, $this->trusted, $this->autoescape);
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
		$slot = $this->template->slot() ?? throw new RuntimeException(
			'No slot was provided for this template',
			location: $this->template->location(),
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
		$sections = $this->template->sections;

		$this->template->capture('section', $name, static fn(string $content) => $sections->assign($name, $content));
	}

	public function append(string $name): void
	{
		$sections = $this->template->sections;

		$this->template->capture('section', $name, static fn(string $content) => $sections->append($name, $content));
	}

	public function prepend(string $name): void
	{
		$sections = $this->template->sections;

		$this->template->capture('section', $name, static fn(string $content) => $sections->prepend($name, $content));
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
				location: $this->template->location(),
			);
		}

		return $this->template->sections->get($name);
	}

	public function hasSection(string $name): bool
	{
		return $this->template->sections->has($name);
	}
}
