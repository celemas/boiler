<?php

declare(strict_types=1);

namespace Celema\Boiler\Tests;

use Celema\Boiler\Exception\RuntimeException;
use Celema\Boiler\Exception\UnexpectedValueException;
use Celema\Boiler\Proxy\StringProxy;
use PHPUnit\Framework\Attributes\TestDox;
use ValueError;

final class ObjectProxyTest extends TestCase
{
	public function testProxyUnwrap(): void
	{
		$object = new class {};
		$value = $this->objectProxy($object);

		$this->assertSame($object, $value->unwrap());
	}

	public function testIsComparesObjectIdentity(): void
	{
		$object = new class {};
		$proxy = $this->objectProxy($object);

		$this->assertTrue($proxy->is($object));
		$this->assertFalse($proxy->is(new \stdClass()));
		$this->assertTrue($proxy->is($this->objectProxy($object)));
	}

	public function testIsComparesEnumCases(): void
	{
		$proxy = $this->objectProxy(Status::Active);

		$this->assertTrue($proxy->is(Status::Active));
		$this->assertFalse($proxy->is(Status::Inactive));
	}

	public function testInComparesEnumCases(): void
	{
		$proxy = $this->objectProxy(Status::Active);

		$this->assertTrue($proxy->in([Status::Inactive, Status::Active]));
		$this->assertTrue($proxy->in($this->arrayProxy([Status::Active])));
		$this->assertFalse($proxy->in([Status::Inactive]));
	}

	public function testTraversableObjectsMustUseIteratorProxy(): void
	{
		$this->throws(UnexpectedValueException::class, 'iterator proxies');

		$this->objectProxy(new \ArrayIterator([]));
	}

	public function testStringableValue(): void
	{
		$stringable = new class {
			public string $value = 'test';

			public function __toString(): string
			{
				return '<b>boiler</b>';
			}

			public function testMethod(): string
			{
				return $this->value . $this->value;
			}
		};
		$value = $this->objectProxy($stringable);

		$this->assertSame('&lt;b&gt;boiler&lt;/b&gt;', (string) $value);
		$this->assertSame($stringable, $value->unwrap());
		$this->assertInstanceOf(StringProxy::class, $value->value);
		$this->assertSame('test', (string) $value->value);
		$value->value = 'boiler';
		$this->assertSame('boiler', (string) $value->value);
		$this->assertInstanceOf(StringProxy::class, $value->testMethod());
		$this->assertSame('boilerboiler', (string) $value->testMethod());
	}

	public function testObjectValid(): void
	{
		$object = new class {
			public function __invoke(string $str): string
			{
				return '<i>' . $str . '</i>';
			}

			public function html(): string
			{
				return '<b>boiler</b><script></script>';
			}
		};
		$value = $this->objectProxy($object);

		$this->assertSame(
			'&lt;b&gt;boiler&lt;/b&gt;&lt;script&gt;&lt;/script&gt;',
			(string) $value->html(),
		);
		$this->assertSame('<b>boiler</b>', (string) $value->html()->sanitize());
		$this->assertSame('&lt;i&gt;test&lt;/i&gt;', (string) $value('test'));
	}

	public function testMethodCallUnwrapsProxyArguments(): void
	{
		$object = new class {
			public function upper(string $value): string
			{
				return strtoupper($value);
			}
		};
		$value = $this->objectProxy($object);

		$this->assertSame('BOILER', (string) $value->upper($this->stringProxy('boiler')));
	}

	public function testInvokeUnwrapsProxyArguments(): void
	{
		$closure = static fn(string $value): string => strtoupper($value);
		$value = $this->objectProxy($closure);

		$this->assertSame('BOILER', (string) $value($this->stringProxy('boiler')));
	}

	public function testSetterUnwrapsProxyValue(): void
	{
		$object = new class {
			public string $value = 'initial';
		};
		$value = $this->objectProxy($object);

		$value->value = $this->stringProxy('boiler');

		$this->assertSame('boiler', $object->value);
	}

	public function testNonStringableObjectThrowsOnStringCast(): void
	{
		$this->throws(RuntimeException::class, 'not stringable');

		$value = $this->objectProxy(new class {});
		(string) $value;
	}

	public function testObjectNotInvokable(): void
	{
		$this->throws(RuntimeException::class, 'No such method');

		$object = new class {};
		$value = $this->objectProxy($object);

		$value();
	}

	public function testClosureValue(): void
	{
		$closure = static fn(): string => '<b>boiler</b><script></script>';
		$value = $this->objectProxy($closure);

		$this->assertSame('&lt;b&gt;boiler&lt;/b&gt;&lt;script&gt;&lt;/script&gt;', (string) $value());
		$this->assertSame('<b>boiler</b>', (string) $value()->sanitize());
	}

	public function testSanitizeUsesBuiltinSanitizer(): void
	{
		$object = new class {
			public function html(): string
			{
				return '<script></script><b>boiler</b>';
			}
		};
		$value = $this->objectProxy($object);

		$this->assertSame('<b>boiler</b>', (string) $value->html()->sanitize());
	}

	#[TestDox('Getter throws I')]
	public function testGetterThrowsI(): void
	{
		$this->throws(RuntimeException::class, 'No such property');

		$value = $this->objectProxy(new class {
			public function __toString(): string
			{
				return 'test';
			}
		});
		$value->test;
	}

	#[TestDox('Getter throws II')]
	public function testGetterThrowsII(): void
	{
		$this->throws(RuntimeException::class, 'No such property');

		$obj = new class {};
		$value = $this->objectProxy($obj);
		$value->test;
	}

	#[TestDox('Setter throws I')]
	public function testSetterThrowsI(): void
	{
		$this->throws(RuntimeException::class, 'No such property');

		$value = $this->objectProxy(new class {
			public function __toString(): string
			{
				return 'test';
			}
		});
		$value->test = null;
	}

	#[TestDox('Setter throws II')]
	public function testSetterThrowsII(): void
	{
		$this->throws(RuntimeException::class, 'No such property');

		$obj = new class {
			public function __set(string $name, mixed $value): void
			{
				if ($name && $value === null) {
					throw new ValueError();
				}
			}
		};
		$value = $this->objectProxy($obj);
		$value->test = null;
	}

	public function testMethodCallThrows(): void
	{
		$this->throws(RuntimeException::class, 'No such method');

		$value = $this->objectProxy(new class {
			public function __toString(): string
			{
				return 'test';
			}
		});
		$value->test();
	}
}
