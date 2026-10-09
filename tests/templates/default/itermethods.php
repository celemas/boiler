<?php foreach ($menu as $item) : ?>[<?= $item->title() ?>:<?php foreach ($item as $child) : ?><?= $child->title() ?>,<?php endforeach ?>]<?php endforeach ?>
