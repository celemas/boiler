<?php

declare(strict_types=1);

namespace Celema\Boiler\Tests;

use Celema\Boiler\Contract\Escaper;
use Celema\Boiler\Contract\Filter;
use Celema\Boiler\Engine;
use Celema\Boiler\Environment;
use Celema\Boiler\Escapers;
use Celema\Boiler\Exception\LookupException;
use Celema\Boiler\Exception\RenderException;
use Celema\Boiler\Exception\RuntimeException;
use Celema\Boiler\Exception\UnexpectedValueException;
use Celema\Boiler\Proxy\StringProxy;
use Celema\Boiler\Resolver;
use Celema\Boiler\Template;
use Celema\Boiler\TemplateContext;
use Celema\Boiler\Wrapper;
use PHPUnit\Framework\Attributes\TestDox;

final class EngineTest extends TestCase
{
	#[TestDox('Directory does not exist I')]
	public function testDirectoryDoesNotExistI(): void
	{
		$this->throws(LookupException::class, 'doesnotexist');

		Engine::create('./doesnotexist');
	}

	#[TestDox('Directory does not exist II')]
	public function testDirectoryDoesNotExistII(): void
	{
		$this->throws(LookupException::class, 'doesnotexist');

		Engine::create([TestCase::DEFAULT_DIR, './doesnotexist']);
	}

	public function testRejectsEmptyDirectoryList(): void
	{
		$this->throws(LookupException::class, 'At least one template directory must be configured');

		Engine::create([]);
	}

	public function testSimpleRendering(): void
	{
		$engine = Engine::create(TestCase::DEFAULT_DIR, ['obj' => $this->obj()]);

		$this->assertSame(
			'<h1>boiler</h1><p>rocks</p>',
			$this->fullTrim($engine->render('simple', ['text' => 'rocks'])),
		);
	}

	public function testConstructorAcceptsResolverAndEnvironmentWithoutDefaultsAndTrusted(): void
	{
		$engine = new Engine(new Resolver(TestCase::DEFAULT_DIR), new Environment(), true);

		$this->assertSame(
			'<h1>boiler</h1><p>rocks</p>',
			$this->fullTrim($engine->render('simple', [
				'obj' => $this->obj(),
				'text' => 'rocks',
			])),
		);
	}

	public function testCustomEnvironmentWrapperIsUsedDuringRendering(): void
	{
		$environment = new Environment();
		$environment->setWrapper(new Wrapper(new Escapers([
			'html' => new class implements Escaper {
				public function escape(string $value): string
				{
					return strtoupper(htmlspecialchars($value));
				}
			},
		])));
		$engine = new Engine(
			new Resolver(TestCase::DEFAULT_DIR),
			$environment,
			true,
			['obj' => $this->obj()],
		);

		$this->assertSame(
			'<h1>BOILER</h1><p>&LT;B&GT;ROCKS&LT;/B&GT;</p>',
			$this->fullTrim($engine->render('simple', ['text' => '<b>rocks</b>'])),
		);
	}

	public function testSimpleScalarValueRendering(): void
	{
		$engine = Engine::create(TestCase::DEFAULT_DIR, ['obj' => $this->obj()]);

		$this->assertSame(
			'<p>13</p><p>1</p><p>13.73</p><p></p><p>&lt;script&gt;&lt;/script&gt;</p>',
			$this->fullTrim($engine->render('scalar', [
				'int' => 13,
				'float' => 13.73,
				'null' => null,
				'bool' => true,
				'string' => '<script></script>',
			])),
		);
	}

	public function testSimpleRenderingNamespaced(): void
	{
		$engine = Engine::create($this->namespaced(), ['obj' => $this->obj()]);

		$this->assertSame(
			'<h1>boiler</h1><p>rocks</p>',
			$this->fullTrim($engine->render('namespace:simple', ['text' => 'rocks'])),
		);
	}

