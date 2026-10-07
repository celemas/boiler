<section><?php foreach ($this->each('slotrows', ['rows' => $rows]) as $row) {
	$this->slot($row);
} ?></section>
