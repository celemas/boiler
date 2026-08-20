<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

const DEFAULT_RUNS = 1000;
const LINE_LEN = 69;
const DEFAULT_ITERATIONS = 3;
const DEFAULT_LIFECYCLE = 'both';
const LIFECYCLE_WORKER = 'worker';
const LIFECYCLE_REQUEST = 'request';
const LIFECYCLE_BOTH = 'both';

function resetCacheDir(string $path): void
{
	if (!is_dir($path)) {
		mkdir($path, 0o755, true);

		return;
	}

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::CHILD_FIRST,
	);

	foreach ($iterator as $entry) {
		if ($entry->isDir() && !$entry->isLink()) {
			rmdir($entry->getPathname());

			continue;
		}

		unlink($entry->getPathname());
	}
}

function resetBenchmarkCaches(): void
{
	resetCacheDir(__DIR__ . '/cache/twig');
	resetCacheDir(__DIR__ . '/cache/blade');
	resetCacheDir(__DIR__ . '/cache/bladeone');
}

function benchmarkContext(): array
{
	return [
		'title' => '  Product Catalog & Deals <Spring>  ',
		'isLoggedIn' => true,
		'isAdmin' => false,
		'user' => [
			'id' => 42,
			'name' => 'John & Jane <Doe>',
			'email' => 'john@example.com',
			'tier' => '  gold member  ',
			'profile' => [
				'bio' => '<script>alert("xss")</script>Web developer & designer',
				'avatar' => '/img/avatars/john.jpg?size=large&crop=1',
				'location' => 'New York & Berlin <HQ>',
			],
		],
		'topCategories' => [
			[
				'label' => ' Electronics ',
				'url' => '/categories/electronics?sort=popular',
				'children' => [
					['label' => ' Laptops ', 'url' => '/categories/electronics/laptops'],
					['label' => ' Accessories ', 'url' => '/categories/electronics/accessories'],
				],
			],
			[
				'label' => ' Office ',
				'url' => '/categories/office?sort=popular',
				'children' => [
					['label' => ' Furniture ', 'url' => '/categories/office/furniture'],
					['label' => ' Supplies ', 'url' => '/categories/office/supplies'],
				],
			],
			[
				'label' => ' Clearance ',
				'url' => '/categories/clearance?sort=discount',
				'children' => [],
			],
		],
		'products' => [
			[
				'id' => 1,
				'sku' => ' lp-1000 ',
				'name' => 'Laptop Pro 14"',
				'vendor' => '  Acme Tech  ',
				'price' => 1299.99,
				'compareAt' => 1499.99,
				'discountPercent' => 13,
				'inStock' => true,
				'stock' => 12,
				'preorder' => false,
				'freeShipping' => true,
				'rating' => 5,
				'reviews' => 231,
				'tags' => ['electronics', 'laptops'],
				'badges' => [' bestseller ', ' spring deal '],
			],
			[
				'id' => 2,
				'sku' => ' ms-220 ',
				'name' => 'Wireless Mouse',
				'vendor' => '  Pixel Works  ',
				'price' => 49.95,
				'compareAt' => 59.95,
				'discountPercent' => 17,
				'inStock' => true,
				'stock' => 3,
				'preorder' => false,
				'freeShipping' => false,
				'rating' => 4,
				'reviews' => 87,
				'tags' => ['electronics', 'accessories'],
				'badges' => [' low stock '],
			],
			[
				'id' => 3,
				'sku' => ' hub-8c ',
				'name' => 'USB-C Hub 8-in-1',
				'vendor' => '  Dock Labs  ',
				'price' => 79.00,
				'compareAt' => 79.00,
				'discountPercent' => 0,
				'inStock' => false,
				'stock' => 0,
				'preorder' => true,
				'freeShipping' => false,
				'rating' => 4,
				'reviews' => 64,
				'tags' => ['electronics', 'accessories'],
				'badges' => [' preorder '],
			],
			[
				'id' => 4,
				'sku' => ' kb-880 ',
				'name' => 'Mechanical Keyboard',
				'vendor' => '  Key Forge  ',
				'price' => 159.99,
				'compareAt' => 199.99,
				'discountPercent' => 20,
				'inStock' => true,
				'stock' => 2,
				'preorder' => false,
				'freeShipping' => true,
				'rating' => 5,
				'reviews' => 142,
				'tags' => ['electronics', 'accessories'],
				'badges' => [' hot ', ' low stock '],
			],
			[
				'id' => 5,
				'sku' => ' mn-270 ',
				'name' => '27" Monitor',
				'vendor' => '  VisionX  ',
				'price' => 399.00,
				'compareAt' => 449.00,
				'discountPercent' => 11,
				'inStock' => true,
				'stock' => 8,
				'preorder' => false,
				'freeShipping' => true,
				'rating' => 4,
				'reviews' => 118,
				'tags' => ['electronics', 'displays'],
				'badges' => [' free shipping '],
			],
			[
				'id' => 6,
				'sku' => ' cam-hd ',
				'name' => 'Webcam HD',
				'vendor' => '  Stream Co  ',
				'price' => 89.99,
				'compareAt' => 89.99,
				'discountPercent' => 0,
				'inStock' => false,
				'stock' => 0,
				'preorder' => false,
				'freeShipping' => false,
				'rating' => 3,
				'reviews' => 59,
				'tags' => ['electronics', 'video'],
				'badges' => [],
			],
			[
				'id' => 7,
				'sku' => ' lamp-42 ',
				'name' => 'Desk Lamp',
				'vendor' => '  Lumi Home  ',
				'price' => 45.00,
				'compareAt' => 45.00,
				'discountPercent' => 0,
				'inStock' => true,
				'stock' => 15,
				'preorder' => false,
				'freeShipping' => false,
				'rating' => 4,
				'reviews' => 39,
				'tags' => ['office', 'lighting'],
				'badges' => [' bundle '],
			],
			[
				'id' => 8,
				'sku' => ' chr-erg ',
				'name' => 'Ergonomic Chair',
				'vendor' => '  Forma Seat  ',
				'price' => 549.00,
				'compareAt' => 699.00,
				'discountPercent' => 21,
				'inStock' => true,
				'stock' => 4,
				'preorder' => false,
				'freeShipping' => true,
				'rating' => 5,
				'reviews' => 205,
				'tags' => ['office', 'furniture'],
				'badges' => [' premium ', ' spring deal '],
			],
			[
				'id' => 9,
				'sku' => ' desk-std ',
				'name' => 'Standing Desk',
				'vendor' => '  Rise Labs  ',
				'price' => 699.00,
				'compareAt' => 799.00,
				'discountPercent' => 13,
				'inStock' => false,
				'stock' => 0,
				'preorder' => true,
				'freeShipping' => true,
				'rating' => 5,
				'reviews' => 174,
				'tags' => ['office', 'furniture'],
				'badges' => [' preorder ', ' free shipping '],
			],
			[
				'id' => 10,
				'sku' => ' nt-set ',
				'name' => 'Notebook Set',
				'vendor' => '  Paper Mill  ',
				'price' => 24.99,
				'compareAt' => 29.99,
				'discountPercent' => 17,
				'inStock' => true,
				'stock' => 40,
				'preorder' => false,
				'freeShipping' => false,
				'rating' => 4,
				'reviews' => 71,
				'tags' => ['office', 'supplies'],
				'badges' => [' value pack '],
			],
		],
		'activeFilters' => [
			['label' => 'Brand', 'value' => '  Acme Tech  '],
			['label' => 'Price', 'value' => '  under $500  '],
			['label' => 'Shipping', 'value' => '  free shipping  '],
		],
		'facets' => [
			[
				'title' => ' Brand ',
				'expanded' => true,
				'options' => [
					['label' => ' Acme Tech ', 'count' => 12, 'selected' => true],
					['label' => ' VisionX ', 'count' => 8, 'selected' => false],
					['label' => ' Forma Seat ', 'count' => 5, 'selected' => false],
				],
			],
			[
				'title' => ' Price ',
				'expanded' => true,
				'options' => [
					['label' => ' Under $100 ', 'count' => 31, 'selected' => false],
					['label' => ' $100 - $500 ', 'count' => 42, 'selected' => true],
					['label' => ' $500+ ', 'count' => 18, 'selected' => false],
				],
			],
			[
				'title' => ' Rating ',
				'expanded' => false,
				'options' => [
					['label' => ' 4 stars & up ', 'count' => 63, 'selected' => true],
					['label' => ' 3 stars & up ', 'count' => 88, 'selected' => false],
				],
			],
		],
		'recommendations' => [
			[
				'title' => ' Customers also bought ',
				'items' => [
					['name' => 'USB-C Cable 2m', 'price' => 19.99],
					['name' => 'Laptop Sleeve & Stand', 'price' => 39.95],
				],
			],
			[
				'title' => ' Complete your office ',
				'items' => [
					['name' => 'Cable Organizer Kit', 'price' => 14.50],
					['name' => 'Monitor Arm', 'price' => 129.00],
					['name' => 'Foot Rest', 'price' => 34.00],
				],
			],
		],
		'campaign' => [
			'title' => '  spring flash deal  ',
			'code' => '  spring20  ',
			'shippingThreshold' => 150.00,
			'endsAt' => '2026-05-01T20:00:00+02:00',
		],
		'cart' => [
			'items' => 3,
			'subtotal' => 188.94,
			'discount' => 16.95,
			'shipping' => 0.00,
			'total' => 171.99,
		],
		'stats' => [
			'totalProducts' => 156,
			'totalOrders' => 1247,
			'openOrders' => 37,
			'conversionRate' => 3.8,
			'revenue' => 98432.50,
		],
		'store' => (object) [
			'name' => 'Celema & Partners <Store>',
			'currency' => 'USD',
			'support' => (object) [
				'email' => 'ernst@celema.dev',
				'timezone' => 'Europe/Berlin',
			],
		],
		'announcement' => '<p class="alert"><strong>Holiday Sale:</strong> 20% off all items!</p>',
		'breadcrumbs' => new ArrayIterator([
			['label' => 'Home & Garden', 'url' => '/?from=home&promo=spring'],
			['label' => 'Products & Services', 'url' => '/products?view=grid&sort=name'],
			['label' => 'Electronics <Featured>', 'url' => '/products/electronics?filter=audio&sale=1'],
		]),
	];
}

