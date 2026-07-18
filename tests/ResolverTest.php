<?php

declare(strict_types=1);

namespace Celema\Boiler\Tests;

use Celema\Boiler\Exception\LookupException;
use Celema\Boiler\Exception\UnexpectedValueException;
use Celema\Boiler\Resolver;

final class ResolverTest extends TestCase
{
	public function testResolvesTemplateFromFirstMatchingDirectory(): void
	{
		$resolver = new Resolver($this->templates($this->additional()));

		$this->assertStringEndsWith(
			'tests/templates/default/simple.php',
			$resolver->resolve('simple'),
		);
	}

	public function testCachesResolvedTemplatePath(): void
	{
		$resolver = new Resolver($this->templates());

		$resolved = $resolver->resolve('simple');
		$this->assertSame($resolved, $resolver->resolve('simple'));

		$reflection = new \ReflectionClass($resolver);
		$cache = $reflection->getProperty('pathCache');
		$pathCache = $cache->getValue($resolver);

		$this->assertCount(1, $pathCache);
		$this->assertArrayHasKey('simple', $pathCache);
	}

	public function testResolvesNamespacedTemplate(): void
	{
		$resolver = new Resolver($this->namespaced($this->additional()));

		$this->assertStringEndsWith(
			'tests/templates/default/sub/home.php',
			$resolver->resolve('namespace:sub/home'),
		);
	}

	public function testRejectsEmptyDirectoryList(): void
	{
		$this->throws(LookupException::class, 'At least one template directory must be configured');

		new Resolver([]);
	}

	public function testRejectsInvalidTemplatePathCharacters(): void
	{
		$this->throws(UnexpectedValueException::class, 'invalid or empty');

		$resolver = new Resolver($this->templates());
		$resolver->resolve("\0");
	}

	public function testRejectsInvalidTemplateFormat(): void
	{
		$this->throws(LookupException::class, 'Invalid template format');

		$resolver = new Resolver($this->templates());
		$resolver->resolve('default:sub:index');
	}

	public function testThrowsForUnknownNamespace(): void
	{
		$this->throws(LookupException::class, 'Template namespace');

		$resolver = new Resolver($this->namespaced());
		$resolver->resolve('missing:sub/home');
	}

	public function testPreventsTemplateTraversalOutsideConfiguredRoot(): void
	{
		$this->throws(LookupException::class, 'outside');

		$resolver = new Resolver($this->templates());
		$resolver->resolve('../unreachable');
	}

	public function testPreventsTemplateTraversalToSiblingDirectoryWithSharedPrefix(): void
	{
		$base = sys_get_temp_dir() . '/boiler-resolver-' . uniqid();
		$root = $base . '/templates';
		$sibling = $base . '/templates_evil';
		$file = $sibling . '/pwn.php';

		mkdir($root, recursive: true);
		mkdir($sibling, recursive: true);
		file_put_contents($file, 'PWN');

		$this->throws(LookupException::class, 'outside');

		try {
			$resolver = new Resolver($root);
			$resolver->resolve('../templates_evil/pwn');
		} finally {
			unlink($file);
			rmdir($sibling);
			rmdir($root);
			rmdir($base);
		}
	}
}
