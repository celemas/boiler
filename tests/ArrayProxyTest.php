<?php

declare(strict_types=1);

namespace Celema\Boiler\Tests;

use Celema\Boiler\Exception\OutOfBoundsException;
use Celema\Boiler\Exception\RuntimeException;
use Celema\Boiler\Exception\UnexpectedValueException;
use Celema\Boiler\Proxy\ArrayProxy;
use Celema\Boiler\Proxy\IteratorProxy;
use Celema\Boiler\Proxy\ObjectProxy;
use Celema\Boiler\Proxy\StringProxy;

final class ArrayProxyTest extends TestCase
{
	public function testCount(): void
	{
		$arrval = $this->arrayProxy([1, 2, 3]);

		$this->assertSame(3, count($arrval));
	}

	public function testUnwrap(): void
	{
		$arrval = $this->arrayProxy(['string', 2]);

		$this->assertSame(['string', 2], $arrval->unwrap());
	}

	public function testIsComparesTheRawArray(): void
	{
		$arrval = $this->arrayProxy(['a' => 1, 'b' => '<i>']);

		$this->assertTrue($arrval->is(['a' => 1, 'b' => '<i>']));
		$this->assertFalse($arrval->is(['a' => 1, 'b' => '&lt;i&gt;']));
		$this->assertTrue($arrval->is($this->arrayProxy(['a' => 1, 'b' => '<i>'])));
	}

	public function testInComparesTheRawArrayAgainstEachElement(): void
	{
		$arrval = $this->arrayProxy(['a', 'b']);

		$this->assertTrue($arrval->in([['a'], ['a', 'b']]));
		$this->assertTrue($arrval->in($this->arrayProxy([['a', 'b']])));
		$this->assertFalse($arrval->in([['b', 'a']]));
	}

	public function testHelperContains(): void
	{
		$arrval = $this->arrayProxy(['featured', '<i>']);

		$this->assertTrue($arrval->contains('featured'));
		$this->assertTrue($arrval->contains($this->stringProxy('<i>')));
		$this->assertFalse($arrval->contains('&lt;i&gt;'));
		$this->assertFalse($arrval->contains(true));
	}

	public function testHelperExists(): void
	{
		$arrval = $this->arrayProxy([1, 2]);

		$this->assertSame(true, $arrval->exists(0));
		$this->assertSame(true, $arrval->exists(1));
		$this->assertSame(false, $arrval->exists(2));
		$this->assertSame(false, $arrval->exists('test'));

		$arrval = $this->arrayProxy([1, 'test' => 2]);

		$this->assertSame(true, $arrval->exists(0));
		$this->assertSame(false, $arrval->exists(1));
		$this->assertSame(false, $arrval->exists(2));
		$this->assertSame(true, $arrval->exists('test'));
	}

	public function testHelperMerge(): void
	{
		$arrval1 = $this->arrayProxy([1, 2]);
		$arrval2 = $this->arrayProxy([3, 4]);

		$this->assertSame([1, 2, 3, 4], $arrval1->merge($arrval2)->unwrap());
		$this->assertSame([1, 2, 5, 6], $arrval1->merge([5, 6])->unwrap());
		$this->assertSame(6, $arrval1->merge([5, 6])[3]);
		$this->assertInstanceOf(StringProxy::class, $arrval1->merge([5, 'string'])[3]);
	}

	public function testHelperMap(): void
	{
		$arrval = $this->arrayProxy(['str1', 'str2']);

		$this->assertSame(['str1plus', 'str2plus'], $arrval->map(static fn($v) => $v . 'plus')->unwrap());
	}

	public function testHelperFilter(): void
	{
		$arrval = $this->arrayProxy([1, 3, 4, 2]);

		$this->assertSame([1, 2], array_values($arrval->filter(static fn($v) => $v < 3)->unwrap()));
	}

	public function testHelperReduce(): void
	{
		$arrval = $this->arrayProxy([1, 3, 4, 2]);

		$this->assertSame(10, $arrval->reduce(static fn($c, $v) => $c + $v, 0));

		$arrval = $this->arrayProxy(['a', 'b', 'c']);

		$this->assertSame('abc', $arrval->reduce(static fn($c, $v) => $c . $v, '')->unwrap());
	}

