<section class="block-faq">
    <h3><?= $block['title'] ?></h3>
    <?php foreach ($block['items'] as $item): ?>
        <?php $this->component('components/card', ['title' => $item['question'], 'tone' => 'faq']) ?>
            <p><?= $item['answer'] ?></p>
        <?php $this->end() ?>
    <?php endforeach ?>
</section>
