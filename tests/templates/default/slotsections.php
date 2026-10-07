<p><?= $this->slot(['name' => 'output']) ?></p>
<?php $this->prepend('s') ?>[<?= $this->slot(['name' => 'prepend']) ?>]<?php $this->end() ?>
<?php $this->section('s') ?>[<?= $this->slot(['name' => '<main>']) ?>]<?php $this->end() ?>
<?php $this->append('s') ?>[<?= $this->slot(['name' => 'append']) ?>]<?php $this->end() ?>
