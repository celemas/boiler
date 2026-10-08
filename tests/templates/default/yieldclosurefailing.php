<?php try {
	echo $this->yield('s', function () {
		echo '[leaked]';

		throw new \RuntimeException('Default failed');
	});
} catch (\RuntimeException) {
	echo '[caught]';
} ?>
