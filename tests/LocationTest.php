<?php

declare(strict_types=1);

namespace Celema\Boiler\Tests;

use Celema\Boiler\Exception\RenderException;
use Celema\Boiler\Location;

final class LocationTest extends TestCase
{
	public function testFromThrowableFallsBackToPathWithoutMatchingFrame(): void
	{
		$path = '/not/in/trace.php';
		$location = Location::fromThrowable($path, new \RuntimeException('Broken'));

		$this->assertSame($path, $location->path);
		$this->assertNull($location->line);
		$this->assertSame($path, (string) $location);
	}

	public function testFromBacktraceFallsBackToPathWithoutMatchingFrame(): void
	{
		$path = '/not/in/backtrace.php';
		$location = Location::fromBacktrace($path);

		$this->assertSame($path, $location->path);
		$this->assertNull($location->line);
	}

	public function testRenderExceptionWithoutTemplateFrameReportsPathOnly(): void
	{
		$path = '/not/in/trace.php';
		$exception = RenderException::fromThrowable($path, new \RuntimeException('Broken'));

		$this->assertSame($path, $exception->location()?->path);
		$this->assertNull($exception->location()?->line);
		$this->assertStringContainsString(
			"Template rendering error ({$path}): Broken",
			$exception->getMessage(),
		);
	}
}
