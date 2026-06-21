<?php

declare(strict_types=1);

namespace Celemas\Boiler;

use Celemas\Boiler\Exception\LookupException;
use Celemas\Boiler\Exception\RenderException;
use Celemas\Boiler\Exception\RuntimeException;
use Celemas\Boiler\Exception\UnexpectedValueException;
use Closure;
use Throwable;

abstract class BaseTemplate
{
	private ?LayoutSpec $layout = null;
	private Methods $methods;
	private ?SlotRenderer $slot = null;
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

	public function __construct(
		public readonly string $path,
		?Sections $sections = null,
		?Engine $engine = null,
	) {
		$this->ownsSections = $sections === null;
		$this->sections = $sections ?? new Sections();
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

	/**
	 * @param list<class-string> $trusted
	 * @psalm-suppress PossiblyUnusedMethod Called through inherited API on concrete templates
	 */
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
	public function setSlot(
		Closure|Slot|null $slot,
		Context $context,
		?Location $location = null,
	): void {
		$this->slot = $slot === null
			? null
			: new SlotRenderer($slot, $context, $location ?? new Location($this->path));
	}

	/** @internal */
	public function slot(): ?SlotRenderer
	{
		return $this->slot;
	}

	/** @param list<class-string> $trusted */
	private function renderIsolated(array $context, array $trusted, bool $autoescape): string
	{
		$this->resetRenderState();

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
	abstract protected function context(
		array $context,
		array $trusted,
		bool $autoescape,
	): Context;

	/** @param list<class-string> $trusted */
	final protected function renderTemplate(array $context, array $trusted, bool $autoescape): string
	{
		$content = $this->getContent($context, $trusted, $autoescape);

		if ($this instanceof Layout) {
			return $content->content;
		}

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
		$templateContext = $this->context($context, $trusted, $autoescape);

		/** @mago-expect lint:prefer-static-closure Closure::call() binds $this to the template context at runtime. */
		$load = function (string $templatePath, array $context = []): void {
			// Must stay non-static so Closure::call() can bind $this to the template context.
			// Hide $templatePath. Could be overwritten if $context['templatePath'] exists.
			$____template_path____ = $templatePath;

			extract($context, EXTR_SKIP);

			/** @psalm-suppress UnresolvableInclude */
			include $____template_path____;
		};

		$level = ob_get_level();
		$sections = $this->sections->checkpoint();

		try {
			ob_start();

			$load->call(
				$templateContext,
				$this->path,
				$autoescape
					? $templateContext->get()
					: $context,
			);
			$this->sections->assertClosed($sections);

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
		BaseTemplate $template,
		Context $context,
		array $trusted,
		string $content,
		bool $autoescape,
	): string {
		while (($layout = $template->layout) !== null) {
			try {
				$file = $template->engine->resolve($layout->path);
			} catch (LookupException|UnexpectedValueException $e) {
				self::throwLayoutException($layout, $e);
			}

			$methods = $template->methods();
			$template = new Layout(
				$file,
				$content,
				$this->sections,
				$template->engine,
			);
			$template->setMethods($methods);

			$layoutContext = $context->get($layout->context);

			$content = $template->renderTemplate($layoutContext, $trusted, $autoescape);
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
