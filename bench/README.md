# Boiler benchmark

This benchmark renders three pages of a shop site with Boiler, Twig, Laravel Blade, and Plates. It is meant to approximate realistic page renders and is used mainly internally to catch regressions, not to benchmark every feature in isolation.

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

The script resets the compiled template caches, warms them up, and can run in two lifecycle modes:

- `worker` reuses the same engine instance across measured renders
- `request` creates a fresh engine instance for every measured render

Use `request` when you want to reduce the impact of persistent userland engine caches. Use `worker` when you want to approximate a long-running worker process. Neither mode is a full deployment simulation. `worker` is closer to a steady-state long-running process, while `request` mainly isolates the cost of fresh engine construction inside one benchmark process.

## How to read the results

Use the benchmark to answer a narrow question: did Boiler get slower on these pages?

The time table shows milliseconds per render for each page, and `total` for one round of all three. The values come from the fastest iteration, which is the one least disturbed by other load on the machine. `spread` shows how much slower the slowest iteration was; when it is large, run more iterations or close other programs.

The memory table comes from one fresh PHP process per engine, so that nothing another engine loaded counts:

- `loaded` is the memory still in use after each page was rendered once: the engine, its loaded templates, and what rendering left behind
- `render` is the additional peak while the pages render again

Keep these limits in mind:

- results depend on PHP version, OPcache settings, hardware, and workload shape
- three pages cannot represent every template structure or application architecture
- the pages leave out some of Boiler's features: template namespaces and multiple directories, custom filters and escapers, the `sanitize` filter, and array helpers such as `map()` and `sorted()`
- the numbers are useful for internal regression checks and local comparisons, not as universal rankings or proofs that one engine always wins
- `worker` results are usually the more representative steady-state numbers
- Blade's `request` results leave out the framework bootstrap that a Laravel request pays for
- in `request` mode, Twig keeps the classes of its compiled templates loaded for the whole process, while a PHP-FPM request loads them from OPcache again

## Run the benchmark

You can run the benchmark from the repository root or from inside `bench/`.

Run the benchmark with Xdebug and PCOV disabled and with OPcache enabled for the CLI. Results without these settings are not useful for fair engine comparisons. `composer benchmark` already sets all of them, and the benchmark script warns when one is off:

- `xdebug.mode=off` and `pcov.enabled=0`: both extensions add substantial runtime overhead, especially for Boiler's proxy-based auto escaping.
- `opcache.enable_cli=1`: without OPcache, PHP compiles a template file again every time it is included. That outweighs the engine's own work for Boiler, Plates, and Blade, while Twig loads each compiled template once per process as a class.
- `opcache.file_update_protection=0`: by default, OPcache does not cache files changed within the last two seconds, and the script compiles the Blade templates right before it measures.

### From the repository root

```bash
composer benchmark
composer benchmark -- --lifecycle=request --runs=1000 --iterations=5
composer benchmark -- --scale=4
```

### From inside `bench/`

1. Change into the benchmark directory:

   ```bash
   cd bench
   ```

2. Install benchmark dependencies:

   ```bash
   composer install
   ```

3. Run the benchmark with the default settings:

   ```bash
   php -d xdebug.mode=off -d pcov.enabled=0 -d opcache.enable_cli=1 -d opcache.file_update_protection=0 run.php
   ```

4. Override the default round count and iteration count when you want a slower or deeper run:

   ```bash
   php -d xdebug.mode=off -d pcov.enabled=0 -d opcache.enable_cli=1 -d opcache.file_update_protection=0 run.php --runs=1000 --iterations=5
   ```

5. Raise the scale to see how the engines cope with more data:

   ```bash
   php -d xdebug.mode=off -d pcov.enabled=0 -d opcache.enable_cli=1 -d opcache.file_update_protection=0 run.php --scale=4
   ```

6. Choose a lifecycle mode when you want to compare a reused engine with a freshly created engine per render:

   ```bash
   php -d xdebug.mode=off -d pcov.enabled=0 -d opcache.enable_cli=1 -d opcache.file_update_protection=0 run.php --lifecycle=worker
   php -d xdebug.mode=off -d pcov.enabled=0 -d opcache.enable_cli=1 -d opcache.file_update_protection=0 run.php --lifecycle=request
   php -d xdebug.mode=off -d pcov.enabled=0 -d opcache.enable_cli=1 -d opcache.file_update_protection=0 run.php --lifecycle=both
   ```

## Defaults

By default, the script runs:

- `300` rounds per engine, each rendering the three pages
- `3` measured iterations
- scale `1`
- `both` lifecycle modes