	public function testExtensionGiven(): void
	{
		$engine = Engine::create(self::DEFAULT_DIR, ['obj' => $this->obj()]);

		$this->assertSame('<p></p>', $this->fullTrim($engine->render('extension.tpl')));
	}

	public function testUnwrapRendering(): void
	{
		$engine = Engine::create(self::DEFAULT_DIR);

		$this->assertSame(
			'&lt;b&gt;boiler&lt;/b&gt;<b>boiler</b>',
			$engine->render('unwrap', ['html' => '<b>boiler</b>']),
		);
	}

	public function testSwitchOffAutoescapingByDefault(): void
	{
		$engine = Engine::unescaped(self::DEFAULT_DIR);

		$this->assertSame(
			'<b>noautoescape</b>',
			$engine->render('noautoescape', ['html' => '<b>noautoescape</b>']),
		);
	}

	public function testForceUnescapedRenderingWhenAutoEscapeIsDefault(): void
	{
		$engine = Engine::create(self::DEFAULT_DIR);

		$this->assertSame(
			'<b>nodefaultautoescape</b>',
			$engine->renderUnescaped(
				'noautoescape',
				['html' => '<b>nodefaultautoescape</b>'],
			),
		);
	}

	public function testForceEscapedRenderingWhenUnescapedIsDefault(): void
	{
		$engine = Engine::unescaped(self::DEFAULT_DIR);

		$this->assertSame(
			'&lt;b&gt;nodefaultautoescape&lt;/b&gt;',
			$engine->renderEscaped(
				'noautoescape',
				['html' => '<b>nodefaultautoescape</b>'],
			),
		);
	}

	public function testUnwrapRenderingWithStringable(): void
	{
		$engine = Engine::create($this->templates());

		$this->assertSame('&lt;b&gt;boiler&lt;/b&gt;<b>boiler</b>', $engine->render(
			'unwrap',
			['html' => new class {
				public function __toString(): string
				{
					return '<b>boiler</b>';
				}
			}],
		));
	}

	public function testRenderingWithStringable(): void
	{
		$engine = Engine::create($this->templates());
		$stringable = new class {
			public string $test = 'test';

			public function __toString(): string
			{
				return '<b>boiler</b>';
			}

			public function testMethod(string $value): string
			{
				return $value . $value;
			}
		};

		$this->assertSame(
			'&lt;b&gt;boiler&lt;/b&gt;<b>boiler</b>testmantasmantas',
			$this->fullTrim(
				$engine->render('stringable', ['html' => $stringable]),
			),
		);
	}

	public function testSanitizeRendering(): void
	{
		$engine = Engine::create(
			$this->templates(),
		);

		$this->assertSame(
			'<b>boiler</b>',
			$engine->render(
				'sanitize',
				['html' => '<script src="/evil.js"></script><b>boiler</b>'],
			),
		);
	}

	public function testArrayRendering(): void
	{
		$engine = Engine::create($this->templates());

		$this->assertSame(
			'&lt;b&gt;1&lt;/b&gt;&lt;b&gt;2&lt;/b&gt;&lt;b&gt;3&lt;/b&gt;',
			trim($engine->render(
				'iter',
				['arr' => ['<b>1</b>', '<b>2</b>', '<b>3</b>']],
			)),
		);
	}

	public function testHelperFunctionRendering(): void
	{
		$engine = Engine::create(
			$this->templates(),
			['obj' => $this->obj()],
		);

		$this->assertSame(
			'&lt;script&gt;<b>clean</b>',
			$this->fullTrim($engine->render('helper')),
		);
	}

	public function testHelperWrapWorksInUnescapedRendering(): void
	{
		$engine = Engine::unescaped(
			$this->templates(),
			['obj' => $this->obj()],
		);

		$this->assertSame(
			'&lt;script&gt;<b>clean</b>',
			$this->fullTrim($engine->render('helper')),
		);
	}

	public function testWrappedHelperStripRespectsEscaping(): void
	{
		$engine = Engine::create($this->templates());

		$this->assertSame(
			'boiler&lt;br&gt;plate',
			trim($engine->render('wrapstrip', ['html' => '<b>boiler<br>plate</b>'])),
		);
	}

