<?php $this->section('note') ?><?php $this->rewrite('title') ?>[layout-title]<?php $this->end() ?>[layout-note]<?php $this->end() ?>
<title><?= $this->yield('title', 'Site') ?></title><note><?= $this->yield('note') ?></note>
