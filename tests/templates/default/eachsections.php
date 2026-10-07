<?php $this->layout('eachsectionslayout') ?>
<?php foreach ($this->each('slotsections') as $row): ?><b><?= $row['name'] ?></b><?php endforeach ?>