	public function testSanitizeRenderingUsesBuiltinSanitizer(): void
	{
		$engine = Engine::create($this->templates());

		$this->assertSame(
			'<b>boiler</b>',
			$engine->render(
				'sanitize',
				['html' => '<script src="/evil.js"></script><b>boiler</b>'],
			),
		);
	}

	public function testUnescapedPlainStringsDoNotExposeFilterMethods(): void
	{
		$this->throws(RenderException::class, 'sanitize');

		$engine = Engine::unescaped($this->templates());

		$engine->render('sanitize', ['html' => '<script src="/evil.js"></script><b>boiler</b>']);
	}

	public function testRawHelperSupportsExplicitStringChecks(): void
	{
		$engine = Engine::create($this->templates());

		$this->assertSame(
			'&lt;b&gt;not empty&lt;/b&gt;',
			$this->fullTrim($engine->render(
				'empty',
				['empty' => '', 'notempty' => '<b>not empty</b>'],
			)),
		);
	}

	public function testEscapeAlreadyWrappedProxy(): void
	{
		$engine = Engine::create($this->templates());

		$this->assertSame(
			'<p>&lt;b&gt;wrapped&lt;/b&gt;</p>',
			$this->fullTrim($engine->render(
				'escapevalue',
				['wrapped' => '<b>wrapped</b>'],
			)),
		);
	}

	public function testEscapeAlwaysEscapesSafeWrappedProxy(): void
	{
		$engine = Engine::create($this->templates());

		$this->assertSame(
			'<p>&lt;b&gt;wrapped&lt;/b&gt;</p>',
			$this->fullTrim($engine->render(
				'escapesafevalue',
				['wrapped' => '<b>wrapped</b>'],
			)),
		);
	}

	public function testIteratorRendering(): void
	{
		$engine = Engine::create($this->templates());

		$iter = static function () {
			$array = ['<b>2</b>', '<b>3</b>', '<b>4</b>'];

			foreach ($array as $item) {
				yield $item;
			}
		};

		$this->assertSame(
			'&lt;b&gt;2&lt;/b&gt;&lt;b&gt;3&lt;/b&gt;&lt;b&gt;4&lt;/b&gt;',
			trim($engine->render(
				'iter',
				['arr' => $iter()],
			)),
		);
	}

	public function testComplexNestedRendering(): void
	{
		$engine = Engine::create(
			$this->templates(),
			['obj' => $this->obj()],
		);

		$iter = static function () {
			$array = [13.73, 'String II', 1];

			foreach ($array as $item) {
				yield $item;
			}
		};

		$context = [
			'title' => 'Boiler App',
			'headline' => 'Boiler App',
			'array' => [
				'<b>sanitize</b>' => [
					1,
					'String',
					new class {
						public function __toString(): string
						{
							return '<p>Object</p>';
						}
					},
				],
				666 => $iter(),
			],
			'html' => '<p>HTML</p>',
		];
		$result = $this->fullTrim($engine->render('complex', $context));
		$compare =
			'<!DOCTYPE html><html lang="en"><head><title>Boiler App</title>'
			. '<meta name="keywords" content="boiler"></head><body>'
			. '<h1>Boiler App</h1><table><tr><td>&lt;b&gt;sanitize&lt;/b&gt;</td><td>1</td><td>String</td>'
			. '<td>&lt;p&gt;Object&lt;/p&gt;</td></tr><tr><td>666</td><td>13.73</td><td>String II</td>'
			. '<td>1</td></tr></table><p>HTML</p></body></html>';

		$this->assertSame($compare, $result);
	}

	public function testSingleLayout(): void
	{
		$engine = Engine::create($this->templates());

		$this->assertSame(
			'<body><p>boiler</p><p>boiler</p></body>',
			$this->fullTrim($engine->render(
				'uselayout',
				['text' => 'boiler'],
			)),
		);
	}

