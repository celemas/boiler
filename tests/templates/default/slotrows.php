<ul>
<?php foreach ($this->unwrap($rows) as $row): ?>
	<li><?= $this->slot($row) ?></li>
<?php endforeach; ?>
</ul>
