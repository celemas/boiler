<?php $this->begin('list'); ?>
<?php foreach ($this->each('slotrows', ['rows' => $rows]) as $row): ?>
<?php $this->end(); ?>
<?php endforeach; ?>
