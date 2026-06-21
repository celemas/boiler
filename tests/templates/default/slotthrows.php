<?php $this->insert('slotbox', slot: function (): void {
	throw new \RuntimeException('boom in slot');
}); ?>
