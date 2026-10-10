<nav class="site-nav" aria-label="Main">
    <ul>
        <?php foreach ($nav as $item): ?>
            <li class="<?= $item->active() ? 'is-active' : 'is-idle' ?>">
                <a href="<?= $this->escape($item->url()) ?>"><?= $this->escape($item->title()) ?></a>
                <?php if ($item->hasChildren()): ?>
                    <ul>
                        <?php foreach ($item as $child): ?>
                            <li><a href="<?= $this->escape($child->url()) ?>"><?= $this->escape($child->title()) ?></a></li>
                        <?php endforeach ?>
                    </ul>
                <?php endif ?>
            </li>
        <?php endforeach ?>
    </ul>
</nav>
