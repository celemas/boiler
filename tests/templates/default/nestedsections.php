<?php $this->begin('outer'); ?><b><?php $this->begin('inner'); ?>inner<?php $this->end(); ?></b><?php $this->end(); ?>
<?= $this->section('outer') ?>|<?= $this->section('inner') ?>