	public function testNonExistentLayoutWithoutExtension(): void
	{
		$this->throws(LookupException::class, 'doesnotexist');

		$engine = Engine::create($this->templates());

		$engine->render('nonexistentlayout');
	}

	public function testNonExistentLayoutWithExtension(): void
	{
		$this->throws(LookupException::class, 'doesnotexist');

		$engine = Engine::create($this->templates());

		$engine->render('nonexistentlayoutext');
	}

	public function testStackedLayout(): void
	{
		$engine = Engine::create($this->templates());

		$this->assertSame(
			'<body><div class="stackedsecond"><div class="stackedfirst"><p>boiler</p></div></div><p>boiler</p></body>',
			$this->fullTrim($engine->render(
				'usestacked',
				['text' => 'boiler'],
			)),
		);
	}

	public function testMultilpleLayoutsError(): void
	{
		$this->throws(RuntimeException::class, 'layout already set');

		Engine::create($this->templates())->render('multilayout');
	}

	public function testSectionRendering(): void
	{
		$engine = Engine::create($this->templates());

		$this->assertSame(
			'<div><p>boiler</p>boiler</div><ul><li>boiler</li></ul>',
			$this->fullTrim($engine->render('addsection', ['text' => 'boiler'])),
		);
	}

	public function testRenderSectionWithDefaultValue(): void
	{
		$engine = Engine::create($this->templates());

		$this->assertSame(
			'<p>default value</p>',
			$this->fullTrim($engine->render('addsectiondefault', [])),
		);
	}

	public function testInsertRenderingInsideSection(): void
	{
		$engine = Engine::create($this->templates());

		$this->assertSame(
			'<div><p>boiler</p>boiler</div><ul><li>boiler</li></ul>',
			$this->fullTrim($engine->render('insertinsection', ['text' => 'boiler'])),
		);
	}

	public function testInsertCannotCloseParentSection(): void
	{
		$this->throws(RenderException::class, 'active output buffer');

		$engine = Engine::create($this->templates());

		$engine->render('closesectionfrominsert');
	}

	public function testAppendPrependToSection(): void
	{
		$engine = Engine::create($this->templates());

		$this->assertSame(
			'<script src="/prepend.js"></script><script src="/assign.js"></script><script src="/append.js"></script>',
			$this->fullTrim($engine->render('appendprepend', ['path' => '/assign.js'])),
		);
	}

	public function testAppendPrependToSectionWithDefaultValueAndOrder(): void
	{
		$engine = Engine::create($this->templates());

		$this->assertSame(
			'<prepend-first><prepend><default><append><append-last>',
			$this->fullTrim($engine->render(
				'appendprependdefault',
				['path' => '/assign.js'],
			)),
		);
	}

	public function testNestedSectionsError(): void
	{
		$this->throws(RenderException::class);

		$engine = Engine::create($this->templates());

		$engine->render('nestedsections');
	}

	public function testUnclosedSectionError(): void
	{
		$this->throws(RenderException::class, 'Unclosed section capture block');

		$engine = Engine::create($this->templates());

		$engine->render('unclosedsection');
	}

	public function testClosingUnopenedSectionError(): void
	{
		$this->throws(RenderException::class);

		$engine = Engine::create($this->templates());

		$engine->render('closeunopened');
	}

	public function testMissingSectionRendering(): void
	{
		$engine = Engine::create($this->templates());

		$this->assertSame(
			'<div><p>boiler</p>boiler</div><p>no list</p>',
			$this->fullTrim($engine->render('nosection', ['text' => 'boiler'])),
		);
	}

	public function testInsertRendering(): void
	{
		$engine = Engine::create($this->templates());

		$this->assertSame(
			'<p>Boiler</p><p>73</p><p>Boiler</p><p>23</p><p>&lt;b&gt;Overwrite&lt;/b&gt;</p><p>13</p>',
			$this->fullTrim($engine->render('insert', ['text' => 'Boiler', 'int' => 73])),
		);
	}

