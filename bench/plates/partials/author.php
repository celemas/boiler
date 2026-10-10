<?php $this->layout('components/card', ['title' => 'About the author', 'tone' => 'author']) ?>

<p><strong><?= $this->e($author->name) ?></strong> – <?= $this->e($author->role) ?></p>
<p><?= $this->e($author->bio) ?></p>
