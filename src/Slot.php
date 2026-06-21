<?php

declare(strict_types=1);

namespace Celemas\Boiler;

/** @api */
final class Slot
{
	/**
	 * @param non-empty-string $path
	 * @param array<array-key, mixed> $context
	 */
	private function __construct(
		private readonly string $path,
		private readonly array $context = [],
	) {}

	/**
	 * Renders a template when the inserted template calls `$this->slot()`.
	 *
	 * The slot template receives the caller context, this context, and the data
	 * passed to `$this->slot([...])`, with later values overriding earlier ones.
	 *
	 * @param non-empty-string $path
	 * @param array<array-key, mixed> $context
	 */
	public static function template(string $path, array $context = []): self
	{
		return new self($path, $context);
	}

	/**
	 * @internal
	 *
	 * @return non-empty-string
	 */
	public function path(): string
	{
		return $this->path;
	}

	/**
	 * @internal
	 *
	 * @return array<array-key, mixed>
	 */
	public function context(): array
	{
		return $this->context;
	}
}
