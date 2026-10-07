<?php $this->section('list'); ?>
<?php foreach ($this->each('slotrows', ['rows' => $rows]) as $row): ?><b><?= $row['name'] ?></b><?php endforeach; ?>
<?php $this->end(); ?>
<div><?= $this->yield('list') ?></div>
