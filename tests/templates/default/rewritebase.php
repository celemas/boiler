<?php $this->rewrite('title') ?><?= $this->yield('title') ?> | Site<?php $this->end() ?>
<?php $this->section('sidebar') ?>[base-sidebar]<?php $this->end() ?>
<?php $this->append('sidebar') ?>[base-ad]<?php $this->end() ?>
<title><?= $this->yield('title') ?></title>
<aside><?= $this->yield('sidebar') ?></aside>
<main><?= $this->slot() ?></main>
