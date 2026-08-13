<?php

declare(strict_types=1);

namespace Celema\Boiler\Tests;

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
