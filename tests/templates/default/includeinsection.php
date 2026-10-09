<?php $this->layout('readsection'); ?>
<p><?= $text; ?></p>
<?php $this->section('list'); ?>
<ul>
    <?php $this->include('sectionitem', ['text' => $text]); ?>
</ul>
<?php $this->end(); ?>
