<?php

declare(strict_types=1);

namespace Celema\Boiler\Tests;

use ArrayObject;
use Celema\Boiler\Proxy\ArrayProxy;
use Celema\Boiler\Proxy\IteratorProxy;
use Celema\Boiler\Proxy\StringProxy;

final class IteratorProxyTest extends TestCase
{
	public function testIteratorProxyWrapping(): void
	{
		$iterator = (static function () {
			yield 1;

			yield 'string';

			yield [1, 2];

			yield (static function () {
				yield 1;
			})();
		})();

		$iterval = $this->iteratorProxy($iterator);
		$new = [];

		foreach ($iterval as $val) {
			$new[] = $val;
		}

		$this->assertSame(1, $new[0]);
		$this->assertInstanceOf(StringProxy::class, $new[1]);
		$this->assertInstanceOf(ArrayProxy::class, $new[2]);
		$this->assertInstanceOf(IteratorProxy::class, $new[3]);
	}

	public function testIterationPreservesKeys(): void
	{
		$iterval = $this->iteratorProxy(
			(static function () {
				yield 10 => 'a';

				yield 20 => 'b';
			})(),
		);
		$keys = [];

		foreach ($iterval as $key => $_) {
			$keys[] = $key;
		}

		$this->assertSame([10, 20], $keys);
	}

	public function testIterationWrapsStringKeys(): void
	{
		[$key] = $this->keys($this->iteratorProxy(
			(static function () {
				yield '<b>' => 1;
			})(),
		));

		$this->assertInstanceOf(StringProxy::class, $key);
		$this->assertSame('&lt;b&gt;', (string) $key);
	}

	public function testIteratorProxyUnwrap(): void
	{
		$iterator = (static function () {
			yield 1;
		})();

		$iterval = $this->iteratorProxy($iterator);

		$this->assertSame($iterator, $iterval->unwrap());
	}

	public function testIteratorProxyIsComparesTheInnerIterator(): void
	{
		$iterator = (static function () {
			yield 1;
		})();

		$iterval = $this->iteratorProxy($iterator);

		$this->assertTrue($iterval->is($iterator));
		$this->assertTrue($iterval->is($this->iteratorProxy($iterator)));
		$this->assertFalse(
			$iterval->is(
				(static function () {
					yield 1;
				})(),
			),
		);
	}

	public function testIteratorProxyInComparesTheInnerIterator(): void
	{
		$iterator = (static function () {
			yield 1;
		})();

		$iterval = $this->iteratorProxy($iterator);

		$this->assertTrue($iterval->in([$iterator]));
		$this->assertTrue($iterval->in($this->arrayProxy([$iterator])));
		$this->assertFalse($iterval->in([]));
	}

	public function testNestedIterationOverAggregateUsesIndependentIterators(): void
	{
		$iterval = $this->iteratorProxy(new ArrayObject([1, 2]));
		$pairs = [];

		foreach ($iterval as $outer) {
			foreach ($iterval as $inner) {
				$pairs[] = [$outer, $inner];
			}
		}

		$this->assertSame([[1, 1], [1, 2], [2, 1], [2, 2]], $pairs);
	}

	public function testUnwrapAndIsUseTheWrappedAggregate(): void
	{
		$aggregate = new ArrayObject([1, 2]);
		$iterval = $this->iteratorProxy($aggregate);

		$this->assertSame($aggregate, $iterval->unwrap());
		$this->assertTrue($iterval->is($aggregate));
		$this->assertSame([1, 2], $iterval->toArray()->unwrap());
	}

	public function testIteratorProxyToArray(): void
	{
		$iterator = (static function () {
			yield 1;

			yield 2;
		})();

		$iterval = $this->iteratorProxy($iterator);

		$this->assertSame([1, 2], $iterval->toArray()->unwrap());
	}
}
