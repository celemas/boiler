<?php $this->layout('sectiondefaultbase') ?>
<?php $this->include('sectiondefaulttitle') ?>
<?php $this->section('sidebar') ?><?php $this->include('sectiondefaultwidget') ?><?php $this->end() ?>
<?php $this->append('js') ?>[mid-js]<?php $this->end() ?>
<?= $this->slot() ?>
