<?php try {
	$this->insert('sectionfailedcapturepartial');
} catch (\Celema\Boiler\Exception\RenderException) {
} ?>
<?php $this->section('s') ?>[page]<?php $this->end() ?>
<?= $this->yield('s') ?>
