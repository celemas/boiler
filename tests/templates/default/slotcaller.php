<?php $this->insert('slotrows', ['rows' => $rows], slot: function (array $row): void { ?>
<input name="<?= $this->escape($row['name']) ?>" value="<?= $this->escape($row['value']) ?>">
<?php }); ?>
