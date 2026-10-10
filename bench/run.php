<?php

declare(strict_types=1);

use Celema\Boiler\Bench\Candidate;
use Celema\Boiler\Bench\Data;
use Celema\Boiler\Bench\Engines;
use Celema\Boiler\Bench\Fpm;
use Celema\Boiler\Bench\FrankenPhp;
use Celema\Boiler\Bench\Loop;
use Celema\Boiler\Bench\Result;
use Celema\Boiler\Bench\Runtime;

require __DIR__ . '/vendor/autoload.php';

// Twig's date filter converts to the default time zone; the other engines
// print the dates of the data as they are.
date_default_timezone_set('UTC');

const DEFAULT_RUNS = 100;
const DEFAULT_ITERATIONS = 3;
const DEFAULT_SCALE = 1;
const WARMUP_ROUNDS = 3;
const LIFECYCLE_REQUEST = 'request';
const LIFECYCLE_WORKER = 'worker';
const LIFECYCLE_LOOP = 'loop';
const LIFECYCLE_ALL = 'all';
const LINE_LEN = 80;

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
	foreach (Engines::caches(__DIR__) as $path) {
		resetCacheDir($path);
	}
}

/**
 * @return array{
 *     runs: int,
 *     iterations: int,
 *     scale: int,
 *     lifecycle: string,
 *     php-fpm: ?string,
 *     frankenphp: ?string,
 * }
 */
