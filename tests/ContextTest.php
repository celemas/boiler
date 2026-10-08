<?php

declare(strict_types=1);

namespace Celema\Boiler\Tests;

use Celema\Boiler\Context;
use Celema\Boiler\Contract\Escaper;
use Celema\Boiler\Engine;
use Celema\Boiler\Exception\RuntimeException;
use Celema\Boiler\Methods;
use Celema\Boiler\Proxy\StringProxy;
use Celema\Boiler\Rendering;
use Celema\Boiler\Sections;

final class ContextTest extends TestCase
{
	private string $templates;

	protected function setUp(): void
	{
		$this->templates = __DIR__ . '/templates/default/';
	}

	public function testGetContext(): void
	{
		$tmplContext = $this->context([
			'value1' => 'Value 1',
			'value2' => '<i>Value 2</i>',
			'value3' => 3,
		]);
		$context = $tmplContext->get();

		$this->assertInstanceOf(StringProxy::class, $context['value1']);
		$this->assertSame('Value 1', (string) $context['value1']);
		$this->assertInstanceOf(StringProxy::class, $context['value2']);
		$this->assertSame('&lt;i&gt;Value 2&lt;/i&gt;', (string) $context['value2']);
		$this->assertSame(3, $context['value3']);
	}

	public function testTrustedObjectMatchesLaterTrustedEntry(): void
	{
		$value = new TrustedValue();
		$tmplContext = $this->context(['value' => $value], [\stdClass::class, TrustedBase::class]);
		$context = $tmplContext->get();

		$this->assertSame($value, $context['value']);
	}

	public function testAddingToContext(): void
	{
		$tmplContext = $this->context(['value1' => 'Value 1']);
		$value2 = $tmplContext->add('value2', '<i>Value 2</i>');
		$context = $tmplContext->get();

		$this->assertInstanceOf(StringProxy::class, $context['value1']);
		$this->assertSame('Value 1', (string) $context['value1']);
		$this->assertInstanceOf(StringProxy::class, $context['value2']);
		$this->assertSame('&lt;i&gt;Value 2&lt;/i&gt;', (string) $context['value2']);
		$this->assertInstanceOf(StringProxy::class, $value2);
		$this->assertSame('&lt;i&gt;Value 2&lt;/i&gt;', (string) $value2);
	}

	public function testAddingToEscapedContextInvalidatesCachedContext(): void
	{
		$tmplContext = $this->context(['value1' => 'Value 1']);
		$tmplContext->get();
		$tmplContext->add('value2', '<i>Value 2</i>');
		$context = $tmplContext->get();

		$this->assertInstanceOf(StringProxy::class, $context['value2']);
		$this->assertSame('&lt;i&gt;Value 2&lt;/i&gt;', (string) $context['value2']);
	}

	public function testAddingToUnescapedContextReturnsRawValue(): void
	{
		$tmplContext = $this->context(autoescape: false);
		$value = $tmplContext->add('value', '<i>Value</i>');
		$context = $tmplContext->get();

		$this->assertSame('<i>Value</i>', $context['value']);
		$this->assertSame('<i>Value</i>', $value);
	}

	public function testEscapedContextLeavesResourcesRaw(): void
	{
		$resource = tmpfile();
		assert(is_resource($resource), 'tmpfile() must return a valid resource for this test');

		try {
			$tmplContext = $this->context(['value' => $resource]);
			$context = $tmplContext->get();

			$this->assertSame($resource, $context['value']);
		} finally {
			fclose($resource);
		}
	}

	public function testEscapesStringableObjects(): void
	{
		$tmplContext = $this->context();
		$value = new class {
			public function __toString(): string
			{
				return '<b>Value</b>';
			}
		};

		$this->assertSame('&lt;b&gt;Value&lt;/b&gt;', $tmplContext->escape($value));
	}

	public function testEscapeAlwaysEscapesSafeStringProxy(): void
	{
		$tmplContext = $this->context();
		$value = $tmplContext->wrap('<b>Value</b>');
		assert($value instanceof StringProxy, 'wrap() must return a string proxy for string input');

		$this->assertSame('&lt;b&gt;Value&lt;/b&gt;', $tmplContext->escape($value->sanitize()));
	}

	public function testEscapeCanUseExplicitEscaperForStringProxy(): void
	{
		$engine = Engine::create($this->templates)
			->escape('caps', new class implements Escaper {
				public function escape(string $value): string
				{
					return strtoupper(htmlspecialchars($value));
				}
			});
		$tmplContext = $this->context(engine: $engine);
		$value = $tmplContext->wrap('<b>tag</b>');
		assert($value instanceof StringProxy, 'wrap() must return a string proxy for string input');

		$this->assertSame('&LT;B&GT;TAG&LT;/B&GT;', $tmplContext->escape($value->sanitize(), 'caps'));
	}

	public function testEscapeRejectsNonStringableWrappedObjects(): void
	{
		$this->throws(RuntimeException::class, 'cannot be escaped as string');

		$tmplContext = $this->context();
		$tmplContext->escape($this->objectProxy(new class {}));
	}

	public function testWrapReturnsWrappedValueInUnescapedContext(): void
	{
		$tmplContext = $this->context(autoescape: false);
		$wrapped = $tmplContext->wrap('<b>Value</b>');

		$this->assertInstanceOf(StringProxy::class, $wrapped);
		$this->assertSame('&lt;b&gt;Value&lt;/b&gt;', (string) $wrapped);
	}

	/** @param list<class-string> $trusted */
	private function context(
		array $values = [],
		array $trusted = [],
		bool $autoescape = true,
		?Engine $engine = null,
	): Context {
		$rendering = new Rendering(
			$this->templates . 'simple.php',
			$engine ?? Engine::create($this->templates),
			new Methods(),
			new Sections(),
			$trusted,
			$autoescape,
		);

		return new Context($rendering, $values);
	}
}
