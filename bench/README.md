# Boiler benchmark

This benchmark measures one feature-rich page render across Boiler, Twig, Laravel Blade, and Plates. It is meant to approximate a realistic steady-state page render and is used mainly internally to catch regressions, not to benchmark every feature in isolation.

## What it covers

The benchmark renders one canonical catalog page with:

- layout inheritance
- partials and repeated partials inside loops
- sections or blocks for script output
- nested arrays, nested loops, and conditionals
- object-backed view data
- one iterator-backed collection
- Boiler renders with auto escaping and manual escaping

The script resets compiled template caches, warms them up, and verifies that all engines produce equivalent output.

It can run in two lifecycle modes:

- `worker` reuses the same engine instance across measured renders
- `request` creates a fresh engine instance for every measured render

Use `request` when you want to reduce the impact of persistent userland engine caches. Use `worker` when you want to approximate a long-running worker process. Neither mode is a full deployment simulation. `worker` is closer to a steady-state long-running process, while `request` mainly isolates the cost of fresh engine construction inside one benchmark process.

## How to read the results

Use the benchmark to answer a narrow question: did Boiler get slower on this canonical page render?

Keep these limits in mind:

- results depend on PHP version, OPcache settings, hardware, and workload shape
- one benchmark cannot represent every template structure or application architecture
- the numbers are useful for internal regression checks and local comparisons, not as universal rankings or proofs that one engine always wins
- `worker` results are usually the more representative steady-state numbers
- Blade runs on `illuminate/view` without a Laravel application, so its `request` results leave out the framework bootstrap that a Laravel request pays for
- `request` time results are useful, but `request` memory results should be read only as a comparative stress signal for repeated fresh engine construction in one process, not as per-request php-fpm memory usage

## Run the benchmark

You can run the benchmark from the repository root or from inside `bench/`.

Run the benchmark with Xdebug and PCOV disabled and with OPcache enabled for the CLI. Results without these settings are not useful for fair engine comparisons. `composer benchmark` already sets all of them, and the benchmark script warns when one is off:

- `xdebug.mode=off` and `pcov.enabled=0`: both extensions add substantial runtime overhead, especially for Boiler's proxy-based auto escaping.
- `opcache.enable_cli=1`: without OPcache, PHP compiles a template file again every time it is included. That outweighs the engine's own work for Boiler, Plates, and Blade, while Twig loads each compiled template once per process as a class.
- `opcache.file_update_protection=0`: by default, OPcache does not cache files changed within the last two seconds, and the script compiles the Blade templates right before it measures.

### From the repository root

```bash
composer benchmark
composer benchmark -- --lifecycle=request --runs=2000 --iterations=5
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

4. Override the default run count and iteration count when you want a slower or deeper run:

   ```bash
   php -d xdebug.mode=off -d pcov.enabled=0 -d opcache.enable_cli=1 -d opcache.file_update_protection=0 run.php --runs=10000 --iterations=5
   ```

5. Choose a lifecycle mode when you want to compare a reused engine with a freshly created engine per render:

   ```bash
   php -d xdebug.mode=off -d pcov.enabled=0 -d opcache.enable_cli=1 -d opcache.file_update_protection=0 run.php --lifecycle=worker
   php -d xdebug.mode=off -d pcov.enabled=0 -d opcache.enable_cli=1 -d opcache.file_update_protection=0 run.php --lifecycle=request
   php -d xdebug.mode=off -d pcov.enabled=0 -d opcache.enable_cli=1 -d opcache.file_update_protection=0 run.php --lifecycle=both
   ```

## Defaults

By default, the script runs:

- `1000` renders per engine
- `3` measured iterations
- `both` lifecycle mode

The memory column reports `peak+`, which is the additional peak memory used within a measured iteration after warmup.

In `worker` mode, this is a reasonable steady-state memory indicator. In `request` mode, it also includes the allocator pressure of repeatedly creating fresh engine instances inside one benchmark process.
