<?php

declare(strict_types=1);

namespace Celema\Boiler\Bench;

use Override;
use RuntimeException;

/**
 * Renders in a FrankenPHP worker, which boots once and keeps its engine
 * between requests.
 */
final class FrankenPhp implements Runtime
{
	private ?Process $server = null;
	private int $port = 0;

	private function __construct(
		private readonly string $binary,
		private readonly string $version,
		private readonly string $php,
		private readonly string $dir,
		private readonly int $scale,
	) {}

	/** @return self|string the runtime, or the reason why it cannot run */
	public static function detect(string $dir, int $scale, ?string $binary): self|string
	{
		$found = Process::find($binary ?? 'frankenphp');

		if ($found === null) {
			return $binary === null
				? 'frankenphp not found; pass --frankenphp=/path/to/frankenphp'
				: "{$binary} is not an executable file or a command on the PATH";
		}

		$line = implode(' ', Process::output($found, 'version'));

		if (!preg_match('/FrankenPHP v?(\S+) PHP (\d\S*)/', $line, $match)) {
			return "{$found} does not report its versions";
		}

		if (version_compare($match[2], '8.5', '<')) {
			return "{$found} embeds PHP {$match[2]}, the engines need PHP 8.5";
		}

		return new self($found, $match[1], $match[2], $dir, $scale);
	}

	#[Override]
	public function label(): string
	{
		return "FrankenPHP {$this->version}, PHP {$this->php}";
	}

	/** Starts a server per candidate, so that no engine shares a process with another. */
	#[Override]
	public function serve(Candidate $candidate): void
	{
		$this->stop();
		$cache = $this->dir . '/cache/frankenphp';
		is_dir($cache) || mkdir($cache, 0o755, true);
		$this->port = Process::freePort();
		file_put_contents($cache . '/Caddyfile', <<<CADDY
			{
				admin off
				auto_https off
				persist_config off
				frankenphp {
					php_ini opcache.file_update_protection 0
					worker {
						file {$this->dir}/server/worker.php
						num 1
						env BENCH_CANDIDATE {$candidate->id}
						env BENCH_SCALE {$this->scale}
					}
				}
			}

			http://127.0.0.1:{$this->port} {
				root * {$this->dir}/server
				php_server
			}

			CADDY);

		$this->server = new Process(
			[$this->binary, 'run', '--config', $cache . '/Caddyfile', '--adapter', 'caddyfile'],
			$cache . '/server.log',
			// Keeps what Caddy stores out of the home directory.
			['XDG_DATA_HOME' => $cache, 'XDG_CONFIG_HOME' => $cache],
		);
		// A request that arrives while the workers start may never be answered,
		// so the check gives up quickly and tries again.
		$this->server->start(fn(): string => $this->get(['page' => 'listing'], timeout: 1.0));
	}

	#[Override]
	public function sample(string $page, bool $html = false): Sample
	{
		return Sample::fromJson($this->get($html ? ['page' => $page, 'html' => 1] : ['page' => $page]));
	}

	#[Override]
	public function stop(): void
	{
		$this->server?->stop();
		$this->server = null;
	}

	/** @param array<string, int|string> $query */
	private function get(array $query, float $timeout = 10.0): string
	{
		$url = "http://127.0.0.1:{$this->port}/worker.php?" . http_build_query($query);
		$context = stream_context_create(['http' => ['timeout' => $timeout, 'ignore_errors' => true]]);
		$body = Process::quietly(static fn() => file_get_contents($url, false, $context));

		if ($body === false) {
			throw new RuntimeException('FrankenPHP does not answer');
		}

		return $body;
	}
}
