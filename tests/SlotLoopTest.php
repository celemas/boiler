<?php

declare(strict_types=1);

namespace Celema\Boiler\Tests;

use Celema\Boiler\Blocks;
use Celema\Boiler\Engine;
use Celema\Boiler\Exception\LogicException;
use Celema\Boiler\Exception\RenderException;
use Celema\Boiler\Location;
use Celema\Boiler\SlotLoop;
use Celema\Boiler\Template;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

final class SlotLoopTest extends TestCase
{
	private const array ROWS = [
		['name' => 'a', 'value' => '1'],
		['name' => 'b', 'value' => '<x>'],
	];

	public function testLoopBodyRendersAsSlotWithEscapedData(): void
	{
		$engine = Engine::create(self::DEFAULT_DIR);

		$this->assertSame(
			'<h1>before</h1><ul><li><input name="a" value="1"></li>'
				. '<li><input name="b" value="&lt;x&gt;"></li></ul><p>after</p>',
			$this->fullTrim($engine->render('eachrows', ['rows' => self::ROWS])),
		);
	}

	public function testLoopDataStaysRawInUnescapedEngine(): void
	{
		$engine = Engine::unescaped(self::DEFAULT_DIR);

		$this->assertSame(
			'<h1>before</h1><ul><li><input name="a" value="1"></li>'
				. '<li><input name="b" value="<x>"></li></ul><p>after</p>',
			$this->fullTrim($engine->render('eachrows', ['rows' => self::ROWS])),
		);
	}

	public function testTemplateWithoutSlotCallsSkipsLoopBody(): void
	{
		$engine = Engine::create(self::DEFAULT_DIR);

		$this->assertSame(
			'<h1>before</h1><ul></ul><p>after</p>',
			$this->fullTrim($engine->render('eachrows', ['rows' => []])),
		);
	}

	public function testContinueSkipsOneSlot(): void
	{
		$engine = Engine::create(self::DEFAULT_DIR);

		$this->assertSame(
			'<ul><li><b>a</b></li><li></li></ul>',
			$this->fullTrim($engine->render('eachcontinue', ['rows' => self::ROWS])),
		);
	}

	public function testBreakLeavesRemainingSlotsEmpty(): void
	{
		$engine = Engine::create(self::DEFAULT_DIR);

		$this->assertSame(
			'<ul><li><b>a</b></li><li></li></ul><p>after</p>',
			$this->fullTrim($engine->render('eachbreak', ['rows' => self::ROWS])),
		);
	}

	public function testReturnLeavesRemainingSlotsEmpty(): void
	{
		$engine = Engine::create(self::DEFAULT_DIR);

		$this->assertSame(
			'<ul><li><b>a</b></li><li></li></ul>',
			$this->fullTrim($engine->render('eachreturn', ['rows' => self::ROWS])),
		);
	}

	public function testCaughtExceptionInLoopBodyKeepsRenderedSlots(): void
	{
		$engine = Engine::create(self::DEFAULT_DIR);

		$this->assertSame(
			'<ul><li><b>a</b></li><li></li></ul><p>fallback</p>',
			$this->fullTrim($engine->render('eachrecover', ['rows' => self::ROWS])),
		);
	}

	public function testNestedLoopForwardsSlot(): void
	{
		$engine = Engine::create(self::DEFAULT_DIR);

		$this->assertSame(
			'<section><ul><li><b>1</b></li><li><b>&lt;x&gt;</b></li></ul></section>',
			$this->fullTrim($engine->render('eachforward', ['rows' => self::ROWS])),
		);
	}

	public function testLoopBodyCanCaptureSections(): void
	{
		$engine = Engine::create(self::DEFAULT_DIR);

		$this->assertSame(
			'<ul><li><i>a</i></li><li><i>&lt;x&gt;</i></li></ul><p>[&lt;x&gt;]</p>',
			$this->fullTrim($engine->render('eachsection', [
				'rows' => [['name' => 'a'], ['name' => '<x>']],
			])),
		);
	}

	public function testLoopInsideSectionOfCaller(): void
	{
		$engine = Engine::create(self::DEFAULT_DIR);

		$this->assertSame(
			'<div><ul><li><b>a</b></li><li><b>b</b></li></ul></div>',
			$this->fullTrim($engine->render('eachwithinsection', ['rows' => self::ROWS])),
		);
	}

	public function testSlotInsideSectionOfInsertedTemplateFailsRender(): void
	{
		$level = ob_get_level();

		try {
			Engine::create(self::DEFAULT_DIR)->render('eachinsection');
			$this->fail('RenderException was not thrown');
		} catch (RenderException $e) {
			$this->assertStringContainsString('cannot be rendered inside a section capture', $e->getMessage());
			$this->assertSame(self::DEFAULT_DIR . '/eachcaptured.php', $e->location()?->path);
			$this->assertSame(1, $e->location()?->line);
		}

		$this->assertSame($level, ob_get_level());
	}

	public function testLoopThatIsNeverIteratedFailsRender(): void
	{
		$this->assertUnfinishedLoop('eachoptional', ['rows' => self::ROWS, 'iterate' => false]);
	}

	public function testBreakOutOfLoopKeptInVariableFailsRender(): void
	{
		$this->assertUnfinishedLoop('eachkept', ['rows' => self::ROWS]);
	}

	/** @return iterable<string, array{bool, bool}> */
	public static function loopsKeptBeyondRender(): iterable
	{
		yield 'unstarted loop resumed' => [false, true];
		yield 'left loop resumed' => [true, true];
		yield 'left loop destroyed' => [true, false];
	}