	public function testInsertUnescapedRendering(): void
	{
		$engine = Engine::unescaped($this->templates());

		$this->assertSame(
			'<p>Boiler</p><p>73</p><p>Boiler</p><p>23</p><p><b>Overwrite</b></p><p>13</p>',
			$this->fullTrim($engine->render('insert', ['text' => 'Boiler', 'int' => 73])),
		);
	}

	public function testTemplateInSubDirectory(): void
	{
		$engine = Engine::create($this->templates());

		$this->assertSame(
			'<h2>Boiler</h2>',
			$this->fullTrim($engine->render('sub/home', ['text' => 'Boiler'])),
		);
	}

	public function testAdditionalTemplateDirectories(): void
	{
		$engine = Engine::create(
			$this->templates($this->additional()),
			['obj' => $this->obj()],
		);

		$this->assertSame(
			'<h1>boiler</h1><p>rocks</p>',
			$this->fullTrim($engine->render('simple', ['text' => 'rocks'])),
		);
		$this->assertSame(
			'<span>Additional</span>',
			$this->fullTrim($engine->render('additional', ['text' => 'Additional'])),
		);
	}

	public function testAdditionalTemplateDirectoriesNamespaced(): void
	{
		$engine = Engine::create($this->namespaced($this->additional()));

		$this->assertSame(
			'<h2>Boiler</h2>',
			$this->fullTrim($engine->render('namespace:sub/home', ['text' => 'Boiler'])),
		);
		$this->assertSame(
			'<span>Additional</span>',
			$this->fullTrim($engine->render('additional:additional', ['text' => 'Additional'])),
		);
	}

	public function testAdditionalTemplateDirectoriesShadowing(): void
	{
		$engine = Engine::create($this->namespaced());

		$this->assertSame(
			'<h2>Boiler</h2>',
			$this->fullTrim($engine->render('sub/home', ['text' => 'Boiler'])),
		);

		$engine = Engine::create($this->namespaced($this->additional()));

		$this->assertSame(
			'<h1>Sub Boiler</h1>',
			$this->fullTrim($engine->render('sub/home', ['text' => 'Boiler'])),
		);
		$this->assertSame(
			'<h2>Boiler</h2>',
			$this->fullTrim($engine->render('namespace:sub/home', ['text' => 'Boiler'])),
		);
		$this->assertSame(
			'<h1>Sub Boiler</h1>',
			$this->fullTrim($engine->render('additional:sub/home', ['text' => 'Boiler'])),
		);
	}

	public function testExistsHelper(): void
	{
		$engine = Engine::create($this->templates());

		$this->assertSame(true, $engine->exists('simple'));
		$this->assertSame(false, $engine->exists('wrongindex'));
	}

	public function testResolveReturnsResolvedTemplatePath(): void
	{
		$engine = Engine::create($this->templates());

		$this->assertStringEndsWith(
			'tests/templates/default/simple.php',
			$engine->resolve('simple'),
		);
	}

	public function testConstructorAcceptsCustomResolver(): void
	{
		$resolver = new class(TestCase::DEFAULT_DIR . '/simple.php') implements
			\Celema\Boiler\Contract\Resolver {
			public int $calls = 0;

			public function __construct(
				private readonly string $resolved,
			) {}

			public function resolve(string $path): string
			{
				$this->calls++;

				if ($path !== 'simple') {
					throw new LookupException("Template `{$path}` not found");
				}

				return $this->resolved;
			}
		};

		$engine = new Engine($resolver, new Environment(), true, ['obj' => $this->obj()]);

		$this->assertSame(
			'<h1>boiler</h1><p>first</p>',
			$this->fullTrim($engine->render('simple', ['text' => 'first'])),
		);
		$this->assertSame(1, $resolver->calls);

		$engine->resolve('simple');
		$this->assertSame(2, $resolver->calls);
	}

	public function testEngineIsFinal(): void
	{
		$this->assertTrue(new \ReflectionClass(Engine::class)->isFinal());
	}

