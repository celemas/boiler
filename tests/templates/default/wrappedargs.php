<?php declare(strict_types=1) ?>
<?php $this->layout($layout, $layoutValues) ?>
<?php $this->section($section) ?>section<?php $this->end($section) ?>
<?php $this->append($section) ?>+appended<?php $this->end() ?>
<?php $this->prepend($section) ?>prepended+<?php $this->end() ?>
<?php $this->include($partial, $row) ?>
<?php $this->component($partial, $row) ?>slot<?php $this->end($partial) ?>
<?= $this->yield('missing', $fallback) ?>|<?= $this->escape($title, $escaper) ?>|<?= $title->escape($escaper) ?>|<?= $this->add($key, 'added') ?>|<?= $numbers->sorted($mode)[0] ?>
