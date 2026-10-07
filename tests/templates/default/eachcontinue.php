<?php foreach ($this->each('slotrows', ['rows' => $rows]) as $row): ?>
<?php if ($row['name']->is('b')) {
	continue;
} ?>
<b><?= $row['name'] ?></b>
<?php endforeach; ?>
