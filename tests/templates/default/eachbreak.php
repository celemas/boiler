<?php foreach ($this->each('slotrows', ['rows' => $rows]) as $row): ?>
<b><?= $row['name'] ?></b>
<?php break; ?>
<?php endforeach; ?>
<p>after</p>
