<?php $this->layout('components/card', ['title' => 'Need help?', 'tone' => 'support']) ?>

<p><a href="mailto:<?= $this->e($site->support->email) ?>"><?= $this->e($site->support->email) ?></a></p>
<p><?= $this->e($site->support->phone) ?> · <?= $this->e($site->support->hours) ?></p>
