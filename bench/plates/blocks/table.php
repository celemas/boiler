<table class="block-table">
    <thead>
        <tr>
            <?php foreach ($block['head'] as $cell): ?>
                <th scope="col"><?= $this->e($cell) ?></th>
            <?php endforeach ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($block['rows'] as $row): ?>
            <tr>
                <?php foreach ($row as $cell): ?>
                    <td><?= $this->e($cell) ?></td>
                <?php endforeach ?>
            </tr>
        <?php endforeach ?>
    </tbody>
</table>
