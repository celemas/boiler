<?php foreach ($this->each('slotrows', ['rows' => $rows]) as $row): ?>
<?php if ($row['name']->is('a')) {
	$this->section('open');
} else {
	$this->end();
} ?>
<?php endforeach; ?>
