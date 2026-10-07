<?php foreach ($this->each('slotrows', ['rows' => $rows]) as $row): ?>
<?php $this->section('last'); ?>[<?= $row['name'] ?>]<?php $this->end(); ?>
<i><?= $row['name'] ?></i>
<?php endforeach; ?>
<p><?= $this->yield('last') ?></p>
