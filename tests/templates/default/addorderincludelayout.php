<?php $this->include('addorderlayoutpartial') ?>
<?php $this->prepend('s') ?>[page-prepend]<?php $this->end() ?>
<?php $this->append('s') ?>[page-append]<?php $this->end() ?>
<?= $this->yield('s', '[main]') ?>
