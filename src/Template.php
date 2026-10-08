<?php

declare(strict_types=1);

namespace Celema\Boiler;

use Celema\Boiler\Exception\LookupException;
use Celema\Boiler\Exception\RenderException;
use Celema\Boiler\Exception\RuntimeException;
use Celema\Boiler\Exception\UnexpectedValueException;
use Closure;
use Throwable;

/** @api */
final class Template
{
	private ?LayoutSpec $layout = null;
	private Methods $methods;
	/** what the template prints with `$this->slot()`: a layout's page or a component's block */
	private ?string $slot = null;
	private readonly bool $ownsSections;

	public private(set) Engine $engine {
		get => $this->engine;
		set(Engine $value) => $this->engine = $value;
	}
	/** @internal */
	public private(set) Sections $sections {
		get => $this->sections;
		set(Sections $value) => $this->sections = $value;
	}
	/** @internal the blocks open in this template's current render */
	public private(set) Blocks $blocks;

	public function __construct(
		public readonly string $path,
		?Sections $sections = null,
		?Engine $engine = null,
	) {
		$this->ownsSections = $sections === null;
		$this->sections = $sections ?? new Sections();
		$this->blocks = new Blocks();
		$this->methods = new Methods();

		if ($engine === null) {
			$dir = dirname($path);

			if ($dir === '' || $path === '') {
				throw new LookupException('No directory given or empty path');
			}

			$this->engine = new Engine(new Resolver($dir), new Environment(), true);

			if (!is_file($path)) {
				throw new LookupException('Template not found: ' . $path);
			}

			return;
		}

		$this->engine = $engine;
	}

	/** @param list<class-string> $trusted */
	public function render(array $context = [], array $trusted = []): string
	{
		return $this->renderIsolated($context, $trusted, autoescape: $this->engine->autoescape);
	}

	/** @param list<class-string> $trusted */
	public function renderEscaped(array $context = [], array $trusted = []): string
	{
		return $this->renderIsolated($context, $trusted, autoescape: true);
	}

	/** @param list<class-string> $trusted */
	public function renderUnescaped(array $context = [], array $trusted = []): string
	{
		return $this->renderIsolated($context, $trusted, autoescape: false);
	}

	/** @param non-empty-string $name */
	public function method(string $name, callable $callable, bool $safe = false): static
	{
		$this->methods()->add($name, $callable, $safe);

		return $this;
	}

	/**
	 * Defines a layout template that will be wrapped around this instance.
	 *
	 * Typically it’s placed at the top of the file.
	 *
	 * @internal
	 */
	public function setLayout(LayoutSpec $layout): void
	{
		if ($this->layout === null) {
			$this->layout = $layout;

			return;
		}

		throw new RuntimeException('Template error: layout already set');
	}

	/** @internal */
	public function setMethods(Methods $methods): void
	{
		$this->methods = $methods;
	}

	/** @internal */
	public function methods(): Methods
	{
		return $this->methods;
	}

	/** @internal */
	public function setSlot(string $slot): void
	{
		$this->slot = $slot;
	}

	/** @internal */
	public function slot(): ?string
	{
		return $this->slot;
	}

	/**
	 * Where this template's code currently runs, for errors raised by helpers.
	 *
	 * @internal
	 */
	public function location(): Location
	{
		return Location::fromBacktrace($this->path);
	}

	/**
	 * Captures this template's output up to the matching `end()`.
	 *
	 * @internal
	 * @param Closure(string): void $close receives the captured output
	 */
	public function capture(string $kind, string $name, Closure $close): void
	{
		$this->blocks->open($kind, $name, $this->location(), $close);
	}

	/**
	 * A template for `$path` that shares this one's sections, engine, and methods.
	 *
	 * @internal
	 * @param non-empty-string $path
	 */
	public function partial(string $path): self
	{
		$template = new self($this->engine->resolve($path), $this->sections, $this->engine);
		$template->setMethods($this->methods);

		return $template;
	}

	/**
	 * Renders this template inside another one, with the trusted classes and
	 * escaping of that one's render.
	 *
	 * @internal
	 * @param list<class-string> $trusted
	 */
	public function renderPartial(array $context, array $trusted, bool $autoescape): string
	{
		return $this->renderIsolated($context, $trusted, $autoescape);
	}

	/** @param list<class-string> $trusted */
	private function renderIsolated(array $context, array $trusted, bool $autoescape): string
	{
		try {
			return $this->renderTemplate($context, $trusted, $autoescape);
		} finally {
			$this->resetRenderState();
		}
	}

	private function resetRenderState(): void
	{
		$this->layout = null;

		if ($this->ownsSections) {
			$this->sections = new Sections();
		}
	}

	/** @param list<class-string> $trusted */
	private function renderTemplate(array $context, array $trusted, bool $autoescape): string
	{
		$content = $this->getContent($context, $trusted, $autoescape);

		return $this->renderLayouts(
			$this,
			$content->templateContext,
			$trusted,
			$content->content,
			$autoescape,
		);
	}

	/** @param list<class-string> $trusted */
	private function getContent(array $context, array $trusted, bool $autoescape): Content
	{
		$templateContext = new Context($this, $context, $trusted, $autoescape);

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
		$this->blocks = new Blocks();

		try {
			ob_start();

			$load->call(
				$templateContext,
				$this->path,
				$autoescape
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

	/** @param list<class-string> $trusted */
	private function renderLayouts(
		Template $template,
		Context $context,
		array $trusted,
		string $content,
		bool $autoescape,
	): string {
		// An inserted template can have layouts too; the template that
		// inserts it continues at its own level.
		$base = $this->sections->level();
		$level = $base;

		try {
			while (($layout = $template->layout) !== null) {
				try {
					$file = $template->engine->resolve($layout->path);
				} catch (LookupException|UnexpectedValueException $e) {
					self::throwLayoutException($layout, $e);
				}

				$methods = $template->methods();
				$template = new Template($file, $this->sections, $template->engine);
				$template->setMethods($methods);
				$template->setSlot($content);
				$this->sections->setLevel(++$level);

				$rendered = $template->getContent($context->get($layout->context), $trusted, $autoescape);
				$content = $rendered->content;
				// The next layout builds on this one's context, like an insert
				// builds on the context of the template that calls it.
				$context = $rendered->templateContext;
			}
		} finally {
			$this->sections->setLevel($base);
		}

		return $content;
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
