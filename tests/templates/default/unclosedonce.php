<?php $this->section('scripts'); ?>script<?php if (!$fail) {
	$this->end();
	echo $this->yield('scripts');
} ?>
