<ul>
<?php foreach ($this->unwrap($rows) as $row): ?>
	<li><?php $this->slot($row); ?></li>
<?php endforeach; ?>
</ul>
