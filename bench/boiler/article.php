<?php $this->layout('layouts/base', ['bodyClass' => 'page-article']) ?>

<?php $this->section('title') ?><?= $article['title'] ?><?php $this->end() ?>

<?php $this->append('head') ?>
<meta name="description" content="<?= $article['lead'] ?>">
<meta name="author" content="<?= $article['author']->name ?>">
<link rel="stylesheet" href="/css/article.css">
<?php $this->end() ?>

<?php $this->section('hero') ?>
<header class="article-hero">
    <h1><?= $article['title'] ?></h1>
    <p class="lead"><?= $article['lead'] ?></p>
    <p class="meta">
        By <a href="<?= $article['author']->url ?>"><?= $article['author']->name ?></a>, <?= $article['author']->role ?> ·
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
                <?php if ($block['type']->is('heading')): ?>
                    <li class="toc-level-<?= $block['level'] ?>"><a href="#<?= $block['id'] ?>"><?= $block['text'] ?></a></li>
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
                <li><a href="/magazine/tags/<?= $tag->lower() ?>">#<?= $tag ?></a></li>
            <?php endforeach ?>
        </ul>

        <?php $this->component('components/card', ['title' => 'About the author', 'tone' => 'author']) ?>
            <p><strong><?= $article['author']->name ?></strong> – <?= $article['author']->role ?></p>
            <p><?= $article['author']->bio ?></p>
        <?php $this->end() ?>
    </footer>
</div>
