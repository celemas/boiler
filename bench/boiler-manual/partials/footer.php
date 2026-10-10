<footer class="site-footer">
    <div class="footer-columns">
        <?php foreach ($footer as $column): ?>
            <section>
                <h2><?= $this->escape($column['title']) ?></h2>
                <ul>
                    <?php foreach ($column['links'] as $link): ?>
                        <li><a href="<?= $this->escape($link['url']) ?>"><?= $this->escape($link['label']) ?></a></li>
                    <?php endforeach ?>
                </ul>
            </section>
        <?php endforeach ?>
    </div>

    <p class="support">
        Questions? <a href="mailto:<?= $this->escape($site->support->email) ?>"><?= $this->escape($site->support->email) ?></a>
        · <?= $this->escape($site->support->phone) ?> · <?= $this->escape($site->support->hours) ?>
    </p>
    <p class="legal">© <?= $site->year ?> <?= $this->escape($site->name) ?>. <?= $this->escape($site->tagline) ?>.</p>
</footer>