	public function testHelperSorted(): void
	{
		$arrval = $this->arrayProxy([1, 3, 4, 2]);
		$this->assertSame([1, 2, 3, 4], $arrval->sorted()->unwrap());

		$arrval = $this->arrayProxy(['a' => 3, 'b' => 1, 'c' => 2]);
		$this->assertSame([1, 2, 3], $arrval->sorted()->unwrap());
		$this->assertSame([1, 2, 3], $arrval->sorted('  ')->unwrap());
		$this->assertSame([3, 2, 1], $arrval->sorted('r')->unwrap());
		$this->assertSame(['b' => 1, 'c' => 2, 'a' => 3], $arrval->sorted('a')->unwrap());
		$this->assertSame(['a' => 3, 'c' => 2, 'b' => 1], $arrval->sorted('ar')->unwrap());
		// Check if original value is preserved
		$this->assertSame(['a' => 3, 'b' => 1, 'c' => 2], $arrval->unwrap());

		$arrval = $this->arrayProxy(['b' => 3, 'c' => 1, 'a' => 2]);
		$this->assertSame(['a' => 2, 'b' => 3, 'c' => 1], $arrval->sorted('k')->unwrap());
		$this->assertSame(['c' => 1, 'b' => 3, 'a' => 2], $arrval->sorted('kr')->unwrap());
	}

	public function testHelperSortedThrows(): void
	{
		$this->throws(UnexpectedValueException::class);

		$arrval = $this->arrayProxy(['B', 'a']);
		$arrval->sorted('t');
	}

	public function testHelperSortedUserdefined(): void
	{
		$arrval = $this->arrayProxy(['B', 'a', 'C', 'c', 'A', 'b']);
		$this->assertSame(
			['a', 'A', 'B', 'b', 'C', 'c'],
			$arrval
				->sorted(
					'u',
					static function ($a, $b) {
						if (strtolower($a) > strtolower($b)) {
							return 1;
						}

						if (strtolower($a) < strtolower($b)) {
							return -1;
						}

						return 0;
					},
				)
				->unwrap(),
		);

		$this->assertSame(
			[1 => 'a', 4 => 'A', 0 => 'B', 5 => 'b', 2 => 'C', 3 => 'c'],
			$arrval
				->sorted(
					'ua',
					static function ($a, $b) {
						if (strtolower($a) > strtolower($b)) {
							return 1;
						}

						if (strtolower($a) < strtolower($b)) {
							return -1;
						}

						return 0;
					},
				)
				->unwrap(),
		);
	}

	public function testHelperSortedUserdefinedThrows(): void
	{
		$this->throws(UnexpectedValueException::class);

		$arrval = $this->arrayProxy(['B', 'a']);
		$arrval->sorted('ut', static fn($a, $b) => strtolower($a) > strtolower($b));
	}

	public function testHelperSortedUserdefinedThrowsNoCallable(): void
	{
		$this->throws(RuntimeException::class);

		$arrval = $this->arrayProxy(['B', 'a']);
		$arrval->sorted('u');
	}

	public function testArrayAccess(): void
	{
		$arrval = $this->arrayProxy([1, 2, 'key' => 3]);

		$this->assertSame(1, $arrval[0]);
		$this->assertSame(2, $arrval[1]);
		$this->assertSame(3, $arrval['key']);
	}

	public function testOffsetExistsUsesArrayKeyExistsSemantics(): void
	{
		$arrval = $this->arrayProxy([1, null]);

		$this->assertTrue($arrval->offsetExists(0));
		$this->assertTrue($arrval->offsetExists(1));
		$this->assertFalse($arrval->offsetExists(2));
	}

	public function testIteration(): void
	{
		$arrval = $this->arrayProxy([1, 2, 3]);
		$new = [];

		foreach ($arrval as $val) {
			$new[] = $val + 2;
		}

		$this->assertSame([3, 4, 5], $new);
	}

	public function testNullValue(): void
	{
		$arrval = $this->arrayProxy([1, null]);

		$this->assertSame(1, $arrval[0]);
		$this->assertSame(null, $arrval[1]);
	}

