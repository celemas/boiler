<?php

declare(strict_types=1);

namespace Celema\Boiler;

use Celema\Boiler\Exception\LogicException;
use Closure;
use ReflectionFunction;

/**
 * Backs the global template functions `u()` and `w()`: loads them and keeps
 * the wrappers of the renders in progress.
 *
 * @internal
 */
final class TemplateFunctions
{
	/** @var list<Contract\Wrapper> the innermost render's last */
	private static array $wrappers = [];
	private static bool $loaded = false;

	/**
	 * Runs a render, during which `w()` wraps values with its wrapper.
	 *
	 * @param Closure(): string $render
	 */
	public static function run(Contract\Wrapper $wrapper, Closure $render): string
	{
		self::load();
		self::$wrappers[] = $wrapper;

		try {
			return $render();
		} finally {
			array_pop(self::$wrappers);
		}
	}

	/** The wrapper of the innermost render in progress, if any. */
	public static function wrapper(): ?Contract\Wrapper
	{
		return self::$wrappers === [] ? null : self::$wrappers[array_key_last(self::$wrappers)];
	}

	private static function load(): void
	{
		if (self::$loaded) {
			return;
		}

		$file = __DIR__ . '/functions.php';
		$defined = false;

		foreach (['u', 'w'] as $name) {
			if (!function_exists($name)) {
				continue;
			}

			$origin = (string) new ReflectionFunction($name)->getFileName();

			// Preloading may have loaded Boiler's own file already.
			if (realpath($origin) !== $file) {
				throw new LogicException(
					"Boiler's template function {$name}() clashes with the global function {$name}() in {$origin}",
				);
			}

			$defined = true;
		}

		if (!$defined) {
			require $file;
		}

		self::$loaded = true;
	}
}
