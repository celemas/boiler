<?= $list[0] ?>|<?= $list[0]->paragraph('a') ?>
<?= $deep['outer']['inner'] ?>
<?php foreach ($iter as $item): ?><?= $item->paragraph('b') ?><?php endforeach ?>
