<?php $this->include('included'); ?>
<?php $this->include('included', ['int' => 23]); ?>
<?php $this->include('included', ['text' => '<b>Overwrite</b>', 'int' => 13]); ?>
