<?php

declare(strict_types=1);

namespace Celema\Boiler\Tests;

use ArrayIterator;
use ArrayObject;
use Celema\Boiler\Exception\RuntimeException;
use Celema\Boiler\Proxy\ArrayProxy;
use Celema\Boiler\Proxy\IteratorProxy;
use Celema\Boiler\Proxy\StringProxy;
use IteratorAggregate;
use Override;

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

	public function testTraversableObjectKeepsItsObjectAccess(): void
	{
		$object = new class implements IteratorAggregate {
			public string $label = '<b>label</b>';
			public ?string $missing = null;

			public function title(string $suffix): string
			{
				return 'Beer & Wine' . $suffix;
			}

			public function __toString(): string
			{
				return '<i>menu</i>';
			}

			public function __invoke(string $value): string
			{
				return "<{$value}>";
			}

			#[Override]
			public function getIterator(): ArrayIterator
			{
				return new ArrayIterator(['<b>item</b>']);
			}
		};
		$iterval = $this->iteratorProxy($object);

		$this->assertInstanceOf(StringProxy::class, $iterval->title($this->stringProxy('!')));
		$this->assertSame('Beer &amp; Wine!', (string) $iterval->title('!'));
		$this->assertSame('&lt;b&gt;label&lt;/b&gt;', (string) $iterval->label);
		$this->assertTrue(isset($iterval->label));
		$this->assertFalse(isset($iterval->missing));
		$iterval->label = $this->stringProxy('new');
		$this->assertSame('new', $object->label);
		$this->assertSame('&lt;i&gt;menu&lt;/i&gt;', (string) $iterval);
		$this->assertSame('&lt;x&gt;', (string) $iterval('x'));

		foreach ($iterval as $item) {
			$this->assertSame('&lt;b&gt;item&lt;/b&gt;', (string) $item);
		}
	}

	public function testTraversableWithoutTheAccessedMemberThrows(): void
	{
		$iterval = $this->iteratorProxy(new ArrayIterator([]));
		$attempts = [
			'No such method' => $iterval->title(...),
			'No such property' => static fn() => $iterval->label,
			'Wrapped object is not stringable' => static fn() => (string) $iterval,
		];

		foreach ($attempts as $message => $attempt) {
			try {
				$attempt();
				$this->fail("Expected: {$message}");
			} catch (RuntimeException $e) {
				$this->assertSame($message, $e->getMessage());
			}
		}

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('No such method');

		$iterval('x');
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