function benchmarkConfig(): array
{
	static $config;

	if (is_array($config)) {
		return $config;
	}

	$options = getopt('', ['runs:', 'iterations:', 'scale:', 'lifecycle:', 'php-fpm:', 'frankenphp:']);
	assert(is_array($options), 'getopt() must return an array of CLI options');
	$fpm = $options['php-fpm'] ?? null;
	$frankenphp = $options['frankenphp'] ?? null;

	return $config = [
		'runs' => intOption($options, 'runs', DEFAULT_RUNS),
		'iterations' => intOption($options, 'iterations', DEFAULT_ITERATIONS),
		'scale' => intOption($options, 'scale', DEFAULT_SCALE),
		'lifecycle' => stringOption(
			$options,
			'lifecycle',
			LIFECYCLE_ALL,
			[LIFECYCLE_REQUEST, LIFECYCLE_WORKER, LIFECYCLE_LOOP, LIFECYCLE_ALL],
		),
		'php-fpm' => is_string($fpm) ? $fpm : null,
		'frankenphp' => is_string($frankenphp) ? $frankenphp : null,
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

/** The settings of this process count for the loop only; the servers get their own. */
function benchmarkWarning(): void
{
	$detected = [...benchmarkProfilers(), ...opcacheSettings()];

	if ($detected === []) {
		return;
	}

	echo str_repeat('!', LINE_LEN) . "\n";
	echo "WARNING: these PHP settings skew the results of the loop lifecycle.\n";
	echo 'Detected: ' . implode(', ', $detected) . "\n";
	echo "Run with: php -d xdebug.mode=off -d pcov.enabled=0 -d opcache.enable_cli=1\n";
	echo '          -d opcache.file_update_protection=0 ' . benchmarkScript() . "\n";
	echo "          [--runs=N] [--iterations=N] [--scale=N]\n";
	echo "          [--lifecycle=(request|worker|loop|all)]\n";
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

/**
 * Without OPcache, PHP compiles a template file again on every include, which
 * outweighs the engine's own work. Its file update protection keeps it from
 * caching the templates that Blade compiles right before the measurement.
 *
 * @return list<string>
 */
function opcacheSettings(): array
{
	$settings = [];

	foreach (['opcache.enable', 'opcache.enable_cli'] as $name) {
		if (!iniEnabled($name)) {
			$settings[] = "{$name}=0";
		}
	}

	$protection = (int) ini_get('opcache.file_update_protection');

	if ($protection > 0) {
		$settings[] = "opcache.file_update_protection={$protection}";
	}

	return $settings;
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

function scale(): int
{
	return benchmarkConfig()['scale'];
}

/** @return list<string> */
function lifecycles(): array
{
	$lifecycle = benchmarkConfig()['lifecycle'];

	return $lifecycle === LIFECYCLE_ALL ? [LIFECYCLE_REQUEST, LIFECYCLE_WORKER, LIFECYCLE_LOOP] : [$lifecycle];
}

/** @return Runtime|string the runtime of the lifecycle, or the reason why it cannot run */
function runtime(string $lifecycle): Runtime|string
{
	return match ($lifecycle) {
		LIFECYCLE_REQUEST => Fpm::detect(__DIR__, scale(), benchmarkConfig()['php-fpm']),
		LIFECYCLE_WORKER => FrankenPhp::detect(__DIR__, scale(), benchmarkConfig()['frankenphp']),
		default => new Loop(scale()),
	};
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

/**
 * Renders the pages in turn, as a site serves them, and times each page on
 * its own.
 *
 * @param Candidate<object> $candidate
 * @param list<string> $pages
 */
function measure(Runtime $runtime, Candidate $candidate, array $pages): Result
{
	$result = new Result($candidate);
	$runs = runs();
	$runtime->serve($candidate);

	// Compiles the templates and fills the caches of the runtime.
	for ($round = 0; $round < WARMUP_ROUNDS; $round++) {
		foreach ($pages as $page) {
			$result->output[$page] = (string) $runtime->sample($page, html: true)->html;
		}
	}

	for ($iteration = 0; $iteration < iterations(); $iteration++) {
		gc_collect_cycles();
		$times = array_fill_keys($pages, 0);

		for ($run = 0; $run < $runs; $run++) {
			foreach ($pages as $page) {
				$sample = $runtime->sample($page);
				$times[$page] += $sample->nanoseconds;
				$result->memory = max($result->memory, $sample->memory);
				$result->held = $sample->held;
			}
		}

		$result->add($times, $runs);
	}

	return $result;
}

/**
 * @param list<Result> $results
 * @param list<string> $pages
 */
function printResults(array $results, array $pages): void
{
	$held = array_any($results, static fn(Result $result): bool => $result->held !== null);
	$columns = array_map(static fn(string $column): string => sprintf('%9s', $column), [...$pages, 'total']);

	printf("%19s%s  spread   memory%s\n", '', implode('', $columns), $held ? '    held' : '');
	echo str_repeat('-', LINE_LEN) . "\n";

	foreach ([['Automatic escaping', true], ['Manual escaping', false]] as [$title, $escapes]) {
		echo $title . "\n";

		foreach ($results as $result) {
			if ($result->candidate->escapes !== $escapes) {
				continue;
			}

			$times = $result->best();
			$times[] = array_sum($times);

			printf(
				"  %-17s%s%7.0f%%%9s%s\n",
				$result->candidate->name,
				implode('', array_map(static fn(float $time): string => sprintf('%9.3f', $time), $times)),
				$result->spread() * 100,
				formatBytes($result->memory),
				$result->held === null ? '' : sprintf('%8s', formatBytes($result->held)),
			);
		}
	}

	echo str_repeat('-', LINE_LEN) . "\n";
}

/** @return list<Result> */
function runScenario(string $lifecycle, Runtime $runtime): array
{
	echo "LIFECYCLE: {$lifecycle} ({$runtime->label()})\n";
	echo str_repeat('-', LINE_LEN) . "\n";

	$pages = array_keys(Data::pages(scale()));
	$results = [];
	// A fatal error skips the finally block below.
	register_shutdown_function($runtime->stop(...));

	try {
		foreach (Engines::all(__DIR__, Data::shared()) as $candidate) {
			$results[] = measure($runtime, $candidate, $pages);
		}
	} finally {
		$runtime->stop();
	}

	printResults($results, $pages);

	return $results;
}

function printLegend(): void
{
	echo 'Times are milliseconds per render in the fastest of ' . iterations() . " iterations; total is one\n";
	echo "round of the pages, and spread is how much slower the slowest iteration was.\n";
	echo "memory is the peak that the timed part adds: in a request the engine with its\n";
	echo "classes and the render, otherwise the render alone. held is what the engine\n";
	echo "keeps between the requests of a worker.\n";
}

/** @param list<Result> $results */
function verifyOutputs(array $results): bool
{
	$normalize = static fn(string $html): string => (string) preg_replace('/\s+/', '', $html);
	$expected = array_map($normalize, $results[0]->output);
	$mismatches = [];

	foreach ($results as $result) {
		foreach ($result->output as $page => $html) {
			// Engines differ in indentation, so whitespace does not count.
			if ($normalize($html) !== $expected[$page]) {
				$mismatches[] = "{$result->candidate->name} ({$result->candidate->id}), page {$page}";
			}
		}
	}

	$sizes = [];

	foreach ($results[0]->output as $page => $html) {
		$sizes[] = $page . ' ' . formatBytes(strlen($html));
	}

	echo 'Pages: ' . implode(', ', $sizes) . "\n";
	echo 'Output verification: ';
	echo $mismatches === [] ? "all engines render the same pages in every lifecycle ✓\n" : "MISMATCH\n";

	foreach (array_unique($mismatches) as $mismatch) {
		echo "  differs from {$results[0]->candidate->name}: {$mismatch}\n";
	}

	return $mismatches === [];
}

function main(): int
{
	try {
		benchmarkConfig();
	} catch (InvalidArgumentException $e) {
		fwrite(STDERR, $e->getMessage() . PHP_EOL);

		return 1;
	}

	if (in_array(LIFECYCLE_LOOP, lifecycles(), true)) {
		benchmarkWarning();
	}

	echo "\n" . str_repeat('=', LINE_LEN);
	echo "\nBenchmark: " . number_format(runs()) . ' rounds × ' . iterations() . ' iterations, scale ' . scale();
	echo "\n           $ composer benchmark -- --runs=" . runs() . ' --iterations=' . iterations();
	echo ' --scale=' . scale() . "\n";
	echo str_repeat('=', LINE_LEN) . "\n\n\n";

	// Once for the whole run: Twig writes a compiled template only when it
	// first loads the class, and the servers need the files as well.
	resetBenchmarkCaches();
	$results = [];

	foreach (lifecycles() as $lifecycle) {
		$runtime = runtime($lifecycle);

		if (is_string($runtime)) {
			echo "LIFECYCLE: {$lifecycle} skipped, {$runtime}\n\n\n";

			continue;
		}

		try {
			array_push($results, ...runScenario($lifecycle, $runtime));
		} catch (RuntimeException $e) {
			fwrite(STDERR, "The {$lifecycle} lifecycle failed: {$e->getMessage()}" . PHP_EOL);

			return 1;
		}

		echo "\n\n";
	}

	if ($results === []) {
		fwrite(STDERR, 'No lifecycle could run.' . PHP_EOL);

		return 1;
	}

	printLegend();
	echo "\n";

	return verifyOutputs($results) ? 0 : 1;
}

exit(main());
