# Boiler benchmark

This benchmark renders three pages of a shop site with Boiler, Twig, Laravel Blade, and Plates: in PHP-FPM requests, in a FrankenPHP worker, and in a plain loop. It is meant to approximate realistic page renders and is used mainly internally to catch regressions, not to benchmark every feature in isolation.

## What it renders

Every round renders three pages in turn, as a site serves them:

| Page | Layout chain | What it shows |
| --- | --- | --- |
| `listing` | `base` ← `shop` | a category with a grid of product cards, filters, sort options, and pagination |
| `product` | `base` ← `shop` | one product with gallery, variants, specifications, reviews, and related products |
| `article` | `base` | a magazine article made of content blocks, each rendered by the template of its type |

All pages share the site header with a two-level menu, breadcrumbs, and the footer. The shop pages also share a promotion banner and a sidebar. At the default scale, the pages are about 70, 35, and 25 KB of HTML with about 800, 400, and 300 escaped values.

[`src/Data.php`](src/Data.php) generates the data from a fixed seed, so every run and every engine works on the same values. It mixes what applications pass to templates: nested arrays, an iterator, read-only objects with properties and methods, an enum, a `Stringable` URL object, dates, markup the application trusts, and menu items that are looped over for their children. Some strings contain characters that must be escaped. `--scale` multiplies the number of products, reviews, and content blocks, which shows how the cost of an engine grows with the data.

## How the templates are written

All engines receive the same data and must produce the same HTML; only whitespace may differ, and the script verifies it. Within that rule, each set of templates is written the way the engine's documentation suggests, with the engine's own means, and not tuned for speed. Where an engine lacks a mechanism, its templates solve the task the way its users would, instead of imitating another engine.

| Task | Boiler | Twig | Blade | Plates |
| --- | --- | --- | --- | --- |
| Layouts | `layout()` | `extends` | `@extends` | `layout()`, passing on the page data with `$this->data()` |
| Page title, extended by the shop layout | `rewrite()` | a block nested in the block | `@section` around `@yield` | layout data |
| Head tags added by layout and page | `append()` to a closure default | `parent()` | `@parent` | `push()` and `unshift()` |
| Sidebar with a default the page adds to | `yield()` default and `prepend()` | block and `parent()` | `@section` … `@show` and `@parent` | a second section |
| Banner the shop layout sets and a page replaces or drops | `section()` in the layout | block | `@section` | a flag in the layout data |
| Scripts of layout and page | `append()` | block and `parent()` | `@push` and `@prepend` | `push()` and `unshift()` |
| Script of a content block | `append()` in the block's template | the page loops over the blocks again | `@push` in the block's template | the page loops over the blocks again |
| Card around varying markup, also in loops | `component()` … `end()` | `embed` | `<x-card>` | a partial with the card as its layout |
| Partials | `include()` | `include()` | `@include` | `insert()` with the values it needs |
| Site-wide data | engine defaults | globals | `share()` | `addData()` |
| Helpers for prices and icons | `method()` | a filter and a function | global functions | `registerFunction()` |
| Trusted markup | a trusted class | `raw` | `{!! !!}` | printed as is |

Boiler is measured twice: with automatic escaping, next to Twig and Blade, and with `Engine::unescaped()` and `$this->escape()` calls, next to Plates.

Blade runs on `illuminate/view` without a Laravel application. Laravel finds anonymous components through the application, so the benchmark registers the card component by name instead; the compiled templates are the same.

## Lifecycles

What a render costs depends on what PHP has to do around it, so the benchmark measures three lifecycles:

| Lifecycle | Runs in | Each render is |
| --- | --- | --- |
| `request` | PHP-FPM | a request of its own: PHP starts without loaded classes, creates the engine, renders one page, and discards everything |
| `worker` | FrankenPHP in worker mode | a request to a worker that booted once and keeps its engine |
| `loop` | the benchmark process | a pass of a loop that keeps its engine, without a server |

The script starts `php-fpm` and `frankenphp` itself, on a free local port and with one worker each, sends the requests one after another, and stops the servers again. Their configuration and logs are written to `cache/`. A lifecycle whose server is not installed is skipped with a note. Both servers must run PHP 8.5.