	public function testSetValue(): void
	{
		$arrval = $this->arrayProxy([1, 2, 3]);
		$arrval[3] = 44;
		$arrval[] = 55;

		$this->assertSame([1, 2, 3, 44, 55], $arrval->unwrap());
	}

	public function testSetValueWithStringKey(): void
	{
		$arrval = $this->arrayProxy(['a' => 1, 'b' => 2]);
		$arrval['c'] = 3;

		$this->assertSame(['a' => 1, 'b' => 2, 'c' => 3], $arrval->unwrap());
	}

	public function testSetValueUpdatesPreviouslyAccessedOffset(): void
	{
		$arrval = $this->arrayProxy(['before']);
		$this->assertSame('before', $arrval[0]->unwrap());

		$arrval[0] = 'after';

		$this->assertSame('after', $arrval[0]->unwrap());
	}

	public function testSetValueUnwrapsWrappedValue(): void
	{
		$arrval = $this->arrayProxy([]);
		$arrval['html'] = $this->stringProxy('<b>boiler</b>');

		$this->assertSame(['html' => '<b>boiler</b>'], $arrval->unwrap());
		$this->assertSame('<b>boiler</b>', $arrval['html']->unwrap());
	}

	public function testAppendValueUnwrapsNestedWrappedValues(): void
	{
		$arrval = $this->arrayProxy([]);
		$arrval[] = ['html' => $this->stringProxy('<b>boiler</b>')];

		$this->assertSame([['html' => '<b>boiler</b>']], $arrval->unwrap());
		$this->assertSame('<b>boiler</b>', $arrval[0]['html']->unwrap());
	}

	public function testUnsetValue(): void
	{
		$arrval = $this->arrayProxy([1, 2, 3]);
		unset($arrval[1]);

		$this->assertSame([0 => 1, 2 => 3], $arrval->unwrap());
	}

	public function testWrappedArrayAccess(): void
	{
		$obj = new class {};
		$stringable = new class {
			public function __toString(): string
			{
				return '';
			}
		};
		$iterator = (static function () {
			yield 1;
		})();
		$arrval = $this->arrayProxy(['string', $obj, $stringable, [1, 2], $iterator]);

		$this->assertInstanceOf(StringProxy::class, $arrval[0]);
		$this->assertInstanceOf(ObjectProxy::class, $arrval[1]);
		$this->assertInstanceOf(ObjectProxy::class, $arrval[2]);
		$this->assertInstanceOf(ArrayProxy::class, $arrval[3]);
		$this->assertInstanceOf(IteratorProxy::class, $arrval[4]);
	}

	public function testWrappedIteration(): void
	{
		$obj = new class {};
		$stringable = new class {
			public function __toString(): string
			{
				return '';
			}
		};
		$iterator = (static function () {
			yield 1;
		})();
		$arrval = $this->arrayProxy(['string', $obj, $stringable, [1, 2], $iterator]);

		$new = [];

		foreach ($arrval as $val) {
			$new[] = $val::class;
		}

		$this->assertSame(
			[
				StringProxy::class,
				ObjectProxy::class,
				ObjectProxy::class,
				ArrayProxy::class,
				IteratorProxy::class,
			],
			$new,
		);
	}

	public function testNestedHahns(): void
	{
		$arrval = $this->arrayProxy([['first'], ['second', 'third']]);

		$this->assertInstanceOf(ArrayProxy::class, $arrval[0]);
		$this->assertInstanceOf(StringProxy::class, $arrval[0][0]);
		$this->assertInstanceOf(ArrayProxy::class, $arrval[1]);
		$this->assertInstanceOf(StringProxy::class, $arrval[1][0]);
		$this->assertInstanceOf(StringProxy::class, $arrval[1][1]);
	}

	public function testUndefinedNumericKey(): void
	{
		$this->throws(OutOfBoundsException::class, 'Undefined array key 4');

		$arrval = $this->arrayProxy([1, 2, 3]);
		$arrval[4];
	}

	public function testUndefinedArrayKey(): void
	{
		$this->throws(OutOfBoundsException::class, "Undefined array key 'key'");

		$arrval = $this->arrayProxy([1, 2, 3]);
		$arrval['key'];
	}
}
