<?php

declare(strict_types=1);

namespace Celema\Boiler\Bench;

use RuntimeException;

/** A minimal FastCGI client: one GET request per connection, as PHP-FPM expects it from a web server. */
final class FastCgi
{
	private const int BEGIN_REQUEST = 1;
	private const int END_REQUEST = 3;
	private const int PARAMS = 4;
	private const int STDIN = 5;
	private const int STDOUT = 6;
	private const int STDERR = 7;
	private const int RESPONDER = 1;

	/** @return string the body of the response */
	public static function get(int $port, string $script, string $query): string
	{
		$socket = Process::quietly(static fn() => stream_socket_client("tcp://127.0.0.1:{$port}", timeout: 5.0));

		if ($socket === false) {
			throw new RuntimeException("Nothing accepts FastCGI connections on port {$port}");
		}

		stream_set_timeout($socket, 10);
		$params = '';

		foreach ([
			'SCRIPT_FILENAME' => $script,
			'SCRIPT_NAME' => '/' . basename($script),
			'REQUEST_URI' => '/' . basename($script) . '?' . $query,
			'QUERY_STRING' => $query,
			'REQUEST_METHOD' => 'GET',
			'SERVER_PROTOCOL' => 'HTTP/1.1',
			'GATEWAY_INTERFACE' => 'CGI/1.1',
		] as $name => $value) {
			$params .= self::length($name) . self::length($value) . $name . $value;
		}

		fwrite(
			$socket,
			self::record(self::BEGIN_REQUEST, pack('nCx5', self::RESPONDER, 0))
				. self::record(self::PARAMS, $params)
				. self::record(self::PARAMS, '')
				. self::record(self::STDIN, ''),
		);
		$output = '';
		$errors = '';

		while (($header = self::read($socket, 8)) !== '') {
			/** @var array{type: int, length: int, padding: int} $record */
			$record = unpack('Cversion/Ctype/nid/nlength/Cpadding/Creserved', $header);
			$content = substr(self::read($socket, $record['length'] + $record['padding']), 0, $record['length']);

			if ($record['type'] === self::END_REQUEST) {
				break;
			}

			if ($record['type'] === self::STDOUT) {
				$output .= $content;
			} elseif ($record['type'] === self::STDERR) {
				$errors .= $content;
			}
		}

		$timedOut = stream_get_meta_data($socket)['timed_out'];
		fclose($socket);
		[$headers, $body] = [...explode("\r\n\r\n", $output, 2), ''];

		if ($timedOut || $errors !== '' || preg_match('/^Status: (?!200)/mi', $headers)) {
			throw new RuntimeException("FastCGI request failed ({$query}): {$errors}{$headers}\n{$body}");
		}

		return $body;
	}

	private static function record(int $type, string $content): string
	{
		return pack('CCnnCC', 1, $type, 1, strlen($content), 0, 0) . $content;
	}

	/** Lengths from 128 bytes take four bytes with the high bit set. */
	private static function length(string $value): string
	{
		$length = strlen($value);

		return $length < 128 ? chr($length) : pack('N', $length | 0x8000_0000);
	}

	/** @param resource $socket */
	private static function read($socket, int $bytes): string
	{
		$data = '';

		while (strlen($data) < $bytes && !feof($socket)) {
			$chunk = fread($socket, $bytes - strlen($data));

			if ($chunk === false) {
				break;
			}

			$data .= $chunk;
		}

		return $data;
	}
}
