<?php

declare(strict_types=1);

namespace Celema\Boiler\Bench;

use DateTimeImmutable;

final readonly class Review
{
	/** @mago-expect lint:excessive-parameter-list A view model: its fields are what the review shows. */
	public function __construct(
		public string $author,
		public int $rating,
		public string $title,
		public string $body,
		public DateTimeImmutable $date,
		public bool $verified,
	) {}
}
