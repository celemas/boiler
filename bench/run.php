<?php

declare(strict_types=1);

use Celema\Boiler\Bench\Candidate;
use Celema\Boiler\Bench\Data;
use Celema\Boiler\Bench\Engines;
use Celema\Boiler\Bench\Fpm;
use Celema\Boiler\Bench\FrankenPhp;
use Celema\Boiler\Bench\History;
use Celema\Boiler\Bench\Loop;
use Celema\Boiler\Bench\Result;
use Celema\Boiler\Bench\Runtime;
use Composer\InstalledVersions;

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

// Runs of the same code differ by about this much in total.
const NOISE = 0.02;

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
 *     compare: ?string,
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

	$options = getopt('', ['runs:', 'iterations:', 'scale:', 'lifecycle:', 'compare::', 'php-fpm:', 'frankenphp:']);
	assert(is_array($options), 'getopt() must return an array of CLI options');
	// getopt() reports an option that takes an optional value and got none as false.
	$compare = $options['compare'] ?? null;
	$fpm = $options['php-fpm'] ?? null;
	$frankenphp = $options['frankenphp'] ?? null;

	return $config = [
		'runs' => intOption($options, 'runs', DEFAULT_RUNS),
		'iterations' => intOption($options, 'iterations', DEFAULT_ITERATIONS),
		'scale' => intOption($options, 'scale', DEFAULT_SCALE),
		'lifecycle' => stringOption(
			$options,
			'lifecycle',
			LIFECYCLE_REQUEST,
			[LIFECYCLE_REQUEST, LIFECYCLE_WORKER, LIFECYCLE_LOOP, LIFECYCLE_ALL],
		),
		'compare' => match (true) {
			$compare === false => '',
			is_string($compare) => $compare,
			default => null,
		},
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
	echo "          [--runs=N] [--iterations=N] [--scale=N] [--compare[=RUN]]\n";
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

function environment(): string
{
	return getenv('BENCH_ENVIRONMENT') ?: 'native';
}

/** The commit the sources are at, which a container cannot see and gets passed in. */
function revision(): string
{
	$revision = getenv('BENCH_REVISION');

	if (is_string($revision) && $revision !== '') {
		return $revision;
	}

	$git = 'git -C ' . escapeshellarg(dirname(__DIR__));
	$commit = trim((string) shell_exec($git . ' rev-parse --short HEAD 2>/dev/null'));

	if ($commit === '') {
		return 'unknown';
	}

	exec($git . ' diff --quiet HEAD 2>/dev/null', $output, $changed);

	return $changed === 0 ? $commit : $commit . '-dirty';
}

/** The local time of the machine for the name of the saved run; this script and a container run in UTC. */
function startTime(): string
{
	$time = getenv('BENCH_TIME') ?: trim((string) shell_exec('date +%Y-%m-%d-%H%M%S 2>/dev/null'));

	return $time !== '' ? $time : gmdate('Y-m-d-His');
}

function history(): History
{
	return new History(dirname(__DIR__) . '/.bench');
}

/**
 * Why the numbers of a saved run say nothing about this one.
 *
 * @param array<string, mixed> $run
 */
function incomparable(array $run): ?string
{
	$environment = (string) ($run['environment'] ?? 'unknown');
	$scale = (int) ($run['scale'] ?? 0);

	return match (true) {
		$environment !== environment() => "it ran in another environment ({$environment})",
		$scale !== scale() => "it ran at scale {$scale}",
		default => null,
	};
}

/**
 * @return array{file: string, run: array<string, mixed>}|null the run that --compare asks for
 *
 * @throws InvalidArgumentException when the run it names is missing or does not compare
 */
function baseline(): ?array
{
	$reference = benchmarkConfig()['compare'];

	if ($reference === null) {
		return null;
	}

	$history = history();
	$file = $reference === ''
		? $history->newest(
			static fn(array $run): bool => (
				incomparable($run) === null
				&& array_diff(lifecycles(), array_keys((array) $run['lifecycles'])) === []
			),
		)
		: $history->find($reference);

	if ($file === null && $reference === '') {
		echo "No earlier run with this lifecycle, scale, and environment to compare with.\n\n\n";

		return null;
	}

	if ($file === null) {
		throw new InvalidArgumentException("No saved run matches {$reference}");
	}

	$run = $history->load($file);
	$reason = incomparable($run);

	if ($reason !== null) {
		throw new InvalidArgumentException('.bench/' . basename($file) . " does not compare: {$reason}");
	}

	return ['file' => $file, 'run' => $run];
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
 * Compiles the templates before any runtime starts. A worker that had to
 * compile them itself would keep the compiler in memory and report it as held.
 */
function compileTemplates(): void
{
	$pages = Data::pages(scale());

	foreach (Engines::all(__DIR__, Data::shared()) as $candidate) {
		$engine = $candidate->engine();

		foreach ($pages as $page => $context) {
			$candidate->render($engine, $page, $context);
		}
	}
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
 * How the total changed against a saved result. A change that may be noise is
 * marked: one below the usual difference between runs or within the spread
 * of either run.
 *
 * @param array{total: float, spread: float}|null $before
 */
function change(float $total, float $spread, ?array $before): string
{
	if ($before === null) {
		return '';
	}

	$change = ($total - $before['total']) / $before['total'];
	$clear = abs($change) > max($spread, $before['spread'], NOISE);

	return sprintf('%s%+.1f%%', $clear ? '' : '~', $change * 100);
}

/**
 * @param list<Result> $results
 * @param list<string> $pages
 * @param array<string, array{total: float, spread: float}>|null $before the saved results by candidate
 */
function printResults(array $results, array $pages, ?array $before): void
{
	$held = array_any($results, static fn(Result $result): bool => $result->held !== null);
	$column = static fn(string $name): string => sprintf('%9s', $name);

	$header = sprintf(
		'%19s%s%s%s  spread   memory%s',
		'',
		$column('total'),
		$before === null ? '' : '  change',
		implode('', array_map($column, $pages)),
		$held ? '    held' : '',
	);
	$rule = str_repeat('-', max(LINE_LEN, strlen($header))) . "\n";

	echo $rule . $header . "\n" . $rule;

	foreach ([['Automatic escaping', true], ['Manual escaping', false]] as [$title, $escapes]) {
		echo $title . "\n";

		foreach ($results as $result) {
			if ($result->candidate->escapes !== $escapes) {
				continue;
			}

			$best = $result->best();
			$total = array_sum($best);
			$time = static fn(float $milliseconds): string => sprintf('%9.3f', $milliseconds);

			printf(
				"  %-17s%s%s%s%7.0f%%%9s%s\n",
				$result->candidate->name,
				$time($total),
				$before === null
					? ''
					: sprintf('%8s', change($total, $result->spread(), $before[$result->candidate->id] ?? null)),
				implode('', array_map($time, array_values($best))),
				$result->spread() * 100,
				formatBytes($result->memory),
				$result->held === null ? '' : sprintf('%8s', formatBytes($result->held)),
			);
		}
	}

	echo $rule;
}

/**
 * @param array<string, array{total: float, spread: float}>|null $before the saved results by candidate
 *
 * @return list<Result>
 */
function runScenario(string $lifecycle, Runtime $runtime, ?array $before): array
{
	echo "LIFECYCLE: {$lifecycle} ({$runtime->label()})\n";

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

	printResults($results, $pages, $before);

	return $results;
}

/**
 * What a run is saved as.
 *
 * @param array<string, array{runtime: string, results: list<Result>}> $scenarios by lifecycle
 *
 * @return array<string, mixed>
 */
function record(array $scenarios): array
{
	$lifecycles = [];
	$round = static fn(float $value): float => round($value, 4);

	foreach ($scenarios as $lifecycle => $scenario) {
		$rows = [];

		foreach ($scenario['results'] as $result) {
			$pages = $result->best();
			$rows[$result->candidate->id] = [
				'name' => $result->candidate->name,
				'total' => $round(array_sum($pages)),
				'pages' => array_map($round, $pages),
				'spread' => $round($result->spread()),
				'memory' => $result->memory,
				'held' => $result->held,
			];
		}

		$lifecycles[$lifecycle] = ['runtime' => $scenario['runtime'], 'results' => $rows];
	}

	$engines = [];

	foreach (['twig/twig', 'illuminate/view', 'league/plates'] as $package) {
		$engines[$package] = InstalledVersions::getPrettyVersion($package);
	}

	return [
		'time' => date(DATE_ATOM),
		'revision' => revision(),
		'environment' => environment(),
		'system' => php_uname('s') . ' ' . php_uname('m'),
		'php' => PHP_VERSION,
		'runs' => runs(),
		'iterations' => iterations(),
		'scale' => scale(),
		'engines' => $engines,
		'lifecycles' => $lifecycles,
	];
}

/** @param list<Result> $results */
function printLegend(array $results, bool $compared): void
{
	echo 'Times are milliseconds per render in the fastest of ' . iterations() . " iterations; total is one\n";
	echo "round of the pages, and spread is how much slower the slowest iteration was.\n";
	echo "memory is the peak that the timed part adds: in a request the engine with its\n";
	echo "classes and the render, otherwise the render alone.\n";

	if (array_any($results, static fn(Result $result): bool => $result->held !== null)) {
		echo "held is what the engine keeps between the requests of a worker.\n";
	}

	if ($compared) {
		echo "change is how total differs from the compared run; ~ marks a difference that\n";
		echo 'may be noise: below ' . (NOISE * 100) . "% or within the spread of either run.\n";
	}
}

/** @param list<Result> $results */
function verifyOutputs(array $results): bool
{
	// Engines differ in indentation and line breaks, so whitespace next to a
	// tag does not count. A space inside text or an attribute value does.
	$normalize = static fn(string $html): string => trim((string) preg_replace(
		['/\s+/', '/ ?([<>]) ?/'],
		[' ', '$1'],
		$html,
	));
	$expected = array_map($normalize, $results[0]->output);
	$mismatches = [];

	foreach ($results as $result) {
		foreach ($result->output as $page => $html) {
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
		return benchmark();
	} catch (InvalidArgumentException $e) {
		fwrite(STDERR, $e->getMessage() . PHP_EOL);

		return 1;
	}
}

function benchmark(): int
{
	if (in_array(LIFECYCLE_LOOP, lifecycles(), true)) {
		benchmarkWarning();
	}

	echo "\n" . str_repeat('=', LINE_LEN);
	echo "\nBenchmark: " . number_format(runs()) . ' rounds × ' . iterations() . ' iterations, scale ' . scale();
	echo "\n           $ composer benchmark -- --runs=" . runs() . ' --iterations=' . iterations();
	echo ' --scale=' . scale() . "\n";
	echo str_repeat('=', LINE_LEN) . "\n\n\n";

	$time = startTime();
	$baseline = baseline();
	resetBenchmarkCaches();
	compileTemplates();
	$scenarios = [];

	foreach (lifecycles() as $lifecycle) {
		$runtime = runtime($lifecycle);

		if (is_string($runtime)) {
			echo "LIFECYCLE: {$lifecycle} skipped, {$runtime}\n\n\n";

			continue;
		}

		/** @var array<string, array{total: float, spread: float}>|null $before */
		$before = $baseline === null ? null : $baseline['run']['lifecycles'][$lifecycle]['results'] ?? [];

		try {
			$scenarios[$lifecycle] = [
				'runtime' => $runtime->label(),
				'results' => runScenario($lifecycle, $runtime, $before),
			];
		} catch (RuntimeException $e) {
			fwrite(STDERR, "The {$lifecycle} lifecycle failed: {$e->getMessage()}" . PHP_EOL);

			return 1;
		}

		echo "\n\n";
	}

	$results = array_merge(...array_values(array_column($scenarios, 'results')));

	if ($results === []) {
		fwrite(STDERR, 'No lifecycle could run.' . PHP_EOL);

		return 1;
	}

	printLegend($results, $baseline !== null);
	echo "\n";

	if (!verifyOutputs($results)) {
		return 1;
	}

	echo 'Saved as .bench/' . basename(history()->save(record($scenarios), $time, revision())) . "\n";

	if ($baseline !== null) {
		echo 'Compared with .bench/' . basename($baseline['file']) . "\n";
	}

	return 0;
}

exit(main());