// @mago-expect lint:file-name
class BenchResult
{
	public string $name;
	public string $output;
	public float $min = PHP_FLOAT_MAX;
	public float $max = 0.0;
	public float $total = 0.0;
	public int $count = 0;
	public int $peakMemoryDelta = 0;

	public function __construct(string $name)
	{
		$this->name = $name;
	}

	public function add(float $time, int $memory): void
	{
		$this->min = min($this->min, $time);
		$this->max = max($this->max, $time);
		$this->total += $time;
		$this->count++;
		$this->peakMemoryDelta = max($this->peakMemoryDelta, $memory);
	}

	public function avg(): float
	{
		return $this->count > 0 ? $this->total / $this->count : 0;
	}

	public function print(): void
	{
		printf(
			"%-12s avg: %6.3fs  min: %6.3fs  max: %6.3fs  peak+: %7s\n",
			$this->name . ':',
			$this->avg(),
			$this->min,
			$this->max,
			formatBytes($this->peakMemoryDelta),
		);
	}
}

/** @return array{runs: int, iterations: int, lifecycle: string} */
function benchmarkConfig(): array
{
	static $config;

	if (is_array($config)) {
		return $config;
	}

	$options = getopt('', ['runs:', 'iterations:', 'lifecycle:']);
	assert(is_array($options), 'getopt() must return an array of CLI options');

	return $config = [
		'runs' => intOption($options, 'runs', DEFAULT_RUNS),
		'iterations' => intOption($options, 'iterations', DEFAULT_ITERATIONS),
		'lifecycle' => stringOption(
			$options,
			'lifecycle',
			DEFAULT_LIFECYCLE,
			[LIFECYCLE_WORKER, LIFECYCLE_REQUEST, LIFECYCLE_BOTH],
		),
	];
}