The data of a page is created outside the timed part in every lifecycle. In a request, the timed part covers what a request needs the engine for: loading its classes from OPcache, setting it up, and rendering. In `worker` and `loop` it covers the render.

`loop` is the quick check that needs no server. On the same PHP build it stays within a few percent of a real worker, so it serves to compare Boiler before and after a change. A request cannot be reproduced that way: a loop that creates a fresh engine for every render still keeps the classes and static caches that a real request has to load again, so `request` always runs on PHP-FPM.

## How to read the results

Use the benchmark to answer a narrow question: did Boiler get slower on these pages?

Each lifecycle prints a table with the milliseconds per render for each page, and `total` for one round of all three. The values come from the fastest iteration, which is the one least disturbed by other load on the machine. `spread` shows how much slower the slowest iteration was; when it is large, run more iterations or close other programs.

`memory` is the peak that the timed part adds: in a request the engine with its classes and the render, in `worker` and `loop` the render alone. `held`, in the `worker` table, is what the engine keeps in memory between requests.

Compare the engines within a table. `request` and `loop` run on the same PHP when `php-fpm` belongs to the PHP that runs the script, so the difference between them is what a request costs on top of a render. FrankenPHP embeds a PHP build of its own, which can be much slower or faster than the other one, so the `worker` numbers do not compare with those of the other tables.

Keep these limits in mind:

- results depend on PHP version and build, OPcache settings, hardware, and workload shape
- three pages cannot represent every template structure or application architecture
- the pages leave out some of Boiler's features: template namespaces and multiple directories, custom filters and escapers, the `sanitize` filter, and array helpers such as `map()` and `sorted()`
- the numbers are useful for internal regression checks and local comparisons, not as universal rankings or proofs that one engine always wins
- the servers handle one request at a time in one worker, so nothing here measures concurrency
- Blade runs without Laravel, so its `request` results leave out the framework bootstrap that a Laravel request pays for

## Run the benchmark

Install the benchmark's own dependencies once, then run it from the repository root:

```bash
composer install --working-dir=bench
composer benchmark
composer benchmark -- --lifecycle=loop
composer benchmark -- --scale=4 --runs=200 --iterations=5
```

The `request` lifecycle needs `php-fpm` and the `worker` lifecycle needs `frankenphp`. The script looks for `php-fpm` next to the PHP that runs it and on the `PATH`, also under the versioned name Debian uses, such as `php-fpm8.5`, and for `frankenphp` on the `PATH`.

| Option | Default | Meaning |
| --- | --- | --- |
| `--lifecycle` | `all` | `request`, `worker`, `loop`, or `all` |
| `--runs` | `100` | rounds per iteration; a round renders each page once |
| `--iterations` | `3` | measured iterations per engine and lifecycle |
| `--scale` | `1` | multiplies the number of products, reviews, and content blocks |
| `--php-fpm` | detected | path to the `php-fpm` binary |
| `--frankenphp` | detected | path to the `frankenphp` binary |

### PHP settings

Results are not useful for fair engine comparisons unless Xdebug and PCOV are disabled and OPcache is enabled. The script starts the servers that way. The `loop` lifecycle runs in the benchmark process itself, which `composer benchmark` starts with the same settings; the script warns when one is off:

- `xdebug.mode=off` and `pcov.enabled=0`: both extensions add substantial runtime overhead, especially for Boiler's proxy-based auto escaping.
- `opcache.enable_cli=1`: without OPcache, PHP compiles a template file again every time it is included. That outweighs the engine's own work for Boiler, Plates, and Blade, while Twig loads each compiled template once per process as a class.
- `opcache.file_update_protection=0`: by default, OPcache does not cache files changed within the last two seconds, and the script compiles the Blade templates right before it measures.

To run the script without Composer, pass the settings yourself:

```bash
php -d xdebug.mode=off -d pcov.enabled=0 -d opcache.enable_cli=1 -d opcache.file_update_protection=0 bench/run.php
```
