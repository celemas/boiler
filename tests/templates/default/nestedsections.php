<?php $this->section('outer'); ?><b><?php $this->section('inner'); ?>inner<?php $this->end(); ?></b><?php $this->end(); ?>
<?= $this->yield('outer') ?>|<?= $this->yield('inner') ?>
