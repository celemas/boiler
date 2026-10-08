<?php $this->layout('sectiondefaultbase') ?>
<?php $this->insert('sectiondefaulttitle') ?>
<?php $this->section('sidebar') ?><?php $this->insert('sectiondefaultwidget') ?><?php $this->end() ?>
<?php $this->append('js') ?>[mid-js]<?php $this->end() ?>
<?= $this->slot() ?>
