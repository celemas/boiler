<?php

declare(strict_types=1);

namespace Celema\Boiler\Tests;

use Celema\Boiler\Contract;
use Celema\Boiler\Escapers;
use Celema\Boiler\Exception\UnexpectedValueException;

final class EscapersTest extends TestCase
{
	public function testEscapesHtmlWithBuiltins(): void
	{
		$escapers = new Escapers();

		$this->assertSame(
			'&lt;b&gt;&quot;Boiler&quot; &amp; more&lt;/b&gt;',
			$escapers->get('html')->escape('<b>"Boiler" & more</b>'),
		);
	}

	public function testEscapersImplementsContract(): void
	{
		$this->assertInstanceOf(Contract\Escapers::class, new Escapers());
	}

	public function testDefaultsToBuiltinHtmlEscaper(): void
	{
		$this->assertSame('html', new Escapers()->default);
	}

	public function testCanRegisterAdditionalEscaper(): void
	{
		$escapers = new Escapers();
		$escapers->register('caps', new class implements Contract\Escaper {
			public function escape(string $value): string
			{
				return strtoupper(htmlspecialchars($value));
			}
		});

		$this->assertSame('&LT;B&GT;BOILER&LT;/B&GT;', $escapers->get('caps')->escape('<b>boiler</b>'));
	}

	public function testConstructorEscapersCanOverrideBuiltins(): void
	{
		$escapers = new Escapers([
			'html' => new class implements Contract\Escaper {
				public function escape(string $value): string
				{
					return strtoupper($value);
				}
			},
		]);

		$this->assertSame('<B>BOILER</B>', $escapers->get('html')->escape('<b>boiler</b>'));
	}

	public function testConstructorCanSetCustomDefaultEscaper(): void
	{
		$escapers = new Escapers([
			'caps' => new class implements Contract\Escaper {
				public function escape(string $value): string
				{
					return strtoupper(htmlspecialchars($value));
				}
			},
		], default: 'caps');

		$this->assertSame('caps', $escapers->default);
	}

	public function testRejectsUnknownEscaper(): void
	{
		$this->throws(UnexpectedValueException::class, 'Unknown escaper `xml`');

		new Escapers()->get('xml');
	}

	public function testRejectsUnknownDefaultEscaper(): void
	{
		$this->throws(UnexpectedValueException::class, 'Unknown escaper `xml`');

		new Escapers(default: 'xml');
	}

	public function testRejectsEmptyEscaperNameOnRegister(): void
	{
		$this->throws(UnexpectedValueException::class, 'Escaper name must be a non-empty string');

		$escapers = new Escapers();
		$escapers->register('', new class implements Contract\Escaper {
			public function escape(string $value): string
			{
				return $value;
			}
		});
	}

	public function testRejectsNonStringEscaperNameInConstructor(): void
	{
		$this->throws(UnexpectedValueException::class, 'Escaper name must be a non-empty string');

		new Escapers([
			13 => new class implements Contract\Escaper {
				public function escape(string $value): string
				{
					return $value;
				}
			},
		]);
	}

	public function testRejectsEscaperWithoutContractInConstructor(): void
	{
		$this->throws(UnexpectedValueException::class, 'must implement');

		new Escapers([
			'bad' => new class {},
		]);
	}
}
