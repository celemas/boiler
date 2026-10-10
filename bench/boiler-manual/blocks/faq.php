<section class="block-faq">
    <h3><?= $this->escape($block['title']) ?></h3>
    <?php foreach ($block['items'] as $item): ?>
        <?php $this->component('components/card', ['title' => $item['question'], 'tone' => 'faq']) ?>
            <p><?= $this->escape($item['answer']) ?></p>
        <?php $this->end() ?>
    <?php endforeach ?>
</section>
