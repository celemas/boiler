<?php try {
	foreach ($this->each('slotrows', ['rows' => $rows]) as $row) {
		if ($row['name']->is('b')) {
			throw new \RuntimeException('second row failed');
		}

		echo "<b>{$row['name']}</b>";
	}
} catch (\RuntimeException) {
	echo '<p>fallback</p>';
} ?>