/** @param array<string, mixed> $options */
function intOption(array $options, string $name, int $default): int
{
	$value = $options[$name] ?? null;

	if ($value === null) {
		return $default;
	}

	if (is_array($value) || !is_string($value) || !ctype_digit($value)) {
		throw new InvalidArgumentException("Option --{$name} must be a positive integer");
	}

	$int = (int) $value;

	if ($int < 1) {
		throw new InvalidArgumentException("Option --{$name} must be greater than 0");
	}

	return $int;
}

/**
 * @param array<string, mixed> $options
 * @param list<string> $allowed
 */
function stringOption(array $options, string $name, string $default, array $allowed): string
{
	$value = $options[$name] ?? null;

	if ($value === null) {
		return $default;
	}

	if (is_array($value) || !is_string($value)) {
		throw new InvalidArgumentException("Option --{$name} must be one of: " . implode(', ', $allowed));
	}

	$value = strtolower(trim($value));

	if (!in_array($value, $allowed, true)) {
		throw new InvalidArgumentException("Option --{$name} must be one of: " . implode(', ', $allowed));
	}

	return $value;
}

function benchmarkWarning(): void
{
	$detected = benchmarkProfilers();

	if ($detected === []) {
		return;
	}

	echo str_repeat('!', LINE_LEN) . "\n";
	echo "WARNING: benchmarking with Xdebug or PCOV enabled skews results.\n";
	echo 'Detected: ' . implode(', ', $detected) . "\n";
	echo 'Run with: php -d xdebug.mode=off -d pcov.enabled=0 ' . benchmarkScript() . "\n";
	echo "          [--runs=N] [--iterations=N] [--lifecycle=(request|worker|both)]\n";
	echo "Tip: use composer benchmark -- [options]\n";
	echo str_repeat('!', LINE_LEN) . "\n\n";
}

