<form class="facets" action="<?= $category['url'] ?>">
    <?php foreach ($facets as $facet): ?>
        <fieldset class="facet facet-<?= $facet['key'] ?>">
            <legend><?= $facet['title'] ?></legend>
            <?php if ($facet['expanded']): ?>
                <ul>
                    <?php foreach ($facet['options'] as $option): ?>
                        <li>
                            <label>
                                <input type="checkbox" name="<?= $facet['key'] ?>[]" value="<?= $option['value'] ?>"<?= $option['selected'] ? ' checked' : '' ?>>
                                <?= $option['label'] ?>
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
