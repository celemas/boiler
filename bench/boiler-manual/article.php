<?php $this->layout('layouts/base', ['bodyClass' => 'page-article']) ?>

<?php $this->section('title') ?><?= $this->escape($article['title']) ?><?php $this->end() ?>

<?php $this->append('head') ?>
<meta name="description" content="<?= $this->escape($article['lead']) ?>">
<meta name="author" content="<?= $this->escape($article['author']->name) ?>">
<link rel="stylesheet" href="/css/article.css">
<?php $this->end() ?>

<?php $this->section('hero') ?>
<header class="article-hero">
    <h1><?= $this->escape($article['title']) ?></h1>
    <p class="lead"><?= $this->escape($article['lead']) ?></p>
    <p class="meta">
        By <a href="<?= $this->escape($article['author']->url) ?>"><?= $this->escape($article['author']->name) ?></a>, <?= $this->escape($article['author']->role) ?> ·
        <time datetime="<?= $article['published']->format('Y-m-d') ?>"><?= $article['published']->format('F j, Y') ?></time> ·
        <?= $article['readingMinutes'] ?> min read
    </p>
</header>
<?php $this->end() ?>

<div class="article">
    <nav class="toc" aria-label="Contents">
        <h2>Contents</h2>
        <ol>
            <?php foreach ($blocks as $block): ?>
                <?php if ($block['type'] === 'heading'): ?>
                    <li class="toc-level-<?= $block['level'] ?>"><a href="#<?= $this->escape($block['id']) ?>"><?= $this->escape($block['text']) ?></a></li>
                <?php endif ?>
            <?php endforeach ?>
        </ol>
    </nav>

    <div class="article-body">
        <?php foreach ($blocks as $block): ?>
            <?php $this->include('blocks/' . $block['type'], ['block' => $block]) ?>
        <?php endforeach ?>
    </div>

    <footer class="article-footer">
        <ul class="tags">
            <?php foreach ($article['tags'] as $tag): ?>
                <li><a href="/magazine/tags/<?= $this->escape(strtolower($tag)) ?>">#<?= $this->escape($tag) ?></a></li>
            <?php endforeach ?>
        </ul>

        <?php $this->component('components/card', ['title' => 'About the author', 'tone' => 'author']) ?>
            <p><strong><?= $this->escape($article['author']->name) ?></strong> – <?= $this->escape($article['author']->role) ?></p>
            <p><?= $this->escape($article['author']->bio) ?></p>
        <?php $this->end() ?>
    </footer>
</div>
