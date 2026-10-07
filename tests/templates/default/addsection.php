<?php $this->layout('readsection'); ?>
<p><?= $text; ?></p>
<?php $this->section('list'); ?>
<ul>
    <li><?= $text; ?></li>
</ul>
<?php $this->end(); ?>
