<?php

declare(strict_types=1);

namespace Celema\Boiler\Exception;

use Celema\Boiler\Location;
use Throwable;

final class LogicException extends \LogicException implements TemplateException
{
	public function __construct(
		string $message = '',
		int $code = 0,
		?Throwable $previous = null,
		private readonly ?Location $location = null,
	) {
		parent::__construct($message, $code, $previous);

		if ($location !== null && $location->line !== null) {
			$this->file = $location->path;
			$this->line = $location->line;
		}
	}

	/** @api */
	public function location(): ?Location
	{
		return $this->location;
	}
}
