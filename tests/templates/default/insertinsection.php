<?php $this->layout('readsection'); ?>
<p><?= $text; ?></p>
<?php $this->section('list'); ?>
<ul>
    <?php $this->insert('sectionitem', ['text' => $text]); ?>
</ul>
<?php $this->end(); ?>
