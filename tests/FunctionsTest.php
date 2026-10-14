<?php

declare(strict_types=1);

namespace Celema\Boiler\Tests;

use Celema\Boiler\Contract\Filter;
use Celema\Boiler\Engine;
use Celema\Boiler\Exception\LogicException;
use Celema\Boiler\Exception\RenderException;
use Celema\Boiler\Template;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use stdClass;

use function Celema\Boiler\unwrap;

final class FunctionsTest extends TestCase
{
	private const array CONTEXT = [
		'title' => 'Tom & Jerry',
		'empty' => '',
		'count' => 2,
		'items' => ['a', 'b' => 'c'],
	];

	public function testUnwrapReturnsTheOriginalValuesInEscapedAndUnescapedRenders(): void
	{
		$template = new Template(self::DEFAULT_DIR . '/unwrapfunction.php');
		$expected = 'same|blank|3|{"items":{"0":"a","b":"c"},"title":"Tom & Jerry"}';

		$this->assertSame($expected, trim($template->render(self::CONTEXT)));
		$this->assertSame($expected, trim($template->renderUnescaped(self::CONTEXT)));
	}

	public function testUnwrapReturnsTheOriginalValuesOutsideARender(): void
	{
		$object = new stdClass();

		$this->assertSame(
			[
				'title' => 'Tom & Jerry',
				'items' => ['a', 'b' => ['c']],
				'object' => $object,
				'nested' => ['title' => 'Tom & Jerry'],
				'count' => 2,
			],
			unwrap([
				'title' => $this->stringProxy('Tom & Jerry'),
				'items' => $this->arrayProxy(['a', 'b' => ['c']]),
				'object' => $this->objectProxy($object),
				'nested' => ['title' => $this->stringProxy('Tom & Jerry')],
				'count' => 2,
			]),
		);
		$this->assertSame('plain', unwrap('plain'));
	}

	public function testWrapUsesTheFiltersOfTheRenderInEscapedAndUnescapedRenders(): void
	{
		$engine = Engine::create($this->templates())->filter('mark', $this->mark('[', ']'));
		$expected = '&lt;b&gt;Tom &amp; Jerry&lt;/b&gt;|TOM &amp; JERRY|[a &amp; b]|3';

		$this->assertSame($expected, trim($engine->render('wrapfunction', self::CONTEXT)));
		$this->assertSame($expected, trim($engine->renderUnescaped('wrapfunction', self::CONTEXT)));
	}

	public function testWrapUsesTheRenderInProgress(): void
	{
		$inner = Engine::create($this->templates())->filter('mark', $this->mark('(', ')'));
		$outer = Engine::create($this->templates())
			->filter('mark', $this->mark('[', ']'))
			->method('inner', static fn(): string => $inner->render('wrapinner'));

		$this->assertSame('[a]|(x)|[b]', trim($outer->render('wrapnested')));
	}

	public function testWrapFailsOutsideARender(): void
	{
		/** @mago-expect lint:no-empty-catch-clause A failed render must not leave its wrapper behind. */
		try {
			new Template(self::DEFAULT_DIR . '/jsonencode.php')->render(['items' => ['a']]);
		} catch (RenderException) {
		}

		$this->expectException(LogicException::class);
		$this->expectExceptionMessage('w() works only while a template renders');

		w('value');
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState(false)]
	public function testAGlobalFunctionOfTheSameNameFailsTheRender(): void
	{
		$file = self::ROOT_DIR . 'clash.php';
		require $file;

		$this->expectException(LogicException::class);
		$this->expectExceptionMessage("Boiler's template function w() clashes with the global function w() in {$file}");

		new Template(self::DEFAULT_DIR . '/unwrapfunction.php')->render(self::CONTEXT);
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState(false)]
	public function testFunctionsLoadedBeforeTheFirstRenderAreBoilersOwn(): void
	{
		require dirname(__DIR__) . '/src/functions.php';

		$this->assertSame(
			'same|blank|3|{"items":{"0":"a","b":"c"},"title":"Tom & Jerry"}',
			trim(new Template(self::DEFAULT_DIR . '/unwrapfunction.php')->render(self::CONTEXT)),
		);
	}

	private function mark(string $open, string $close): Filter
	{
		return new readonly class($open, $close) implements Filter {
			public function __construct(
				private string $open,
				private string $close,
			) {}

			public function apply(string $value, mixed ...$args): string
			{
				return $this->open . $value . $this->close;
			}

			public function safe(): bool
			{
				return false;
			}
		};
	}
}
