<form class="facets" action="<?= $this->escape($category['url']) ?>">
    <?php foreach ($facets as $facet): ?>
        <fieldset class="facet facet-<?= $this->escape($facet['key']) ?>">
            <legend><?= $this->escape($facet['title']) ?></legend>
            <?php if ($facet['expanded']): ?>
                <ul>
                    <?php foreach ($facet['options'] as $option): ?>
                        <li>
                            <label>
                                <input type="checkbox" name="<?= $this->escape($facet['key']) ?>[]" value="<?= $this->escape($option['value']) ?>"<?= $option['selected'] ? ' checked' : '' ?>>
                                <?= $this->escape($option['label']) ?>
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
