<?php

declare(strict_types=1);

namespace Celema\Boiler\Tests;

use Celema\Boiler\Contract;
use Celema\Boiler\Contract\Escaper;
use Celema\Boiler\Escapers;
use Celema\Boiler\Exception\UnexpectedValueException;
use Celema\Boiler\Proxy\ArrayProxy;
use Celema\Boiler\Proxy\IteratorProxy;
use Celema\Boiler\Proxy\ObjectProxy;
use Celema\Boiler\Proxy\StringProxy;
use Celema\Boiler\Wrapper;
use Traversable;

final class WrapperTest extends TestCase
{
	public function testWrapperImplementsContract(): void
	{
		$this->assertInstanceOf(Contract\Wrapper::class, new Wrapper());
	}

	public function testWrapNumber(): void
	{
		$wrapper = new Wrapper();

		$this->assertSame(13, $wrapper->wrap(13));
		$this->assertSame(1.13, $wrapper->wrap(1.13));
	}

	public function testWrapString(): void
	{
		$this->assertInstanceOf(StringProxy::class, new Wrapper()->wrap('string'));
	}

	public function testWrapArray(): void
	{
		$warray = new Wrapper()->wrap([1, 2, 3]);

		$this->assertInstanceOf(ArrayProxy::class, $warray);
		$this->assertSame(false, is_array($warray));
		$this->assertSame(true, is_array($warray->unwrap()));
		$this->assertSame(3, count($warray));
	}

	public function testWrapIterator(): void
	{
		$wrapper = new Wrapper();
		$iterator = (static function () {
			yield 1;
		})();
		$witerator = $wrapper->wrap($iterator);

		$this->assertInstanceOf(IteratorProxy::class, $witerator);
		$this->assertInstanceOf(Traversable::class, $witerator->unwrap());
		$this->assertSame(true, is_iterable($witerator->unwrap()));
	}

	public function testWrapObject(): void
	{
		$obj = new class {};

		$this->assertInstanceOf(ObjectProxy::class, new Wrapper()->wrap($obj));
	}

	public function testWrapStringable(): void
	{
		$obj = new class {
			public function __toString(): string
			{
				return '';
			}
		};

		$this->assertInstanceOf(ObjectProxy::class, new Wrapper()->wrap($obj));
	}

	public function testWrapResourcePassthrough(): void
	{
		$wrapper = new Wrapper();
		$resource = tmpfile();
		assert(is_resource($resource), 'tmpfile() must return a valid resource for this test');

		try {
			$this->assertSame($resource, $wrapper->wrap($resource));
		} finally {
			fclose($resource);
		}
	}

	public function testWrapClosedResourceThrows(): void
	{
		$this->throws(UnexpectedValueException::class, 'Unsupported template value type');

		$wrapper = new Wrapper();
		$resource = tmpfile();
		assert(is_resource($resource), 'tmpfile() must return a valid resource for this test');
		fclose($resource);

		$wrapper->wrap($resource);
	}

	public function testNestingWrapping(): void
	{
		$wrapper = new Wrapper();
		$value = $this->stringProxy('string');

		$this->assertInstanceOf(StringProxy::class, $wrapper->wrap($value));
		$this->assertSame('string', $wrapper->wrap($value)->unwrap());
		$this->assertSame(true, is_string($wrapper->wrap($value)->unwrap()));
		$this->assertInstanceOf(StringProxy::class, $wrapper->wrap($value));
	}

	public function testWrapUsesCustomEscaper(): void
	{
		$wrapper = new Wrapper(new Escapers([
			'html' => new class implements Escaper {
				public function escape(string $value): string
				{
					return strtoupper(htmlspecialchars($value));
				}
			},
		]));

		$this->assertSame('&LT;B&GT;BOILER&LT;/B&GT;', (string) $wrapper->wrap('<b>boiler</b>'));
	}

	public function testWrapCanUseCustomDefaultEscaper(): void
	{
		$wrapper = new Wrapper(
			escapers: new Escapers([
				'caps' => new class implements Escaper {
					public function escape(string $value): string
					{
						return strtoupper(htmlspecialchars($value));
					}
				},
			], default: 'caps'),
		);

		$this->assertSame('&LT;B&GT;BOILER&LT;/B&GT;', (string) $wrapper->wrap('<b>boiler</b>'));
	}

	public function testEscapeAlwaysEscapesSafeWrappedString(): void
	{
		$wrapper = new Wrapper();
		$value = $wrapper->wrap('<b>boiler</b>');
		assert($value instanceof StringProxy, 'wrap() must return a string proxy for string input');

		$this->assertSame('&lt;b&gt;boiler&lt;/b&gt;', $wrapper->escape($value->sanitize()->unwrap()));
		$this->assertSame('&lt;b&gt;boiler&lt;/b&gt;', $value->sanitize()->escape());
	}

	public function testFilterReturnsBuiltinFilter(): void
	{
		$wrapper = new Wrapper();

		$this->assertInstanceOf(Contract\Filter::class, $wrapper->filter('stripTags'));
	}

	public function testFilterApplySupportsArgs(): void
	{
		$wrapper = new Wrapper();

		$this->assertSame('<b>boiler</b>', $wrapper->filter('stripTags')->apply(
			'<b>boiler</b><script></script>',
			'<b>',
		));
	}

	public function testFilterExposesSafety(): void
	{
		$wrapper = new Wrapper();

		$this->assertTrue($wrapper->filter('sanitize')->safe());
		$this->assertFalse($wrapper->filter('stripTags')->safe());
	}

	public function testFilterRejectsUnknownFilter(): void
	{
		$this->throws(UnexpectedValueException::class, 'Unknown filter `nope`');

		new Wrapper()->filter('nope');
	}

	public function testSanitizeFilterUsesBuiltinSanitizer(): void
	{
		$this->assertSame(
			'<b>boiler</b>',
			new Wrapper()
				->filter('sanitize')
				->apply('<script></script><b>boiler</b>'),
		);
	}
}
