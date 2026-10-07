<?php $this->section('list'); ?>
<?php foreach ($this->each('slotrows', ['rows' => $rows]) as $row): ?>
<?php $this->end(); ?>
<?php endforeach; ?>