	public function testTemplateIsFinal(): void
	{
		$this->assertTrue(new \ReflectionClass(Template::class)->isFinal());
	}

	public function testTemplateContextIsFinal(): void
	{
		$this->assertTrue(new \ReflectionClass(TemplateContext::class)->isFinal());
	}

	#[TestDox('Config error wrong template format I')]
	public function testConfigErrorWrongTemplateFormatI(): void
	{
		$this->throws(LookupException::class, 'Invalid template format');

		$engine = Engine::create($this->templates());

		$engine->render('default:sub:index');
	}

	#[TestDox('Config error wrong template format II')]
	public function testConfigErrorWrongTemplateFormatII(): void
	{
		$this->throws(LookupException::class, 'Invalid template format');

		$engine = Engine::create($this->templates());

		$engine->render(':default.php');
	}

	#[TestDox('Config error wrong template format III')]
	public function testConfigErrorWrongTemplateFormatIII(): void
	{
		$this->throws(LookupException::class, 'Invalid template format');

		$engine = Engine::create($this->templates());

		$engine->render('default.php:');
	}

	#[TestDox('Config error wrong template format IV')]
	public function testConfigErrorWrongTemplateFormatIV(): void
	{
		$this->throws(UnexpectedValueException::class, 'invalid or empty');

		$engine = Engine::create($this->templates());

		$engine->render('');
	}

	#[TestDox('Config error wrong template format V')]
	public function testConfigErrorWrongTemplateFormatV(): void
	{
		$this->throws(UnexpectedValueException::class, 'invalid or empty');

		$engine = Engine::create($this->templates());

		$engine->render("\0");
	}

	public function testRenderErrorMissingTemplate(): void
	{
		$this->throws(LookupException::class, 'not found');

		$engine = Engine::create($this->templates());

		$engine->render('nonexistent');
	}

	public function testNamespaceDoesNotExist(): void
	{
		$this->throws(LookupException::class, 'Template namespace');

		$engine = Engine::create($this->namespaced());

		$engine->render('doesnotexist:sub/home');
	}

	#[TestDox('Render error template outside root directory I')]
	public function testRenderErrorTemplateOutsideRootDirectoryI(): void
	{
		$this->throws(LookupException::class, 'not found');

		$engine = Engine::create($this->templates());

		$engine->render('.././../.././../etc/passwd');
	}

	#[TestDox('Render error template outside root directory II')]
	public function testRenderErrorTemplateOutsideRootDirectoryII(): void
	{
		$this->throws(LookupException::class, 'outside');

		$engine = Engine::create($this->templates());

		$engine->render('../unreachable');
	}

	public function testRenderErrorParseError(): void
	{
		$this->throws(RenderException::class);

		$engine = Engine::create($this->templates());

		$engine->render('failing');
	}

	public function testRenderExceptionReportsLocation(): void
	{
		$path = self::DEFAULT_DIR . '/unknownmethod.php';

		try {
			Engine::create($this->templates())->render('unknownmethod');
			$this->fail('RenderException was not thrown');
		} catch (RenderException $e) {
			$this->assertSame($path, $e->getFile());
			$this->assertSame(1, $e->getLine());
			$this->assertSame($path, $e->location()?->path);
			$this->assertSame(1, $e->location()?->line);
			$this->assertStringContainsString($path . ':1', $e->getMessage());
			$this->assertNotNull($e->getPrevious());
		}
	}

	public function testRenderExceptionReportsInsertedLocation(): void
	{
		$path = self::DEFAULT_DIR . '/closesection.php';

		try {
			Engine::create($this->templates())->render('closesectionfrominsert');
			$this->fail('RenderException was not thrown');
		} catch (RenderException $e) {
			$this->assertSame($path, $e->getFile());
			$this->assertSame(1, $e->getLine());
			$this->assertSame($path, $e->location()?->path);
			$this->assertSame(1, $e->location()?->line);
		}
	}

