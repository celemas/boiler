<?php

declare(strict_types=1);

namespace Celema\Boiler\Tests;

use Celema\Boiler\Exception\LogicException;
use Celema\Boiler\Location;
use Celema\Boiler\Sections;

final class SectionsTest extends TestCase
{
	public function testAssertClosedReportsUnexpectedlyClosedSectionLocation(): void
	{
		$sections = new Sections();
		$line = __LINE__ + 1;
		$sections->begin('scripts', new Location(__FILE__, $line));
		$checkpoint = $sections->checkpoint();
		echo '<script src="/app.js"></script>';
		$sections->end();

		try {
			$sections->assertClosed($checkpoint);
			$this->fail('LogicException was not thrown');
		} catch (LogicException $e) {
			$this->assertSame(__FILE__, $e->getFile());
			$this->assertSame($line, $e->getLine());
			$this->assertSame(__FILE__, $e->location()?->path);
			$this->assertSame($line, $e->location()?->line);
			$this->assertStringContainsString(
				'Section capture block `scripts` was closed unexpectedly at ' . __FILE__ . ":{$line}",
				$e->getMessage(),
			);
		}
	}
}
