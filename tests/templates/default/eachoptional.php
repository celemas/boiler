<?php $loop = $this->each('slotrows', ['rows' => $rows]); ?>
<?php if ($iterate): ?>
<?php foreach ($loop as $row): ?><b><?= $row['name'] ?></b><?php endforeach; ?>
<?php endif; ?>