/** @return list<string> */
function benchmarkProfilers(): array
{
	$profilers = [];
	$xdebug = xdebugMode();

	if ($xdebug !== null) {
		$profilers[] = "xdebug.mode={$xdebug}";
	}

	if (extension_loaded('pcov') && iniEnabled('pcov.enabled')) {
		$profilers[] = 'pcov.enabled=1';
	}

	return $profilers;
}

function xdebugMode(): ?string
{
	if (!extension_loaded('xdebug')) {
		return null;
	}

	$mode = getenv('XDEBUG_MODE');

	if (!is_string($mode) || trim($mode) === '') {
		$mode = (string) ini_get('xdebug.mode');
	}

	$mode = strtolower(trim($mode));

	if ($mode === '' || $mode === 'off') {
		return null;
	}

	return $mode;
}

function benchmarkScript(): string
{
	$argv = $_SERVER['argv'] ?? null;

	if (!is_array($argv)) {
		return 'bench/run.php';
	}

	$script = $argv[0] ?? null;

	return is_string($script) && $script !== '' ? $script : 'bench/run.php';
}

function iniEnabled(string $name): bool
{
	$value = strtolower(trim((string) ini_get($name)));

	return !in_array($value, ['', '0', 'false', 'off', 'no'], true);
}

function runs(): int
{
	return benchmarkConfig()['runs'];
}

function iterations(): int
{
	return benchmarkConfig()['iterations'];
}

function lifecycle(): string
{
	return benchmarkConfig()['lifecycle'];
}

/** @return list<string> */
function lifecycles(): array
{
	return (
		lifecycle() === LIFECYCLE_BOTH
			? [LIFECYCLE_REQUEST, LIFECYCLE_WORKER]
			: [lifecycle()]
	);
}

