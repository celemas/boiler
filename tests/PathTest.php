<?php

declare(strict_types=1);

namespace Celema\Boiler\Tests;

use Celema\Boiler\Exception\LookupException;
use Celema\Boiler\Path;
use PHPUnit\Framework\Attributes\TestDox;

final class PathTest extends TestCase
{
	public function testInitialization(): void
	{
		$tplp = new Path($this::DEFAULT_DIR, 'simple');

		$this->assertSame(true, $tplp->isValid());
		$this->assertStringEndsWith('tests/templates/default/simple.php', $tplp->path());
		$this->assertSame('', $tplp->error());
	}

	#[TestDox('Initialization with PHP extension')]
	public function testInitializationWithPhpExtension(): void
	{
		$tplp = new Path($this::DEFAULT_DIR, 'simple.php');

		$this->assertSame(true, $tplp->isValid());
		$this->assertStringEndsWith('tests/templates/default/simple.php', $tplp->path());
		$this->assertSame('', $tplp->error());
	}

	public function testInitializationWithCustomExtension(): void
	{
		$tplp = new Path($this::DEFAULT_DIR, 'extension.tpl');

		$this->assertSame(true, $tplp->isValid());
		$this->assertStringEndsWith('tests/templates/default/extension.tpl', $tplp->path());
		$this->assertSame('', $tplp->error());
	}

	public function testFailingInitializationEmptyDirectoryString(): void
	{
		$tplp = new Path('', 'simple');

		$this->assertSame(false, $tplp->isValid());
		$this->assertSame('Template directory must not be an empty string', $tplp->error());
	}

	public function testFailingInitializationNonExistentDirectory(): void
	{
		$tplp = new Path('doesnotexist', 'simple');

		$this->assertSame(false, $tplp->isValid());
		$this->assertSame("Template directory not found: 'doesnotexist'", $tplp->error());
	}

	public function testFailingOutsideOfTemplateDirectory(): void
	{
		$tplp = new Path($this::DEFAULT_DIR, '../unreachable');

		$this->assertSame(false, $tplp->isValid());
		$this->assertStringStartsWith('Template resides outside of root directory', $tplp->error());
	}

	#[TestDox('Failing outside of template directory with PHP extension')]
	public function testFailingOutsideOfTemplateDirectoryWithPhpExtension(): void
	{
		$tplp = new Path($this::DEFAULT_DIR, '../unreachable.php');

		$this->assertSame(false, $tplp->isValid());
		$this->assertStringStartsWith('Template resides outside of root directory', $tplp->error());
	}

	public function testRejectsSiblingDirectoryWithSharedPrefix(): void
	{
		$base = sys_get_temp_dir() . '/boiler-template-path-' . uniqid();
		$root = $base . '/templates';
		$sibling = $base . '/templates_evil';
		$file = $sibling . '/pwn.php';

		mkdir($root, recursive: true);
		mkdir($sibling, recursive: true);
		file_put_contents($file, 'PWN');

		try {
			$tplp = new Path($root, '../templates_evil/pwn');

			$this->assertSame(false, $tplp->isValid());
			$this->assertStringStartsWith('Template resides outside of root directory', $tplp->error());
		} finally {
			unlink($file);
			rmdir($sibling);
			rmdir($root);
			rmdir($base);
		}
	}

	public function testErrorWhenAccessingInvalidPath(): void
	{
		$this->throws(LookupException::class, 'Error while accessing path');

		$tplp = new Path('', 'simple');
		$tplp->path();
	}
}
