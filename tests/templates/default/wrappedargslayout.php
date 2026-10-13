<?php declare(strict_types=1) ?>
<?php $this->rewrite($section) ?>[<?= $this->yield($section) ?>]<?php $this->end() ?>
<?= $site ?>:<?= $this->yield('side & bar') ?>:<?= $this->slot() ?>