	public function testRenderExceptionReportsUnclosedSectionLocation(): void
	{
		$path = self::DEFAULT_DIR . '/unclosedsection.php';

		try {
			Engine::create($this->templates())->render('unclosedsection');
			$this->fail('RenderException was not thrown');
		} catch (RenderException $e) {
			$this->assertSame($path, $e->getFile());
			$this->assertSame(1, $e->getLine());
			$this->assertStringContainsString('Unclosed section capture block `scripts`', $e->getMessage());
			$this->assertStringContainsString($path . ':1', $e->getMessage());
		}
	}

	public function testLayoutLookupReportsDeclarationLocation(): void
	{
		$path = self::DEFAULT_DIR . '/nonexistentlayout.php';

		try {
			Engine::create($this->templates())->render('nonexistentlayout');
			$this->fail('LookupException was not thrown');
		} catch (LookupException $e) {
			$this->assertSame($path, $e->getFile());
			$this->assertSame(5, $e->getLine());
			$this->assertSame($path, $e->location()?->path);
			$this->assertSame(5, $e->location()?->line);
			$this->assertStringContainsString($path . ':5', $e->getMessage());
		}
	}

	public function testInvalidLayoutLookupReportsDeclarationLocation(): void
	{
		$path = self::DEFAULT_DIR . '/invalidlayout.php';

		try {
			Engine::create($this->templates())->render('invalidlayout');
			$this->fail('UnexpectedValueException was not thrown');
		} catch (UnexpectedValueException $e) {
			$this->assertSame($path, $e->getFile());
			$this->assertSame(1, $e->getLine());
			$this->assertSame($path, $e->location()?->path);
			$this->assertSame(1, $e->location()?->line);
			$this->assertStringContainsString($path . ':1', $e->getMessage());
		}
	}

	public function testCustomTemplateMethod(): void
	{
		$engine = Engine::create($this->templates());
		$engine->method(
			'upper',
			static fn(string $value): string => '<b>' . strtoupper($value) . '</b>',
		);

		$this->assertSame(
			'<h2>&lt;b&gt;BOILER&lt;/b&gt;</h2>',
			$this->fullTrim($engine->render('method', ['text' => 'Boiler'])),
		);
	}

	public function testSafeCustomTemplateMethod(): void
	{
		$engine = Engine::create($this->templates());
		$engine->method(
			'upper',
			static fn(string $value): string => '<b>' . strtoupper($value) . '</b>',
			safe: true,
		);

		$this->assertSame(
			'<h2><b>BOILER</b></h2>',
			$this->fullTrim($engine->render('method', ['text' => 'Boiler'])),
		);
	}

	public function testSafeCustomTemplateMethodAcceptsStringProxyReturn(): void
	{
		$engine = Engine::create($this->templates());
		$engine->method(
			'upper',
			fn(string $value): StringProxy => $this->stringProxy(
				'<b>' . strtoupper($value) . '</b>',
			),
			safe: true,
		);

		$this->assertSame(
			'<h2><b>BOILER</b></h2>',
			$this->fullTrim($engine->render('method', ['text' => 'Boiler'])),
		);
	}

	public function testCustomTemplateMethodRespectsUnescapedRendering(): void
	{
		$engine = Engine::unescaped($this->templates());
		$engine->method(
			'upper',
			static fn(string $value): string => '<b>' . strtoupper($value) . '</b>',
		);

		$this->assertSame(
			'<h2><b>BOILER</b></h2>',
			$this->fullTrim($engine->render('method', ['text' => 'Boiler'])),
		);
	}

	public function testCustomTemplateMethodIsAvailableInInsertedTemplates(): void
	{
		$engine = Engine::create($this->templates())
			->method('upper', static fn(string $value): string => strtoupper($value));

		$this->assertSame(
			'<p>BOILER</p><p>13</p>',
			$this->fullTrim($engine->render('insertmethod', ['text' => 'Boiler'])),
		);
	}

