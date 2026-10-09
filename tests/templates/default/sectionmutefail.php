<?php try {
	$this->include('sectionmutefailcard');
} catch (\Celema\Boiler\Exception\RenderException) {
} ?>
<?php $this->append('s') ?>[after]<?php $this->end() ?>
<?= $this->yield('s', '') ?>
