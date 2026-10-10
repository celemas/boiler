<footer class="site-footer">
    <div class="footer-columns">
        <?php foreach ($footer as $column): ?>
            <section>
                <h2><?= $this->e($column['title']) ?></h2>
                <ul>
                    <?php foreach ($column['links'] as $link): ?>
                        <li><a href="<?= $this->e($link['url']) ?>"><?= $this->e($link['label']) ?></a></li>
                    <?php endforeach ?>
                </ul>
            </section>
        <?php endforeach ?>
    </div>

    <p class="support">
        Questions? <a href="mailto:<?= $this->e($site->support->email) ?>"><?= $this->e($site->support->email) ?></a>
        · <?= $this->e($site->support->phone) ?> · <?= $this->e($site->support->hours) ?>
    </p>
    <p class="legal">© <?= $site->year ?> <?= $this->e($site->name) ?>. <?= $this->e($site->tagline) ?>.</p>
</footer>