	public function testSafeCustomTemplateMethodIsAvailableInInsertedTemplates(): void
	{
		$engine = Engine::create($this->templates())
			->method(
				'upper',
				static fn(string $value): string => '<b>' . strtoupper($value) . '</b>',
				safe: true,
			);

		$this->assertSame(
			'<p><b>BOILER</b></p><p>13</p>',
			$this->fullTrim($engine->render('insertmethod', ['text' => 'Boiler'])),
		);
	}

	public function testCustomTemplateMethodIsAvailableInLayouts(): void
	{
		$engine = Engine::create($this->templates())
			->method('upper', static fn(string $value): string => strtoupper($value));

		$this->assertSame(
			'<body><p>BOILER</p></body>',
			$this->fullTrim($engine->render('uselayoutmethod', ['text' => 'Boiler'])),
		);
	}

	public function testSafeCustomTemplateMethodPreservesSafeOutputThroughFilters(): void
	{
		$engine = Engine::create($this->templates())
			->method(
				'upper',
				static fn(string $value): string => ' <b>' . strtoupper($value) . '</b> ',
				safe: true,
			);

		$this->assertSame(
			'<h2><b>BOILER</b></h2>',
			$this->fullTrim($engine->render('methodfilter', ['text' => 'Boiler'])),
		);
	}

	public function testSafeCustomTemplateMethodRejectsNonStringReturn(): void
	{
		$this->throws(
			RenderException::class,
			'Safe template methods must return string or Stringable values',
		);

		$engine = Engine::create($this->templates())
			->method(
				'upper',
				static fn(string $value): int => 13,
				safe: true,
			);

		$engine->render('method', ['text' => 'Boiler']);
	}

	public function testRegisterFilter(): void
	{
		$engine = Engine::create($this->templates())
			->filter('upper', new class implements Filter {
				public function apply(string $value, mixed ...$args): string
				{
					return strtoupper($value);
				}

				public function safe(): bool
				{
					return false;
				}
			});

		$this->assertSame(
			'&lt;B&gt;BOILER&lt;/B&gt;',
			$engine->render('filter', ['text' => '<b>boiler</b>']),
		);
	}

	public function testRegisterSafeFilter(): void
	{
		$engine = Engine::create($this->templates())
			->filter('upper', new class implements Filter {
				public function apply(string $value, mixed ...$args): string
				{
					return strtoupper($value);
				}

				public function safe(): bool
				{
					return true;
				}
			});

		$this->assertSame(
			'<B>BOILER</B>',
			$engine->render('filter', ['text' => '<b>boiler</b>']),
		);
	}

	public function testRegisterFilterFluent(): void
	{
		$engine = Engine::create($this->templates());
		$result = $engine->filter('upper', new class implements Filter {
			public function apply(string $value, mixed ...$args): string
			{
				return strtoupper($value);
			}

			public function safe(): bool
			{
				return false;
			}
		});

		$this->assertSame($engine, $result);
	}

	public function testRegisterEscaper(): void
	{
		$engine = Engine::create($this->templates())
			->escape('caps', new class implements Escaper {
				public function escape(string $value): string
				{
					return strtoupper(htmlspecialchars($value));
				}
			});

		$template = new Template(
			TestCase::DEFAULT_DIR . '/simple.php',
			engine: $engine,
		);
		$context = new TemplateContext($template, ['text' => '<tag>'], [], true);

		$this->assertSame('&LT;TAG&GT;', $context->escape('<tag>', 'caps'));
	}

	public function testRegisterEscaperFluent(): void
	{
		$engine = Engine::create($this->templates());
		$result = $engine->escape('caps', new class implements Escaper {
			public function escape(string $value): string
			{
				return strtoupper(htmlspecialchars($value));
			}
		});

		$this->assertSame($engine, $result);
	}

	public function testUnknownCustomMethod(): void
	{
		$this->throws(RenderException::class, 'upper');

		$engine = Engine::create($this->templates());

		$engine->render('unknownmethod');
	}

	public function testTemplateInstancesFromEngineCanBeRenderedMultipleTimes(): void
	{
		$engine = Engine::create($this->templates());
		$template = $engine->template('addsection');

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
}
