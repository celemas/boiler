<?php $loop = $this->each('slotrows', ['rows' => $rows]); ?>
<?php $this->keep($loop); ?>
<?php if ($iterate) {
	foreach ($loop as $row) {
		break;
	}
} ?>
