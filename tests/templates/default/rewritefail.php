<?php try {
	$this->include('rewritefailcard');
} catch (\Celema\Boiler\Exception\RenderException) {
} ?>
<?php $this->append('s') ?>[after]<?php $this->end() ?>
[<?= $this->yield('t', '') ?>][<?= $this->yield('s', '') ?>]
