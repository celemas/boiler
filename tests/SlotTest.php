<?php

declare(strict_types=1);

namespace Celemas\Boiler\Tests;

use Celemas\Boiler\Engine;
use Celemas\Boiler\Exception\RenderException;

final class SlotTest extends TestCase
{
	public function testRendersSlotOnce(): void
	{
		$engine = Engine::create(self::DEFAULT_DIR);

		$this->assertSame(
			'<div class="box"><b>hello</b></div>',
			$this->fullTrim($engine->render('slotonce')),
		);
	}

	public function testRepeatsSlotPerRowWithEscaping(): void
	{
		$engine = Engine::create(self::DEFAULT_DIR);

		$this->assertSame(
			'<ul><li><input name="a" value="1"></li><li><input name="b" value="&lt;x&gt;"></li></ul>',
			$this->fullTrim($engine->render('slotcaller', [
				'rows' => [
					['name' => 'a', 'value' => '1'],
					['name' => 'b', 'value' => '<x>'],
				],
			])),
		);
	}

	public function testSlotCanReturnMarkup(): void
	{
		$engine = Engine::create(self::DEFAULT_DIR);

		$this->assertSame(
			'<div class="box"><i>returned</i></div>',
			$this->fullTrim($engine->render('slotreturn')),
		);
	}

	public function testSlotCanInsertNestedTemplate(): void
	{
		$engine = Engine::create(self::DEFAULT_DIR);

		$this->assertSame(
			'<div class="box"><p>nested</p><p>7</p></div>',
			$this->fullTrim($engine->render('slotnested')),
		);
	}

	public function testHasSlotRendersProvidedContent(): void
	{
		$engine = Engine::create(self::DEFAULT_DIR);

		$this->assertSame(
			'<div>provided</div>',
			$this->fullTrim($engine->render('slothas')),
		);
	}

	public function testHasSlotFallsBackWhenNoSlotGiven(): void
	{
		$engine = Engine::create(self::DEFAULT_DIR);

		$this->assertSame(
			'<div>fallback</div>',
			$this->fullTrim($engine->render('slotmissingoptional')),
		);
	}

	public function testSlotClosureRespectsAutoescape(): void
	{
		$engine = Engine::create(self::DEFAULT_DIR);

		$this->assertSame(
			'<div class="box">&lt;b&gt;x&lt;/b&gt;</div>',
			$this->fullTrim($engine->render('slotraw', ['html' => '<b>x</b>'])),
		);
	}

	public function testSlotClosureStaysRawInUnescapedEngine(): void
	{
		$engine = Engine::unescaped(self::DEFAULT_DIR);

		$this->assertSame(
			'<div class="box"><b>x</b></div>',
			$this->fullTrim($engine->render('slotraw', ['html' => '<b>x</b>'])),
		);
	}

	public function testCallingSlotWithoutProvidingOneThrows(): void
	{
		$this->throws(RenderException::class, 'No slot was provided');

		Engine::create(self::DEFAULT_DIR)->render('slotnoslot');
	}

	public function testExceptionInSlotIsWrappedAndBuffersRestored(): void
	{
		$engine = Engine::create(self::DEFAULT_DIR);
		$level = ob_get_level();

		try {
			$engine->render('slotthrows');
			$this->fail('RenderException was not thrown');
		} catch (RenderException $e) {
			$path = self::DEFAULT_DIR . '/slotthrows.php';

			$this->assertSame($path, $e->getFile());
			$this->assertSame(2, $e->getLine());
			$this->assertSame($path, $e->location()?->path);
			$this->assertSame(2, $e->location()?->line);
			$this->assertStringContainsString('boom in slot', $e->getMessage());
			$this->assertStringContainsString($path . ':2', $e->getMessage());
		}

		$this->assertSame($level, ob_get_level());
	}
}
