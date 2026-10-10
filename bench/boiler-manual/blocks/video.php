<div class="block-video" id="video-<?= $block['id'] ?>" data-provider="<?= $this->escape($block['provider']) ?>">
    <a href="<?= $this->escape($block['url']) ?>"><?= $this->escape($block['title']) ?></a>
</div>

<?php $this->append('scripts') ?>
<script src="/js/video.js" data-target="video-<?= $block['id'] ?>" data-provider="<?= $this->escape($block['provider']) ?>"></script>
<?php $this->end() ?>
