<?php

declare(strict_types=1);

use Celema\Boiler\Bench\Data;
use Celema\Boiler\Bench\Engines;
use Celema\Boiler\Bench\Sample;

// The FrankenPHP worker: boots once, then renders one page per request with the same engine.

require dirname(__DIR__) . '/vendor/autoload.php';

date_default_timezone_set('UTC');

$scale = (int) $_SERVER['BENCH_SCALE'];
$candidate = Engines::find(dirname(__DIR__), Data::shared(), (string) $_SERVER['BENCH_CANDIDATE']);
$base = memory_get_usage();
$engine = $candidate->engine();

$handle = static function () use ($candidate, $engine, $scale, $base): void {
	$held = memory_get_usage() - $base;
	$page = (string) $_GET['page'];
	$context = Data::pages($scale)[$page];
	$sample = Sample::take(static fn(): string => $candidate->render($engine, $page, $context), $held);

	header('Content-Type: application/json');
	echo $sample->json(isset($_GET['html']));
};

while (frankenphp_handle_request($handle)) {
	gc_collect_cycles();
}
