<?php

declare(strict_types=1);

namespace Celema\Boiler\Tests;

use Celema\Boiler\Engine;
use Celema\Boiler\Exception\LookupException;
use Celema\Boiler\Exception\RenderException;

final class SlotTest extends TestCase
{
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

	public function testLayoutPrintsPageAsSlot(): void
	{
		$engine = Engine::create(self::DEFAULT_DIR);

		$this->assertSame("filled[<p>page</p>\n]Title", $engine->render('slotfilledpage'));
	}

	public function testPageOfOnlyWhitespaceCountsAsNoSlot(): void
	{
		$engine = Engine::create(self::DEFAULT_DIR);

		$this->assertSame('empty[]Title', $engine->render('slotemptypage'));
	}

	public function testComponentPrintsBlockAsSlot(): void
	{
		$engine = Engine::create(self::DEFAULT_DIR);

		$this->assertSame(
			'<div class="card"><h2>&lt;News&gt;</h2><p>&lt;b&gt;</p></div>',
			$this->fullTrim($engine->render('componentcard', ['text' => '<b>'])),
		);
	}

	public function testComponentBlockOfOnlyWhitespaceCountsAsNoSlot(): void
	{
		$engine = Engine::create(self::DEFAULT_DIR);

		$this->assertSame('<div>fallback</div><div>given</div>', $this->fullTrim($engine->render('componentempty')));
	}

	public function testComponentIgnoresSlotDataSoTemplatesForEachWorkToo(): void
	{
		$engine = Engine::create(self::DEFAULT_DIR);

		$this->assertSame(
			'<ul><li><b>row</b></li><li><b>row</b></li></ul>',
			$this->fullTrim($engine->render('componentrows', ['rows' => [['name' => 'a'], ['name' => 'b']]])),
		);
	}

	public function testComponentWithScriptWorksInsideSection(): void
	{
		$engine = Engine::create(self::DEFAULT_DIR);

		$this->assertSame(
			'<div class="widget"><p>body</p></div><script src="/widget.js"></script>',
			$this->fullTrim($engine->render('componentinsection')),
		);
	}

	public function testEachTemplateCanPassItsSlotToComponent(): void
	{
		$engine = Engine::create(self::DEFAULT_DIR);

		$this->assertSame(
			'<div class="box"><b>a</b></div><div class="box"><b>&lt;x&gt;</b></div>',
			$this->fullTrim($engine->render('eachcomponent', ['rows' => [['name' => 'a'], ['name' => '<x>']]])),
		);
	}

	public function testEndWithNameOfSectionFailsInsideComponent(): void
	{
		$this->assertRenderFails(
			'componentmismatch',
			3,
			"`end('title')` does not match the open component `card`",
		);
	}

	public function testUnclosedComponentFailsRender(): void
	{
		$path = self::DEFAULT_DIR . '/componentunclosed.php';

		$this->assertRenderFails('componentunclosed', 1, "Unclosed component `card` at {$path}:1");
	}

	public function testMissingComponentFailsAtItsCall(): void
	{
		$e = $this->assertRenderFails('componentmissing', 2, 'Template not found');

		$this->assertInstanceOf(LookupException::class, $e->getPrevious());
	}

	public function testCallingSlotWithoutProvidingOneThrows(): void
	{
		try {
			Engine::create(self::DEFAULT_DIR)->render('slotnoslot');
			$this->fail('RenderException was not thrown');
		} catch (RenderException $e) {
			$path = self::DEFAULT_DIR . '/slotbox.php';

			$this->assertStringContainsString('No slot was provided', $e->getMessage());
			$this->assertSame($path, $e->location()?->path);
			$this->assertSame(1, $e->location()?->line);
		}
	}

	private function assertRenderFails(string $template, int $line, string $message): RenderException
	{
		$level = ob_get_level();

		try {
			Engine::create(self::DEFAULT_DIR)->render($template);
		} catch (RenderException $e) {
			$this->assertSame(self::DEFAULT_DIR . "/{$template}.php", $e->location()?->path);
			$this->assertSame($line, $e->location()?->line);
			$this->assertStringContainsString($message, $e->getMessage());
			$this->assertSame($level, ob_get_level());

			return $e;
		}

		$this->fail('RenderException was not thrown');
	}
}
