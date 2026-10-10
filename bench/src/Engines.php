<?php

declare(strict_types=1);

namespace Celema\Boiler\Bench;

use Celema\Boiler\Engine as Boiler;
use Illuminate\Container\Container;
use Illuminate\Contracts\View\Factory as FactoryContract;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Component;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory as Blade;
use Illuminate\View\FileViewFinder;
use League\Plates\Engine as Plates;
use Twig\Environment as Twig;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Sets up every engine the way an application would: site-wide data and the
 * helpers are registered with the engine, the page data is passed per render.
 */
final class Engines
{
	/**
	 * @param string $dir the benchmark directory with one template directory per candidate
	 * @param array<string, mixed> $shared
	 *
	 * @return list<Candidate<object>>
	 */
	public static function all(string $dir, array $shared): array
	{
		return [
			new Candidate(
				'twig',
				'Twig',
				true,
				static fn(): Twig => self::twig($dir, $shared),
				static fn(Twig $engine, string $page, array $context): string => $engine->render(
					$page . '.html.twig',
					$context,
				),
			),
			new Candidate(
				'blade',
				'Blade',
				true,
				static fn(): Blade => self::blade($dir, $shared),
				static fn(Blade $engine, string $page, array $context): string => $engine
					->make($page, $context)
					->render(),
			),
			new Candidate(
				'boiler',
				'Boiler',
				true,
				static fn(): Boiler => self::helpers(Boiler::create($dir . '/boiler', $shared, [Html::class])),
				static fn(Boiler $engine, string $page, array $context): string => $engine->render($page, $context),
			),
			new Candidate(
				'plates',
				'Plates',
				false,
				static fn(): Plates => self::plates($dir, $shared),
				static fn(Plates $engine, string $page, array $context): string => $engine->render($page, $context),
			),
			new Candidate(
				'boiler-manual',
				'Boiler',
				false,
				static fn(): Boiler => self::helpers(Boiler::unescaped($dir . '/boiler-manual', $shared)),
				static fn(Boiler $engine, string $page, array $context): string => $engine->render($page, $context),
			),
		];
	}

	/** @return list<string> the directories that hold compiled templates */
	public static function caches(string $dir): array
	{
		return [$dir . '/cache/twig', $dir . '/cache/blade'];
	}

	private static function helpers(Boiler $engine): Boiler
	{
		return $engine
			->method('money', money(...))
			->method('icon', icon(...), safe: true);
	}

	/** @param array<string, mixed> $shared */
	private static function twig(string $dir, array $shared): Twig
	{
		$twig = new Twig(new FilesystemLoader($dir . '/twig'), ['cache' => $dir . '/cache/twig']);
		$twig->addFilter(new TwigFilter('money', money(...)));
		$twig->addFunction(new TwigFunction('icon', icon(...), ['is_safe' => ['html']]));

		foreach ($shared as $name => $value) {
			$twig->addGlobal($name, $value);
		}

		return $twig;
	}

	/**
	 * Wires Laravel's view factory as its ViewServiceProvider does, minus the
	 * application container and the engines for plain PHP and static files.
	 *
	 * @param array<string, mixed> $shared
	 */
	private static function blade(string $dir, array $shared): Blade
	{
		$files = new Filesystem();
		$compiler = new BladeCompiler($files, $dir . '/cache/blade');
		// Laravel finds anonymous components through the application, which is
		// missing here, so the component is registered by name instead.
		$compiler->component('components.card', 'card');
		$resolver = new EngineResolver();
		$resolver->register('blade', static fn(): CompilerEngine => new CompilerEngine($compiler, $files));

		$container = new Container();
		$factory = new Blade(
			$resolver,
			new FileViewFinder($files, [$dir . '/blade']),
			new Dispatcher($container),
		);
		$factory->setContainer($container);
		$factory->share('app', $container);
		$factory->share($shared);

		// Components resolve the factory through the global container and keep
		// it in static caches, which Laravel resets at the end of a request.
		$container->instance(FactoryContract::class, $factory);
		$container->instance('view', $factory);
		Container::setInstance($container);
		Component::flushCache();
		Component::forgetFactory();

		return $factory;
	}

	/** @param array<string, mixed> $shared */
	private static function plates(string $dir, array $shared): Plates
	{
		$plates = new Plates($dir . '/plates');
		$plates->addData($shared);
		$plates->registerFunction('money', money(...));
		$plates->registerFunction('icon', icon(...));

		return $plates;
	}
}
