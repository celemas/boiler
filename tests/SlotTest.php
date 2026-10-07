<?php

declare(strict_types=1);

namespace Celema\Boiler\Tests;

use Celema\Boiler\Engine;
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
}
