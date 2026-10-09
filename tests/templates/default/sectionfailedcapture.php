<?php try {
	$this->include('sectionfailedcapturepartial');
} catch (\Celema\Boiler\Exception\RenderException) {
} ?>
<?php $this->section('s') ?>[page]<?php $this->end() ?>
<?= $this->yield('s') ?>
