<section><?php foreach ($this->each('slotrows', ['rows' => $rows]) as $row) {
	echo $this->slot($row);
} ?></section>
