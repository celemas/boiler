<?php

declare(strict_types=1);

namespace Celema\Boiler\Tests;

use Celema\Boiler\Contract\Escaper;
use Celema\Boiler\Engine;
use Celema\Boiler\Exception\RuntimeException;
use Celema\Boiler\Proxy\StringProxy;
use Celema\Boiler\Template;
use Celema\Boiler\TemplateContext;

final class TemplateContextTest extends TestCase
{
	private string $templates;
	private Template $template;

	protected function setUp(): void
	{
		$this->templates = __DIR__ . '/templates/default/';
		$path = $this->templates . 'simple.php';
		$this->template = new Template($path);
	}

	public function testGetContext(): void
	{
		$tmplContext = new TemplateContext(
			$this->template,
			[
				'value1' => 'Value 1',
				'value2' => '<i>Value 2</i>',
				'value3' => 3,
			],
			[],
			true,
		);
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
		$tmplContext = new TemplateContext(
			$this->template,
			['value' => $value],
			[\stdClass::class, TrustedBase::class],
			true,
		);
		$context = $tmplContext->get();

		$this->assertSame($value, $context['value']);
	}

	public function testAddingToContext(): void
	{
		$tmplContext = new TemplateContext($this->template, ['value1' => 'Value 1'], [], true);
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
		$tmplContext = new TemplateContext($this->template, ['value1' => 'Value 1'], [], true);
		$tmplContext->get();
		$tmplContext->add('value2', '<i>Value 2</i>');
		$context = $tmplContext->get();

		$this->assertInstanceOf(StringProxy::class, $context['value2']);
		$this->assertSame('&lt;i&gt;Value 2&lt;/i&gt;', (string) $context['value2']);
	}

	public function testAddingToUnescapedContextReturnsRawValue(): void
	{
		$tmplContext = new TemplateContext($this->template, [], [], false);
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
			$tmplContext = new TemplateContext($this->template, ['value' => $resource], [], true);
			$context = $tmplContext->get();

			$this->assertSame($resource, $context['value']);
		} finally {
			fclose($resource);
		}
	}

	public function testEscapesStringableObjects(): void
	{
		$tmplContext = new TemplateContext($this->template, [], [], true);
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
		$tmplContext = new TemplateContext($this->template, [], [], true);
		$value = $tmplContext->wrap('<b>Value</b>');
		assert($value instanceof StringProxy, 'wrap() must return a string proxy for string input');

		$this->assertSame('&lt;b&gt;Value&lt;/b&gt;', $tmplContext->escape($value->sanitize()));
	}

	public function testEscapeCanUseExplicitEscaperForStringProxy(): void
	{
		$template = new Template(
			$this->templates . 'simple.php',
			engine: Engine::create($this->templates)
				->escape('caps', new class implements Escaper {
					public function escape(string $value): string
					{
						return strtoupper(htmlspecialchars($value));
					}
				}),
		);
		$tmplContext = new TemplateContext($template, [], [], true);
		$value = $tmplContext->wrap('<b>tag</b>');
		assert($value instanceof StringProxy, 'wrap() must return a string proxy for string input');

		$this->assertSame('&LT;B&GT;TAG&LT;/B&GT;', $tmplContext->escape($value->sanitize(), 'caps'));
	}

	public function testEscapeRejectsNonStringableWrappedObjects(): void
	{
		$this->throws(RuntimeException::class, 'cannot be escaped as string');

		$tmplContext = new TemplateContext($this->template, [], [], true);
		$tmplContext->escape($this->objectProxy(new class {}));
	}

	public function testWrapReturnsWrappedValueInUnescapedContext(): void
	{
		$tmplContext = new TemplateContext($this->template, [], [], false);
		$wrapped = $tmplContext->wrap('<b>Value</b>');

		$this->assertInstanceOf(StringProxy::class, $wrapped);
		$this->assertSame('&lt;b&gt;Value&lt;/b&gt;', (string) $wrapped);
	}
}
