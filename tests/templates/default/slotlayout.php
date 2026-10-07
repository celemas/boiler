<?php if ($this->hasSlot()): ?>filled<?php else: ?>empty<?php endif ?>[<?= $this->slot() ?>]<?= $this->yield('title') ?>
