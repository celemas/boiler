<?php foreach ($this->each('eachforwarding', ['rows' => $rows]) as $row): ?>
<b><?= $row['value'] ?></b>
<?php endforeach; ?>
