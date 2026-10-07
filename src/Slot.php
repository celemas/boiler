<?php

declare(strict_types=1);

namespace Celema\Boiler;

/**
 * What a template prints with `$this->slot()`.
 *
 * @internal
 */
interface Slot
{
	/** Whether there is anything to print, as `hasSlot()` reports it. */
	public function filled(): bool;

	/** @param array<array-key, mixed> $data */
	public function render(array $data): string;
}
