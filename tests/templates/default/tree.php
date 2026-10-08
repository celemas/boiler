<?php $this->layout('treelayout') ?>
<?php $this->component('treebox') ?><?= $name ?><?= $this->children($kids) ?><?php $this->end() ?>
