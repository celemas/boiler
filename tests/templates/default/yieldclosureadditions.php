<?php $this->prepend('s') ?>[prepend]<?php $this->end() ?>
<?php $this->append('s') ?>[append]<?php $this->end() ?>
<?= $this->yield('s', function () { ?>[default]<?php $this->append('s') ?>[default-append]<?php $this->end() ?><?php }) ?>