function formatBytes(int $bytes): string
{
	if ($bytes >= (1024 * 1024)) {
		return sprintf('%.1fMB', ($bytes / 1024) / 1024);
	}

	if ($bytes >= 1024) {
		return sprintf('%.0fKB', $bytes / 1024);
	}

	return $bytes . 'B';
}

function lifecycleLabel(string $lifecycle): string
{
	return match ($lifecycle) {
		LIFECYCLE_WORKER => 'worker (engine reused)',
		LIFECYCLE_REQUEST => 'request (engine recreated per render)',
		default => $lifecycle,
	};
}

/**
 * @template TEngine
 *
 * @param callable(): TEngine $createEngine
 * @param callable(TEngine, array): string $render
 */
function benchEngine(
	string $name,
	callable $createEngine,
	callable $render,
	string $lifecycle,
): BenchResult {
	$result = new BenchResult($name);
	$context = benchmarkContext();
	$runs = runs();
	$iterations = iterations();
	$engine = $createEngine();

	// Warmup - populate caches and trigger autoloading.
	$result->output = $render($engine, $context);
	gc_collect_cycles();

	for ($iter = 0; $iter < $iterations; $iter++) {
		memory_reset_peak_usage();
		$memBefore = memory_get_usage();
		$start = hrtime(true);

		if ($lifecycle === LIFECYCLE_WORKER) {
			for ($i = 0; $i < $runs; $i++) {
				$t = $render($engine, $context);
			}
		} else {
			for ($i = 0; $i < $runs; $i++) {
				$currentEngine = $createEngine();
				$t = $render($currentEngine, $context);
				unset($currentEngine);
			}
		}

		$elapsed = (hrtime(true) - $start) / 1e9;
		$memPeak = max(0, memory_get_peak_usage() - $memBefore);

		$result->add($elapsed, $memPeak);
		$result->output = $t;

		gc_collect_cycles();
	}

	return $result;
}

function benchTwigAutoEscaping(string $lifecycle): BenchResult
{
	return benchEngine(
		'Twig',
		static fn() => new \Twig\Environment(
			new \Twig\Loader\FilesystemLoader(__DIR__ . '/twig'),
			['cache' => __DIR__ . '/cache/twig'],
		),
		static fn(\Twig\Environment $engine, array $context): string => $engine->render(
			'page.html',
			$context,
		),
		$lifecycle,
	);
}

function benchBladeAutoEscaping(string $lifecycle): BenchResult
{
	return benchEngine(
		'Blade',
		static fn() => new \Tempest\Blade\Blade(__DIR__ . '/blade', __DIR__ . '/cache/blade'),
		static fn(\Tempest\Blade\Blade $engine, array $context): string => $engine->render(
			'page',
			$context,
		),
		$lifecycle,
	);
}

function benchBladeOneAutoEscaping(string $lifecycle): BenchResult
{
	return benchEngine(
		'BladeOne',
		static fn() => new \eftec\bladeone\BladeOne(__DIR__ . '/bladeone', __DIR__ . '/cache/bladeone'),
		static fn(\eftec\bladeone\BladeOne $engine, array $context): string => $engine->run(
			'page',
			$context,
		),
		$lifecycle,
	);
}

function benchBoilerAutoEscaping(string $lifecycle): BenchResult
{
	return benchEngine(
		'Boiler',
		static fn() => Celema\Boiler\Engine::create(__DIR__ . '/boiler'),
		static fn(Celema\Boiler\Engine $engine, array $context): string => $engine->render(
			'page',
			$context,
		),
		$lifecycle,
	);
}

function benchPlatesManualEscaping(string $lifecycle): BenchResult
{
	return benchEngine(
		'Plates',
		static fn() => new League\Plates\Engine(__DIR__ . '/plates'),
		static fn(League\Plates\Engine $engine, array $context): string => $engine->render(
			'page',
			$context,
		),
		$lifecycle,
	);
}

