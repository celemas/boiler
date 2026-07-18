<?php

declare(strict_types=1);

namespace Celema\Boiler\Tests;

use Celema\Boiler\Contract;
use Celema\Boiler\Contract\Escaper;
use Celema\Boiler\Exception\UnexpectedValueException;

final class StringProxyTest extends TestCase
{
	public function testProxyUnwrap(): void
	{
		$this->assertSame('<b>boiler</b>', $this->stringProxy('<b>boiler</b>')->unwrap());
	}

	public function testStringValue(): void
	{
		$html = '<b onclick="func()">boiler</b>';
		$value = $this->stringProxy($html);

		$this->assertSame('&lt;b onclick=&quot;func()&quot;&gt;boiler&lt;/b&gt;', (string) $value);
	}

	public function testStripTagsFilter(): void
	{
		$proxy = $this->stringProxy('<b>boiler<br>plate</b>');

		$this->assertSame('boilerplate', (string) $proxy->stripTags());
	}

	public function testStripTagsFilterWithAllowedTags(): void
	{
		$proxy = $this->stringProxy('<b>boiler<br>plate</b>');

		$this->assertSame('boiler&lt;br&gt;plate', (string) $proxy->stripTags('<br>'));
	}

	public function testStripTagsReturnsStringProxy(): void
	{
		$proxy = $this->stringProxy('<b>boiler</b>');

		$this->assertInstanceOf($proxy::class, $proxy->stripTags());
	}

	public function testSanitizeFilter(): void
	{
		$proxy = $this->stringProxy('<script></script><b>boiler</b>');

		$this->assertSame('<b>boiler</b>', (string) $proxy->sanitize());
	}

	public function testSanitizeIsSafe(): void
	{
		$proxy = $this->stringProxy('<b>boiler</b>');
		$sanitized = $proxy->sanitize();

		// safe filter output is not auto-escaped
		$this->assertSame('<b>boiler</b>', (string) $sanitized);
	}

	public function testStripTagsIsNotSafe(): void
	{
		$proxy = $this->stringProxy('<b>boiler</b>');
		$stripped = $proxy->stripTags();

		// stripTags is not safe, so output is auto-escaped
		$this->assertSame('boiler', (string) $stripped);
	}

	public function testBuiltinFilterCanPreserveSafeOutput(): void
	{
		$proxy = $this->stringProxy('<b>boiler</b>');
		$result = $proxy->sanitize()->upper();

		$this->assertSame('<B>BOILER</B>', (string) $result);
	}

	public function testUnsafeFilterBreaksSafeChainWithoutPreserveOptIn(): void
	{
		$proxy = $this->stringProxy(
			'<b>boiler</b>',
			new \Celema\Boiler\Filters([
				'append' => new class implements Contract\Filter {
					public function apply(string $value, mixed ...$args): string
					{
						return $value . (string) ($args[0] ?? '');
					}

					public function safe(): bool
					{
						return false;
					}
				},
			]),
		);

		$result = $proxy->sanitize()->append('<script>alert(1)</script>');

		$this->assertSame(
			'&lt;b&gt;boiler&lt;/b&gt;&lt;script&gt;alert(1)&lt;/script&gt;',
			(string) $result,
		);
	}

	public function testCustomFilterCanPreserveSafeOutputWithOptIn(): void
	{
		$proxy = $this->stringProxy(
			'<b>boiler</b>',
			new \Celema\Boiler\Filters([
				'append' => new class implements Contract\Filter, Contract\PreservesSafety {
					public function apply(string $value, mixed ...$args): string
					{
						return $value . (string) ($args[0] ?? '');
					}

					public function safe(): bool
					{
						return false;
					}
				},
			]),
		);

		$result = $proxy->sanitize()->append('<i>next</i>');

		$this->assertSame('<b>boiler</b><i>next</i>', (string) $result);
	}

	public function testEscapeReturnsDefaultEscapedValue(): void
	{
		$proxy = $this->stringProxy('<b>boiler</b>');

		$this->assertSame('&lt;b&gt;boiler&lt;/b&gt;', $proxy->escape());
	}

	public function testEscapeCanUseNamedEscaper(): void
	{
		$proxy = $this->escapedStringProxy(
			'<b>boiler</b>',
			[
				'caps' => new class implements Escaper {
					public function escape(string $value): string
					{
						return strtoupper(htmlspecialchars($value));
					}
				},
			],
		);

		$this->assertSame('&LT;B&GT;BOILER&LT;/B&GT;', $proxy->escape('caps'));
	}

	public function testExplicitEscapeIgnoresSafeFlag(): void
	{
		$proxy = $this->escapedStringProxy(
			'<b>boiler</b>',
			[
				'caps' => new class implements Escaper {
					public function escape(string $value): string
					{
						return strtoupper(htmlspecialchars($value));
					}
				},
			],
		);

		$this->assertSame('&lt;b&gt;boiler&lt;/b&gt;', $proxy->sanitize()->escape());
		$this->assertSame('&LT;B&GT;BOILER&LT;/B&GT;', $proxy->sanitize()->escape('caps'));
	}

	public function testUnsafeChainStaysUnsafe(): void
	{
		$proxy = $this->stringProxy('<b>boiler</b>');

		// stripTags is unsafe, chain remains unsafe, output gets escaped
		$stripped = $proxy->stripTags();
		$this->assertSame('boiler', (string) $stripped);
	}

	public function testUnknownFilterThrows(): void
	{
		$this->throws(UnexpectedValueException::class, 'Unknown filter `nope`');

		$this->stringProxy('test')->nope();
	}

	public function testFilterUnwrapReturnsFilteredValue(): void
	{
		$proxy = $this->stringProxy('<script></script><b>boiler</b>');
		$sanitized = $proxy->sanitize();

		$this->assertSame('<b>boiler</b>', $sanitized->unwrap());
	}
}
