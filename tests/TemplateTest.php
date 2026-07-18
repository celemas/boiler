<?php

declare(strict_types=1);

namespace Celema\Boiler\Tests;

use Celema\Boiler\Exception\LookupException;
use Celema\Boiler\Exception\RenderException;
use Celema\Boiler\Template;

final class TemplateTest extends TestCase
{
	private string $templates;

	protected function setUp(): void
	{
		$this->templates = __DIR__ . '/templates/default/';
	}

	public function testStandaloneRendering(): void
	{
		$path = $this->templates . 'simple.php';
		$template = new Template($path);

		$this->assertSame(
			'<h1>boiler</h1><p>rocks</p>',
			$this->fullTrim($template->render([
				'obj' => $this->obj(),
				'text' => 'rocks',
			])),
		);
	}

	public function testTrustedValueStaysUnwrapped(): void
	{
		$path = $this->templates . 'trusted.php';
		$template = new Template($path);

		$this->assertSame(
			'<h1>headline</h1><p>test</p>',
			$this->fullTrim($template->render(
				[
					'wl' => new TrustedValue(),
					'content' => 'test',
				],
				[TrustedValue::class],
			)),
		);
	}

	public function testTrustedBaseClassStaysUnwrapped(): void
	{
		$path = $this->templates . 'trusted.php';
		$template = new Template($path);

		$this->assertSame(
			'<h1>headline</h1><p>test</p>',
			$this->fullTrim($template->render(
				[
					'wl' => new TrustedValue(),
					'content' => 'test',
				],
				[TrustedBase::class],
			)),
		);
	}

	public function testUntrustedValueIsWrapped(): void
	{
		$path = $this->templates . 'trusted.php';
		$template = new Template($path);

		$this->assertSame(
			'&lt;h1&gt;headline&lt;/h1&gt;&lt;p&gt;test&lt;/p&gt;',
			$this->fullTrim($template->render(
				[
					'wl' => new TrustedValue(),
					'content' => 'test',
				],
			)),
		);
	}

	public function testStandaloneWithLayout(): void
	{
		$path = $this->templates . 'uselayout.php';
		$template = new Template($path);

		$this->assertSame(
			'<body><p>standalone</p><p>standalone</p></body>',
			$this->fullTrim($template->render(['text' => 'standalone'])),
		);
	}

	public function testStandaloneTemplateCanBeRenderedMultipleTimes(): void
	{
		$template = new Template($this->templates . 'addsection.php');

		$this->assertSame(
			'<div><p>first</p>first</div><ul><li>first</li></ul>',
			$this->fullTrim($template->render(['text' => 'first'])),
		);
		$this->assertFalse($template->sections->has('list'));

		$this->assertSame(
			'<div><p>second</p>second</div><ul><li>second</li></ul>',
			$this->fullTrim($template->render(['text' => 'second'])),
		);
		$this->assertFalse($template->sections->has('list'));
	}

	public function testOverwriteLayoutContext(): void
	{
		$template = new Template($this->templates . 'overridelayoutcontext.php');

		$this->assertSame(
			'<body><p>Boiler 1</p><p>Boiler 2</p><p>changed</p><p>Boiler 2</p></body>',
			$this->fullTrim($template->render([
				'text' => 'Boiler 1',
				'text2' => 'Boiler 2',
			])),
		);
	}

	public function testNonExistentLayoutWithoutExtension(): void
	{
		$this->throws(LookupException::class, 'Template not found:.*doesnotexist');

		$template = new Template($this->templates . 'nonexistentlayout.php');

		$template->render();
	}

	public function testNonExistentLayoutWithExtension(): void
	{
		$this->throws(LookupException::class, 'Template not found:.*doesnotexist.php');

		$template = new Template($this->templates . 'nonexistentlayoutext.php');

		$template->render();
	}

	public function testCustomTemplateMethod(): void
	{
		$template = new Template($this->templates . 'method.php');
		$template->method(
			'upper',
			static fn(string $value): string => '<b>' . strtoupper($value) . '</b>',
		);

		$this->assertSame(
			'<h2>&lt;b&gt;BOILER&lt;/b&gt;</h2>',
			$this->fullTrim($template->render(['text' => 'Boiler'])),
		);
	}

	public function testSafeCustomTemplateMethod(): void
	{
		$template = new Template($this->templates . 'method.php');
		$template->method(
			'upper',
			static fn(string $value): string => '<b>' . strtoupper($value) . '</b>',
			safe: true,
		);

		$this->assertSame(
			'<h2><b>BOILER</b></h2>',
			$this->fullTrim($template->render(['text' => 'Boiler'])),
		);
	}

	public function testNonExistentTemplateWithoutExtension(): void
	{
		$this->throws(LookupException::class, 'Template not found');

		$template = new Template($this->templates . 'nonexistent');

		$template->render();
	}

	public function testDirectoryNotFound(): void
	{
		$this->throws(LookupException::class, 'Template directory does not exist');

		$template = new Template('/__nonexistent_boiler_dir__/template.php');

		$template->render();
	}

	public function testEmptyPath(): void
	{
		$this->throws(LookupException::class, 'No directory given or');

		$template = new Template('');

		$template->render();
	}

	public function testRenderError(): void
	{
		$this->throws(RenderException::class, 'Template rendering error');

		$template = new Template($this->templates . 'rendererror.php');

		$template->render();
	}

	public function testUnclosedSectionCaptureThrowsRenderError(): void
	{
		$this->throws(RenderException::class, 'Unclosed section capture block');

		$template = new Template($this->templates . 'unclosedsection.php');

		$template->render();
	}
}
