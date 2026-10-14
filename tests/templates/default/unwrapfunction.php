<?= u($title) === 'Tom & Jerry' ? 'same' : 'other' ?>|<?= u($empty) ? 'filled' : 'blank' ?>|<?= u($count) + 1 ?>|<?= json_encode(u(['items' => $items, 'title' => $title])) ?>
