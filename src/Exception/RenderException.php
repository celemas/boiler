<?php

declare(strict_types=1);

namespace Celema\Boiler\Exception;

use Celema\Boiler\Location;
use Throwable;

final class RenderException extends RuntimeException implements TemplateException
{
	public static function fromThrowable(string $path, Throwable $throwable): self
	{
		$location =
			$throwable instanceof RuntimeException || $throwable instanceof LogicException
				? $throwable->location()
				: null;
		$location ??= Location::fromThrowable($path, $throwable);
		$message = $location->line === null
			? "Template rendering error ({$path})"
			: "Template rendering error at {$location}";

		// Keep the original code so handlers that read it, e.g. for an HTTP
		// status, still can; non-integer codes such as PDO's SQLSTATE cannot
		// be passed on.
		$code = $throwable->getCode();

		return new self(
			$message . ': ' . $throwable->getMessage(),
			is_int($code) ? $code : 0,
			$throwable,
			$location,
		);
	}
}
