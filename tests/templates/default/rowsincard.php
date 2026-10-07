<?php foreach ($this->unwrap($rows) as $row): ?><?php $this->component('slotbox') ?><?= $this->slot($row) ?><?php $this->end() ?><?php endforeach ?>
