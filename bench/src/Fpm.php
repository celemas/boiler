<?php

declare(strict_types=1);

namespace Celema\Boiler\Bench;

use LogicException;
use Override;

/**
 * Renders each page in a PHP-FPM request of its own, which starts without
 * loaded classes or engine state.
 */
final class Fpm implements Runtime
{
	private ?Process $server = null;
	private int $port = 0;

	/** @var Candidate<object>|null */
	private ?Candidate $candidate = null;

	private function __construct(
		private readonly string $binary,
		private readonly string $version,
		private readonly string $dir,
		private readonly int $scale,
	) {}

	/** @return self|string the runtime, or the reason why it cannot run */
	public static function detect(string $dir, int $scale, ?string $binary): self|string
	{
		// Debian installs it as /usr/sbin/php-fpm8.5, outside the PATH of a user.
		$versioned = 'php-fpm' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
		$sbin = dirname(PHP_BINARY) . '/../sbin/';
		$found = $binary !== null
			? Process::find($binary)
			: Process::find($sbin . 'php-fpm', $sbin . $versioned, 'php-fpm', $versioned);

		if ($found === null) {
			return $binary === null
				? 'php-fpm not found; pass --php-fpm=/path/to/php-fpm'
				: "{$binary} is not an executable file or a command on the PATH";
		}

		if (!preg_match('/^PHP (\d\S*)/', Process::output($found, '-v')[0] ?? '', $match)) {
			return "{$found} does not report a PHP version";
		}

		if (version_compare($match[1], '8.5', '<')) {
			return "{$found} runs PHP {$match[1]}, the engines need PHP 8.5";
		}

		return new self($found, $match[1], $dir, $scale);
	}

	#[Override]
	public function label(): string
	{
		return 'PHP-FPM, PHP ' . $this->version;
	}

	#[Override]
	public function serve(Candidate $candidate): void
	{
		$this->candidate = $candidate;

		if ($this->server !== null) {
			return;
		}

		$cache = $this->dir . '/cache/fpm';
		is_dir($cache) || mkdir($cache, 0o755, true);
		$this->port = Process::freePort();
		file_put_contents($cache . '/php-fpm.conf', <<<CONF
			[global]
			error_log = {$cache}/server.log
			daemonize = no

			[bench]
			listen = 127.0.0.1:{$this->port}
			; One worker, so that every request finds the same warm OPcache.
			pm = static
			pm.max_children = 1
			catch_workers_output = yes

			CONF);

		$this->server = new Process(
			[
				$this->binary,
				'--fpm-config',
				$cache . '/php-fpm.conf',
				'-d',
				'xdebug.mode=off',
				'-d',
				'pcov.enabled=0',
				'-d',
				'opcache.enable=1',
				// The templates are compiled moments before OPcache sees them.
				'-d',
				'opcache.file_update_protection=0',
			],
			$cache . '/server.log',
		);
		$this->server->start(fn(): Sample => $this->sample('listing'));
	}

	#[Override]
	public function sample(string $page, bool $html = false): Sample
	{
		if ($this->candidate === null) {
			throw new LogicException('No candidate to render with');
		}

		$query = ['candidate' => $this->candidate->id, 'page' => $page, 'scale' => $this->scale];

		return Sample::fromJson(FastCgi::get(
			$this->port,
			$this->dir . '/server/request.php',
			http_build_query($html ? $query + ['html' => 1] : $query),
		));
	}

	#[Override]
	public function stop(): void
	{
		$this->server?->stop();
		$this->server = null;
	}
}
