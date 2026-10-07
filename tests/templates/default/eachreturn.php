<?php foreach ($this->each('slotrows', ['rows' => $rows]) as $row): ?>
<b><?= $row['name'] ?></b>
<?php return; ?>
<?php endforeach; ?>
<p>not reached</p>
