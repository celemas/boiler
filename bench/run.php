<?php

declare(strict_types=1);

use Celema\Boiler\Bench\Candidate;
use Celema\Boiler\Bench\Data;
use Celema\Boiler\Bench\Engines;
use Celema\Boiler\Bench\Result;

require __DIR__ . '/vendor/autoload.php';

// Twig's date filter converts to the default time zone; the other engines
// print the dates of the data as they are.
date_default_timezone_set('UTC');

const DEFAULT_RUNS = 300;
const DEFAULT_ITERATIONS = 3;
const DEFAULT_SCALE = 1;
const DEFAULT_LIFECYCLE = 'both';
const LIFECYCLE_WORKER = 'worker';
const LIFECYCLE_REQUEST = 'request';
const LIFECYCLE_BOTH = 'both';
const LINE_LEN = 69;

// What a meaningful run needs; benchmarkWarning() reports deviations.
const PHP_SETTINGS = [
	'xdebug.mode' => 'off',
	'pcov.enabled' => '0',
	'opcache.enable_cli' => '1',
	'opcache.file_update_protection' => '0',
];

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

/** @return array{runs: int, iterations: int, scale: int, lifecycle: string, probe: ?string} */
function benchmarkConfig(): array
{
	static $config;

	if (is_array($config)) {
		return $config;
	}

	$options = getopt('', ['runs:', 'iterations:', 'scale:', 'lifecycle:', 'probe:']);
	assert(is_array($options), 'getopt() must return an array of CLI options');
	$probe = $options['probe'] ?? null;

	return $config = [
		'runs' => intOption($options, 'runs', DEFAULT_RUNS),
		'iterations' => intOption($options, 'iterations', DEFAULT_ITERATIONS),
		'scale' => intOption($options, 'scale', DEFAULT_SCALE),
		'lifecycle' => stringOption(
			$options,
			'lifecycle',
			DEFAULT_LIFECYCLE,
			[LIFECYCLE_WORKER, LIFECYCLE_REQUEST, LIFECYCLE_BOTH],
		),
		'probe' => is_string($probe) ? $probe : null,
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
	$detected = [...benchmarkProfilers(), ...opcacheSettings()];

	if ($detected === []) {
		return;
	}

	echo str_repeat('!', LINE_LEN) . "\n";
	echo "WARNING: these PHP settings skew the benchmark results.\n";
	echo 'Detected: ' . implode(', ', $detected) . "\n";
	echo "Run with: php -d xdebug.mode=off -d pcov.enabled=0 -d opcache.enable_cli=1\n";
	echo '          -d opcache.file_update_protection=0 ' . benchmarkScript() . "\n";
	echo "          [--runs=N] [--iterations=N] [--scale=N]\n";
	echo "          [--lifecycle=(request|worker|both)]\n";
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

/** @return list<Candidate<object>> */
function candidates(): array
{
	return Engines::all(__DIR__, Data::shared());
}

/**
 * Renders the pages in turn, as a site serves them, and times each page on
 * its own.
 *
 * @param Candidate<object> $candidate
 * @param array<string, array<string, mixed>> $pages
 */
function measure(Candidate $candidate, array $pages, string $lifecycle): Result
{
	$result = new Result($candidate);
	$runs = runs();
	$engine = $candidate->engine();

	// Warmup: compiles the templates and triggers autoloading.
	foreach ($pages as $page => $context) {
		$result->output[$page] = $candidate->render($engine, $page, $context);
	}

	for ($iteration = 0; $iteration < iterations(); $iteration++) {
		gc_collect_cycles();
		$times = array_fill_keys(array_keys($pages), 0);

		for ($run = 0; $run < $runs; $run++) {
			foreach ($pages as $page => $context) {
				$start = hrtime(true);

				if ($lifecycle === LIFECYCLE_REQUEST) {
					$engine = $candidate->engine();
				}

				$candidate->render($engine, $page, $context);
				$times[$page] += hrtime(true) - $start;
			}
		}

		$result->add($times, $runs);
	}

	return $result;
}

/**
 * @param list<Candidate<object>> $candidates
 * @param callable(Candidate<object>): string $row
 */
function printGroups(array $candidates, callable $row): void
{
	foreach ([['Automatic escaping', true], ['Manual escaping', false]] as [$title, $escapes]) {
		echo $title . "\n";

		foreach ($candidates as $candidate) {
			if ($candidate->escapes === $escapes) {
				printf("  %-19s%s\n", $candidate->name, $row($candidate));
			}
		}
	}
}

/**
 * @param array<string, Result> $results keyed by candidate id
 * @param list<string> $pages
 */
function printTimes(array $results, array $pages): void
{
	$columns = array_map(static fn(string $column): string => sprintf('%10s', $column), [...$pages, 'total']);

	printf("%21s%s  spread\n", '', implode('', $columns));
	echo str_repeat('-', LINE_LEN) . "\n";

	printGroups(
		array_map(static fn(Result $result): Candidate => $result->candidate, array_values($results)),
		static function (Candidate $candidate) use ($results): string {
			$result = $results[$candidate->id];
			$times = $result->best();
			$times[] = array_sum($times);

			return sprintf(
				'%s%7.0f%%',
				implode('', array_map(static fn(float $time): string => sprintf('%10.3f', $time), $times)),
				$result->spread() * 100,
			);
		},
	);

	echo str_repeat('-', LINE_LEN) . "\n";
	echo 'Milliseconds per render in the fastest of ' . iterations() . " iterations. total is one\n";
	echo "round, which renders each page once. spread is how much slower the\n";
	echo "slowest iteration was.\n";
}

/** @return array<string, Result> keyed by candidate id */
function runScenario(string $lifecycle): array
{
	echo 'LIFECYCLE: ' . lifecycleLabel($lifecycle) . "\n";
	if (count(lifecycles()) === 1) {
		echo "           use --lifecycle=(request|worker) to change mode\n";
	}
	echo str_repeat('-', LINE_LEN) . "\n";

	$pages = Data::pages(scale());
	$results = [];

	foreach (candidates() as $candidate) {
		$results[$candidate->id] = measure($candidate, $pages, $lifecycle);
	}

	printTimes($results, array_keys($pages));

	return $results;
}

/**
 * Measures one candidate in this process, which the benchmark started for it
 * alone, so that nothing another engine loaded counts.
 */
function probe(string $id): int
{
	$pages = Data::pages(scale());

	foreach (candidates() as $candidate) {
		if ($candidate->id !== $id) {
			continue;
		}

		$render = static function (object $engine) use ($candidate, $pages): void {
			foreach ($pages as $page => $context) {
				$candidate->render($engine, $page, $context);
			}
		};

		$before = memory_get_usage();
		$engine = $candidate->engine();
		$render($engine);
		gc_collect_cycles();
		$loaded = memory_get_usage() - $before;

		memory_reset_peak_usage();
		$before = memory_get_usage();
		$render($engine);
		$peak = memory_get_peak_usage() - $before;

		echo json_encode(['loaded' => $loaded, 'render' => $peak]);

		return 0;
	}

	fwrite(STDERR, "Unknown candidate {$id}" . PHP_EOL);

	return 1;
}

/**
 * @param Candidate<object> $candidate
 *
 * @return array{loaded: int, render: int}|null
 */
function probeInFreshProcess(Candidate $candidate): ?array
{
	$command = [PHP_BINARY];

	foreach (PHP_SETTINGS as $name => $value) {
		array_push($command, '-d', "{$name}={$value}");
	}

	array_push($command, __FILE__, '--probe=' . $candidate->id, '--scale=' . scale());
	$process = proc_open($command, [1 => ['pipe', 'w']], $pipes);

	if (!is_resource($process)) {
		return null;
	}

	$memory = json_decode((string) stream_get_contents($pipes[1]), true);
	fclose($pipes[1]);

	return proc_close($process) === 0 && is_array($memory) ? $memory : null;
}

function printMemory(): void
{
	echo "MEMORY: one fresh process per engine\n";
	echo str_repeat('-', LINE_LEN) . "\n";
	printf("%31s%10s\n", 'loaded', 'render');
	echo str_repeat('-', LINE_LEN) . "\n";

	printGroups(candidates(), static function (Candidate $candidate): string {
		$memory = probeInFreshProcess($candidate);

		return $memory === null
			? sprintf('%10s%10s', 'failed', '')
			: sprintf('%10s%10s', formatBytes($memory['loaded']), formatBytes($memory['render']));
	});

	echo str_repeat('-', LINE_LEN) . "\n";
	echo "loaded is the memory still in use after each page was rendered once.\n";
	echo "render is the additional peak while the pages render again.\n";
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
	echo $mismatches === [] ? "all engines render the same pages ✓\n" : "MISMATCH\n";

	foreach (array_unique($mismatches) as $mismatch) {
		echo "  differs from {$results[0]->candidate->name}: {$mismatch}\n";
	}

	return $mismatches === [];
}

function main(): int
{
	try {
		$config = benchmarkConfig();
	} catch (InvalidArgumentException $e) {
		fwrite(STDERR, $e->getMessage() . PHP_EOL);

		return 1;
	}

	if ($config['probe'] !== null) {
		return probe($config['probe']);
	}

	benchmarkWarning();

	echo "\n" . str_repeat('=', LINE_LEN);
	echo "\nBenchmark: " . number_format(runs()) . ' rounds × ' . iterations() . ' iterations, scale ' . scale();
	echo "\n           $ composer benchmark -- --runs=" . runs() . ' --iterations=' . iterations();
	echo ' --scale=' . scale() . "\n";
	echo str_repeat('=', LINE_LEN) . "\n\n\n";

	// Once for the whole run: Twig writes a compiled template only when it
	// first loads the class, and the memory probes need the files.
	resetBenchmarkCaches();
	$results = [];

	foreach (lifecycles() as $lifecycle) {
		array_push($results, ...array_values(runScenario($lifecycle)));
		echo "\n\n";
	}

	printMemory();
	echo "\n\n";

	return verifyOutputs($results) ? 0 : 1;
}

exit(main());
