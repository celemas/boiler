<?php $this->layout('rewritebase') ?>
<?php $this->rewrite('title') ?><?php if ($title = $this->yield('title', '')) : ?><?= $title ?> – <?php endif ?>Blog<?php $this->end() ?>
<?php $this->rewrite('sidebar') ?>
	<?php if ($sidebar = $this->yield('sidebar', '')) : ?><div class="blog"><?= $sidebar ?></div><?php endif ?>
<?php $this->end() ?>
<?= $this->slot() ?>
