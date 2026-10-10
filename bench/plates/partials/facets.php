<form class="facets" action="<?= $this->e($category['url']) ?>">
    <?php foreach ($facets as $facet): ?>
        <fieldset class="facet facet-<?= $this->e($facet['key']) ?>">
            <legend><?= $this->e($facet['title']) ?></legend>
            <?php if ($facet['expanded']): ?>
                <ul>
                    <?php foreach ($facet['options'] as $option): ?>
                        <li>
                            <label>
                                <input type="checkbox" name="<?= $this->e($facet['key']) ?>[]" value="<?= $this->e($option['value']) ?>"<?= $option['selected'] ? ' checked' : '' ?>>
                                <?= $this->e($option['label']) ?>
                                <small>(<?= $option['count'] ?>)</small>
                            </label>
                        </li>
                    <?php endforeach ?>
                </ul>
            <?php else: ?>
                <p class="facet-summary"><?= count($facet['options']) ?> options</p>
            <?php endif ?>
        </fieldset>
    <?php endforeach ?>
</form>