	#[DataProvider('loopsKeptBeyondRender')]
	public function testLoopKeptBeyondRenderLeavesLaterBuffersAlone(bool $iterate, bool $resume): void
	{
		$kept = [];
		$engine = Engine::create(self::DEFAULT_DIR);
		$engine->method('keep', static function (Generator $loop) use (&$kept): string {
			$kept[] = $loop;

			return '';
		});

		try {
			$engine->render('eachkeep', ['rows' => self::ROWS, 'iterate' => $iterate]);
			$this->fail('RenderException was not thrown');
		} catch (RenderException $e) {
			$this->assertStringContainsString('did not finish within its template', $e->getMessage());
		}

		ob_start();
		echo 'unrelated';

		if ($resume) {
			try {
				$kept[0]->next();
				$this->fail('LogicException was not thrown');
			} catch (LogicException $e) {
				$this->assertStringContainsString('resumed after its template finished', $e->getMessage());
				$this->assertSame(self::DEFAULT_DIR . '/eachkeep.php', $e->getFile());
				$this->assertSame(1, $e->getLine());
			}
		}

		$kept = [];

		$this->assertSame('unrelated', ob_get_clean());
	}

	public function testReusedTemplateForgetsLoopsOfFailedRender(): void
	{
		$template = new Template(self::DEFAULT_DIR . '/eachoptional.php');

		try {
			$template->render(['rows' => self::ROWS, 'iterate' => false]);
			$this->fail('RenderException was not thrown');
		} catch (RenderException $e) {
			// The next render must not report this loop again.
			$this->assertStringContainsString('did not finish within its template', $e->getMessage());
		}

		$this->assertSame(
			'<ul><li><b>a</b></li><li><b>b</b></li></ul>',
			$this->fullTrim($template->render(['rows' => self::ROWS, 'iterate' => true])),
		);
	}

	public function testExceptionInLoopBodyPropagates(): void
	{
		$level = ob_get_level();

		try {
			Engine::create(self::DEFAULT_DIR)->render('eachthrows', ['rows' => self::ROWS]);
			$this->fail('RenderException was not thrown');
		} catch (RenderException $e) {
			$this->assertStringContainsString('boom in loop', $e->getMessage());
			$this->assertSame(self::DEFAULT_DIR . '/eachthrows.php', $e->location()?->path);
			$this->assertSame(2, $e->location()?->line);
			$this->assertInstanceOf(RuntimeException::class, $e->getPrevious());
		}

		$this->assertSame($level, ob_get_level());
	}

	public function testExceptionInInsertedTemplatePropagates(): void
	{
		$level = ob_get_level();

		try {
			Engine::create(self::DEFAULT_DIR)->render('eachpartialthrows');
			$this->fail('RenderException was not thrown');
		} catch (RenderException $e) {
			$this->assertStringContainsString('boom in partial', $e->getMessage());
			$this->assertSame(self::DEFAULT_DIR . '/eachfailing.php', $e->location()?->path);
			$this->assertSame(2, $e->location()?->line);
		}

		$this->assertSame($level, ob_get_level());
	}

	public function testSectionLeftOpenInLoopBodyFailsRender(): void
	{
		$level = ob_get_level();

		try {
			Engine::create(self::DEFAULT_DIR)->render('eachunclosed', ['rows' => self::ROWS]);
			$this->fail('RenderException was not thrown');
		} catch (RenderException $e) {
			// Reported after the first iteration, before the next one closes it.
			$this->assertStringContainsString('Unclosed section `open`', $e->getMessage());
			$this->assertSame(self::DEFAULT_DIR . '/eachunclosed.php', $e->location()?->path);
			$this->assertSame(3, $e->location()?->line);
		}

		$this->assertSame($level, ob_get_level());
	}

	public function testLoopBodyCannotCloseSectionOpenedBeforeLoop(): void
	{
		$level = ob_get_level();

		try {
			Engine::create(self::DEFAULT_DIR)->render('eachcloses', ['rows' => self::ROWS]);
			$this->fail('RenderException was not thrown');
		} catch (RenderException $e) {
			$this->assertStringEndsWith(
				'Section `list` cannot be closed here: it was opened outside the current output buffer or `each()` loop body',
				$e->getMessage(),
			);
			$this->assertSame(self::DEFAULT_DIR . '/eachcloses.php', $e->location()?->path);
			$this->assertSame(3, $e->location()?->line);
		}

		$this->assertSame($level, ob_get_level());
	}

	public function testSlotAfterTemplateRenderedThrows(): void
	{
		$loop = new SlotLoop(
			new Location('/tmp/caller.php', 3),
			new Blocks(),
			new Template(self::DEFAULT_DIR . '/slotbox.php'),
		);
		iterator_to_array($loop->run(static fn(): string => '', static fn(array $data): array => $data));

		$this->expectException(LogicException::class);
		$this->expectExceptionMessage('can only be rendered while its template renders');

		$loop->render([]);
	}

	/** @param array<string, mixed> $context */
	private function assertUnfinishedLoop(string $template, array $context): void
	{
		$level = ob_get_level();

		try {
			Engine::create(self::DEFAULT_DIR)->render($template, $context);
			$this->fail('RenderException was not thrown');
		} catch (RenderException $e) {
			$this->assertStringContainsString('did not finish within its template', $e->getMessage());
			$this->assertSame(self::DEFAULT_DIR . "/{$template}.php", $e->location()?->path);
			$this->assertSame(1, $e->location()?->line);
		}

		$this->assertSame($level, ob_get_level());
	}
}
