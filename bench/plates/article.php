<?php $this->layout('layouts/base', ['title' => $article['title'], 'bodyClass' => 'page-article'] + $this->data()) ?>

<?php $this->push('head') ?>
<meta name="description" content="<?= $this->e($article['lead']) ?>">
<meta name="author" content="<?= $this->e($article['author']->name) ?>">
<link rel="stylesheet" href="/css/article.css">
<?php $this->stop() ?>

<?php $this->start('hero') ?>
<header class="article-hero">
    <h1><?= $this->e($article['title']) ?></h1>
    <p class="lead"><?= $this->e($article['lead']) ?></p>
    <p class="meta">
        By <a href="<?= $this->e($article['author']->url) ?>"><?= $this->e($article['author']->name) ?></a>, <?= $this->e($article['author']->role) ?> ·
        <time datetime="<?= $article['published']->format('Y-m-d') ?>"><?= $article['published']->format('F j, Y') ?></time> ·
        <?= $article['readingMinutes'] ?> min read
    </p>
</header>
<?php $this->stop() ?>

<div class="article">
    <nav class="toc" aria-label="Contents">
        <h2>Contents</h2>
        <ol>
            <?php foreach ($blocks as $block): ?>
                <?php if ($block['type'] === 'heading'): ?>
                    <li class="toc-level-<?= $block['level'] ?>"><a href="#<?= $this->e($block['id']) ?>"><?= $this->e($block['text']) ?></a></li>
                <?php endif ?>
            <?php endforeach ?>
        </ol>
    </nav>

    <div class="article-body">
        <?php foreach ($blocks as $block): ?>
            <?php $this->insert('blocks/' . $block['type'], ['block' => $block]) ?>
        <?php endforeach ?>
    </div>

    <footer class="article-footer">
        <ul class="tags">
            <?php foreach ($article['tags'] as $tag): ?>
                <li><a href="/magazine/tags/<?= $this->e($tag, 'strtolower') ?>">#<?= $this->e($tag) ?></a></li>
            <?php endforeach ?>
        </ul>

        <?php $this->insert('partials/author', ['author' => $article['author']]) ?>
    </footer>
</div>

<?php // An inserted template has its own sections, so the page lists the scripts of its video blocks. ?>
<?php $this->push('scripts') ?>
<?php foreach ($blocks as $block): ?>
    <?php if ($block['type'] === 'video'): ?>
        <script src="/js/video.js" data-target="video-<?= $block['id'] ?>" data-provider="<?= $this->e($block['provider']) ?>"></script>
    <?php endif ?>
<?php endforeach ?>
<?php $this->stop() ?>
