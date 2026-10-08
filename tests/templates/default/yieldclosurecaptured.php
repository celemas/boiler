<?php $this->section('s') ?>[main]<?php $this->end() ?>
<?= $this->yield('s', fn() => throw new \RuntimeException('Default evaluated')) ?>
