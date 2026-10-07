<?php foreach ($this->unwrap($rows) as $row): ?>
<i><?= trim($this->slot($row)) ?></i><u><?= $this->wrap($this->slot($row))->upper() ?></u><s><?= $this->escape($this->slot($row)) ?></s><q><?= rawurlencode($this->slot($row)) ?></q><p><?= $this->slot($row) ?></p>
<?php endforeach ?>
