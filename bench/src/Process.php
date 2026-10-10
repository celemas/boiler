<?php

declare(strict_types=1);

namespace Celema\Boiler\Bench;

use Closure;
use RuntimeException;
use Throwable;

/** A server the benchmark starts for itself and stops again. */
final class Process
{
	/** @var resource|null */
	private $handle = null;

	/**
	 * @param list<string> $command
	 * @param array<string, string> $env added to the environment of this process
	 */
	public function __construct(
		private readonly array $command,
		private readonly string $log,
		private readonly array $env = [],
	) {}

	/** @param Closure(): mixed $ready throws while the server does not answer yet */
	public function start(Closure $ready, float $timeout = 10.0): void
	{
		file_put_contents($this->log, '');
		$handle = proc_open(
			$this->command,
			[['file', '/dev/null', 'r'], ['file', $this->log, 'a'], ['file', $this->log, 'a']],
			$pipes,
			null,
			[...getenv(), ...$this->env],
		);

		if ($handle === false) {
			throw new RuntimeException('Could not start ' . $this->command[0]);
		}

		$this->handle = $handle;
		$deadline = microtime(true) + $timeout;

		while (true) {
			try {
				$ready();

				return;
			} catch (Throwable $e) {
				if (microtime(true) > $deadline || !proc_get_status($handle)['running']) {
					$this->stop();

					throw new RuntimeException(
						"{$this->command[0]} did not come up: {$e->getMessage()}\n" . file_get_contents($this->log),
						previous: $e,
					);
				}

				usleep(50_000);
			}
		}
	}

	public function stop(): void
	{
		if ($this->handle === null) {
			return;
		}

		proc_terminate($this->handle);
		$deadline = microtime(true) + 3.0;

		while (proc_get_status($this->handle)['running']) {
			if (microtime(true) > $deadline) {
				proc_terminate($this->handle, 9);

				break;
			}

			usleep(20_000);
		}

		proc_close($this->handle);
		$this->handle = null;
	}

	/**
	 * Runs a call that reports failure through its return value, without the
	 * warning PHP adds to it.
	 *
	 * @template T
	 *
	 * @param Closure(): T $call
	 *
	 * @return T
	 */
	public static function quietly(Closure $call): mixed
	{
		set_error_handler(static fn(): bool => true);

		try {
			return $call();
		} finally {
			restore_error_handler();
		}
	}

	/** The first candidate that is an executable file, or a command found on the PATH. */
	public static function find(string ...$candidates): ?string
	{
		$paths = explode(PATH_SEPARATOR, (string) getenv('PATH'));

		foreach ($candidates as $candidate) {
			$files = str_contains($candidate, '/')
				? [$candidate]
				: array_map(static fn(string $path): string => $path . '/' . $candidate, $paths);

			foreach ($files as $file) {
				if (is_file($file) && is_executable($file)) {
					return $file;
				}
			}
		}

		return null;
	}

	/** @return list<string> the lines the command printed */
	public static function output(string ...$command): array
	{
		exec(implode(' ', array_map(escapeshellarg(...), $command)) . ' 2>&1', $lines);

		return $lines;
	}

	public static function freePort(): int
	{
		$socket = stream_socket_server('tcp://127.0.0.1:0');

		if ($socket === false) {
			throw new RuntimeException('Could not find a free port');
		}

		$name = (string) stream_socket_get_name($socket, false);
		fclose($socket);

		return (int) substr($name, (int) strrpos($name, ':') + 1);
	}
}
