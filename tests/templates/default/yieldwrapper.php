<?php $sidebar = $this->yield('sidebar', '') ?>
<?php if ($sidebar) : ?><aside><?= $sidebar ?></aside><?php endif ?>
<main><?= $this->slot() ?></main>
