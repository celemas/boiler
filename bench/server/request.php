<?php

declare(strict_types=1);

use Celema\Boiler\Bench\Data;
use Celema\Boiler\Bench\Engines;
use Celema\Boiler\Bench\Sample;

// Served by PHP-FPM: one request renders one page.

require dirname(__DIR__) . '/vendor/autoload.php';

date_default_timezone_set('UTC');

$dir = dirname(__DIR__);
$page = (string) $_GET['page'];
$context = Data::pages((int) $_GET['scale'])[$page];
$candidate = Engines::find($dir, Data::shared(), (string) $_GET['candidate']);

// Timed from here: a request also loads the classes of its engine and sets the engine up.
$sample = Sample::take(static fn(): string => $candidate->render($candidate->engine(), $page, $context));

header('Content-Type: application/json');
echo $sample->json(isset($_GET['html']));
