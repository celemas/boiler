<?php

declare(strict_types=1);

namespace Celema\Boiler\Tests;

use Celema\Boiler\Contract\Escaper;
use Celema\Boiler\Escapers;
use Celema\Boiler\Filters;
use Celema\Boiler\Proxy\ArrayProxy;
use Celema\Boiler\Proxy\IteratorProxy;
use Celema\Boiler\Proxy\ObjectProxy;
use Celema\Boiler\Proxy\StringProxy;
use Celema\Boiler\Wrapper;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Traversable;

/**
 * @internal
 *
 * @coversNothing
 */
class TestCase extends BaseTestCase
{
	public const ROOT_DIR = __DIR__ . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR;
	public const DEFAULT_DIR = self::ROOT_DIR . 'default';
	public const DS = DIRECTORY_SEPARATOR;

	public function fulltrim(string $text): string
	{
		return trim(
			preg_replace(
				'/> </',
				'><',
				preg_replace(
					'/\s+/',
					' ',
					preg_replace('/\n/', '', $text),
				),
			),
		);
	}

	public function templates(array $templates = []): array
	{
		return array_merge($templates, [self::DEFAULT_DIR]);
	}

	public function namespaced(array $templates = []): array
	{
		return array_merge($templates, [
			'namespace' => self::DEFAULT_DIR,
		]);
	}

	public function additional(): array
	{
		return [
			'additional' => self::ROOT_DIR . 'additional',
		];
	}

	public function obj(): object
	{
		return new class {
			public function name(): string
			{
				return 'boiler';
			}
		};
	}

	public function throws(string $exception, ?string $message = null): void
	{
		$this->expectException($exception);

		if ($message) {
			$this->expectExceptionMessageMatches("/{$message}/");
		}
	}

	protected function wrapper(?Filters $filters = null): Wrapper
	{
		return new Wrapper(filters: $filters);
	}

	/** @param array<array-key, mixed> $value */
	protected function arrayProxy(array $value): ArrayProxy
	{
		return new ArrayProxy($value, $this->wrapper());
	}

	protected function iteratorProxy(Traversable $value): IteratorProxy
	{
		return new IteratorProxy($value, $this->wrapper());
	}

	protected function objectProxy(
		object $value,
		?Filters $filters = null,
	): ObjectProxy {
		return new ObjectProxy($value, $this->wrapper($filters));
	}

	protected function stringProxy(
		string $value,
		?Filters $filters = null,
	): StringProxy {
		return new StringProxy($value, $this->wrapper($filters));
	}

	/**
	 * @param array<non-empty-string, Escaper> $escapers
	 */
	protected function escapedStringProxy(
		string $value,
		array $escapers,
		string $default = 'html',
		?Filters $filters = null,
	): StringProxy {
		return new StringProxy(
			$value,
			new \Celema\Boiler\Wrapper(new Escapers($escapers, $default), $filters),
		);
	}
}