function benchBoilerManualEscaping(string $lifecycle): BenchResult
{
	return benchEngine(
		'Boiler',
		static fn() => Celema\Boiler\Engine::unescaped(__DIR__ . '/boiler-manual'),
		static fn(Celema\Boiler\Engine $engine, array $context): string => $engine->render(
			'page',
			$context,
		),
		$lifecycle,
	);
}

function fulltrim(string $text): string
{
	// Remove all whitespace for comparison - engines differ in indentation.
	return preg_replace('/\s+/', '', $text);
}

/** @param list<BenchResult> $results */
function verifyOutputs(array $results): void
{
	echo str_repeat('-', LINE_LEN) . "\n";
	echo 'Output verification: ';

	$expected = fulltrim($results[array_key_first($results)]->output);
	$allMatch = true;

	foreach ($results as $result) {
		if (fulltrim($result->output) === $expected) {
			continue;
		}

		echo "MISMATCH in {$result->name}!\n";
		$allMatch = false;
	}

	if ($allMatch) {
		echo "All outputs match ✓\n";
	}
}

function autoEscapingMemoryNote(string $lifecycle): string
{
	return $lifecycle === LIFECYCLE_WORKER
		? "Note: peak+ is the additional peak memory after warmup for a reused\nengine. "
		. 'Typical for worker mode (FrankenPHP, Roadrunner, etc.).'
		: "Note: peak+ includes allocator overhead from recreating engines\nwithin one process. "
		. 'Not typical for PHP-FPM.';
}

function runScenario(string $lifecycle): void
{
	resetBenchmarkCaches();

	echo 'LIFECYCLE: ' . lifecycleLabel($lifecycle) . "\n";
	if (count(lifecycles()) === 1) {
		echo "           use --lifecycle=(request|worker) to change mode\n";
	}
	echo str_repeat('-', LINE_LEN) . "\n";

	echo "Automatic Escaping:\n";
	echo str_repeat('-', LINE_LEN) . "\n";

	$twigAutoEscaping = benchTwigAutoEscaping($lifecycle);
	$bladeAutoEscaping = benchBladeAutoEscaping($lifecycle);
	$bladeOneAutoEscaping = benchBladeOneAutoEscaping($lifecycle);
	$boilerAutoEscaping = benchBoilerAutoEscaping($lifecycle);

	$twigAutoEscaping->print();
	$bladeAutoEscaping->print();
	$bladeOneAutoEscaping->print();
	$boilerAutoEscaping->print();

	echo str_repeat('-', LINE_LEN) . "\n";
	echo "Manual Escaping:\n";
	echo str_repeat('-', LINE_LEN) . "\n";

	$platesManualEscaping = benchPlatesManualEscaping($lifecycle);
	$boilerManualEscaping = benchBoilerManualEscaping($lifecycle);

	$platesManualEscaping->print();
	$boilerManualEscaping->print();

	echo str_repeat('-', LINE_LEN) . "\n";
	printf("%s\n", autoEscapingMemoryNote($lifecycle));

	verifyOutputs([
		$platesManualEscaping,
		$twigAutoEscaping,
		$bladeOneAutoEscaping,
		$bladeAutoEscaping,
		$boilerAutoEscaping,
		$boilerManualEscaping,
	]);
}

function main(): int
{
	try {
		$runs = runs();
		$iterations = iterations();
	} catch (InvalidArgumentException $e) {
		fwrite(STDERR, $e->getMessage() . PHP_EOL);

		return 1;
	}

	benchmarkWarning();

	echo "\n" . str_repeat('=', LINE_LEN);
	echo "\nBenchmark: " . number_format($runs) . ' renders × ' . $iterations . ' iterations';
	echo "\n           $ composer benchmark -- --runs=" . $runs . ' --iterations=' . $iterations . "\n";
	echo str_repeat('=', LINE_LEN) . "\n\n\n";

	foreach (lifecycles() as $index => $lifecycle) {
		if ($index > 0) {
			echo "\n\n" . str_repeat(' ~ ', (int) LINE_LEN / 3) . "\n\n\n";
		}

		runScenario($lifecycle);
	}

	return 0;
}

exit(main());
