<?php $this->layout('components/card', ['title' => $item['question'], 'tone' => 'faq']) ?>

<p><?= $this->e($item['answer']) ?></p>
