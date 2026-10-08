<?php $this->section('sidebar') ?>[base-sidebar]<?php $this->end() ?>
<?php $this->append('js') ?>[base-js]<?php $this->end() ?>
<title><?= $this->yield('title', '') ?></title>
<main><?= $this->slot() ?></main>
<aside><?= $this->yield('sidebar') ?></aside>
<js><?= $this->yield('js', '') ?></js>
<modal><?= $this->yield('modal', '') ?></modal>
