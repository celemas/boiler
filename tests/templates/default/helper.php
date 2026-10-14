<?= $this->escape('<script>') ?>
<?= w('<script>console.log("evil");</script><b>clean</b>')->sanitize(); ?>
