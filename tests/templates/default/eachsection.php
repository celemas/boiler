<?php foreach ($this->each('slotrows', ['rows' => $rows]) as $row): ?>
<?php $this->begin('last'); ?>[<?= $row['name'] ?>]<?php $this->end(); ?>
<i><?= $row['name'] ?></i>
<?php endforeach; ?>
<p><?= $this->section('last') ?></p>
