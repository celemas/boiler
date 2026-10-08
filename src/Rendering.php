<?php

declare(strict_types=1);

namespace Celema\Boiler;

use Celema\Boiler\Exception\LookupException;
use Celema\Boiler\Exception\RenderException;
use Celema\Boiler\Exception\RuntimeException;
use Celema\Boiler\Exception\UnexpectedValueException;
use Closure;
use Stringable;
use Throwable;

/**
 * One run of a template file during a render: the rendered template, one of
 * its layouts, or an inserted template. It holds the state that the
 * template's helpers act on and shares the rest with the other runs of the
 * render. A new render starts new runs, so a Template can render again, even
 * from within its own render.
 *
 * @internal
 */
final class Rendering
{
	/** the blocks open in this run */
	public readonly Blocks $blocks;
	private ?LayoutSpec $layout = null;

	/**
	 * @mago-expect lint:excessive-parameter-list The runs of a render share all but the path and slot, which with() copies.
	 *
	 * @param list<class-string> $trusted
	 * @param ?string $slot what the template prints with `$this->slot()`: a layout's page or a component's block
	 */
	public function __construct(
		public readonly string $path,
		public readonly Engine $engine,
		public readonly Methods $methods,
		public readonly Sections $sections,
		public readonly array $trusted,
		public readonly bool $autoescape,
		public readonly ?string $slot = null,
	) {
		$this->blocks = new Blocks();
	}

	/** Runs the template and wraps its output in its layouts, innermost first. */
	public function render(array $context): string
	{
		$rendering = $this;
		$content = $this->run($context);

		while (($layout = $rendering->layout) !== null) {
			try {
				$file = $this->engine->resolve($layout->path);
			} catch (LookupException|UnexpectedValueException $e) {
				self::throwLayoutException($layout, $e);
			}

			$rendering = $this->with($file, $content->content);
			$this->sections->enterLayout();
			// A layout builds on the context of the template it wraps, like an
			// insert builds on the context of the template that calls it.
			$content = $rendering->run($content->templateContext->get($layout->context));
		}

		return $content->content;
	}

	/**
	 * Renders another template file within this render, for an insert or a
	 * component.
	 *
	 * @param non-empty-string $file
	 */
	public function insert(string $file, array $context, ?string $slot = null): string
	{
		return $this->sections->nest(fn(): string => $this->with($file, $slot)->render($context));
	}

	/**
	 * Defines the layout that wraps this run's output.
	 */
	public function setLayout(LayoutSpec $layout): void
	{
		if ($this->layout === null) {
			$this->layout = $layout;

			return;
		}

		throw new RuntimeException('Template error: layout already set');
	}

	/** Where the template's code currently runs, for errors raised by helpers. */
	public function location(): Location
	{
		return Location::fromBacktrace($this->path);
	}

	/**
	 * Captures the template's output up to the matching `end()`.
	 *
	 * @param Closure(string, Location): void $close receives the captured output and where the block opened
	 */
	public function capture(string $kind, string $name, Closure $close): void
	{
		$this->blocks->open($kind, $name, $this->location(), $close);
	}

	/**
	 * Returns what a closure of the template prints. It must print rather
	 * than return, like a block, so a returned string fails instead of
	 * vanishing.
	 *
	 * @param Closure(): mixed $print
	 */
	public function output(Closure $print): string
	{
		$level = ob_get_level();

		try {
			ob_start();
			/** @var mixed $result */
			$result = $print();
			$output = (string) ob_get_clean();
		} finally {
			while (ob_get_level() > $level) {
				ob_end_clean();
			}
		}

		if (is_string($result) || $result instanceof Stringable) {
			throw new UnexpectedValueException(
				'A closure passed to yield() must print its content, not return it',
				location: $this->location(),
			);
		}

		return $output;
	}

	private function with(string $path, ?string $slot): self
	{
		return new self(
			$path,
			$this->engine,
			$this->methods,
			$this->sections,
			$this->trusted,
			$this->autoescape,
			$slot,
		);
	}

	private function run(array $context): Content
	{
		$templateContext = new Context($this, $context);

		/** @mago-expect lint:prefer-static-closure Closure::call() binds $this to the template context at runtime. */
		$load = function (string $____template_path____, array $____template_context____): void {
			// Must stay non-static so Closure::call() can bind $this to the template context.
			// extract() skips names that already exist, so the parameter names
			// are obscure to leave common names such as `$context` to the caller.
			extract($____template_context____, EXTR_SKIP);

			/** @psalm-suppress UnresolvableInclude */
			include $____template_path____;
		};

		$level = ob_get_level();

		try {
			ob_start();

			$load->call(
				$templateContext,
				$this->path,
				$this->autoescape
					? $templateContext->get()
					: $context,
			);
			$this->blocks->assertClosed();

			$content = (string) ob_get_clean();

			return new Content($content, $templateContext);
		} catch (RenderException $e) {
			throw $e;
		} catch (Throwable $e) {
			throw RenderException::fromThrowable($this->path, $e);
		} finally {
			while (ob_get_level() > $level) {
				ob_end_clean();
			}
		}
	}

	private static function throwLayoutException(
		LayoutSpec $layout,
		LookupException|UnexpectedValueException $exception,
	): never {
		$location = $layout->location;
		$message = $exception->getMessage() . " (referenced at {$location})";

		if ($exception instanceof LookupException) {
			throw new LookupException($message, $exception->getCode(), $exception, $location);
		}

		throw new UnexpectedValueException($message, $exception->getCode(), $exception, $location);
	}
}
