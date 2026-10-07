<?php foreach ($this->each('slotrows', ['rows' => $rows]) as $row): ?>
<?php throw new \RuntimeException('boom in loop'); ?>
<?php endforeach; ?>
