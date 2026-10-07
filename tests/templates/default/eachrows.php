<h1>before</h1>
<?php foreach ($this->each('slotrows', ['rows' => $rows]) as ['name' => $name, 'value' => $value]): ?>
<input name="<?= $name ?>" value="<?= $value ?>">
<?php endforeach; ?>
<p>after</p>
