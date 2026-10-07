<div><?= $this->body(); ?><?= $text; ?></div>
<?php if ($this->hasSection('list')) { ?>
    <?= $this->yield('list'); ?>
<?php } else { ?>
    <p>no list</p>
<?php } ?>
