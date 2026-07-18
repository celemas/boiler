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

		return new self(
			$message . ': ' . $throwable->getMessage(),
			previous: $throwable,
			location: $location,
		);
	}
}
