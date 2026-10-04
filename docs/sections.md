# Sections

Sections let child templates push named content into layouts. They are useful for repeated slots such as scripts, styles, sidebars, or page headers.

Assume the following directory structure:

```text
path
`-- to
    `-- templates
        |-- page.php
        `-- layout.php
```

## Define section content

Create `page.php`:

```php
<?php $this->layout('layout') ?>

<?php $this->begin('scripts') ?>
<script src="/page.js"></script>
<?php $this->end() ?>

<p><?= $text ?></p>
```

Create `layout.php`:

```php
<body>
    <?= $this->body() ?>
    <?= $this->section('scripts') ?>
</body>
```

Rendering `page` inserts the captured section content into the layout. Section capture runs while the page template executes, so the captured block can access the same variables as that template. When a layout later calls `$this->section()`, Boiler outputs the captured string rather than rendering a separate template with its own context.

## Default content

Use a default when the section may be missing:

```php
<?= $this->section('scripts', '<script src="/default.js"></script>') ?>
```

Check for a section first when you need conditional markup:

```php
<?php if ($this->has('scripts')) : ?>
    <aside><?= $this->section('scripts') ?></aside>
<?php endif; ?>
```

## Append and prepend

Use `append()` or `prepend()` instead of `begin()` when you want to add content relative to existing section content:

```php
<?php $this->prepend('scripts') ?>
<script src="/first.js"></script>
<?php $this->end() ?>

<?php $this->append('scripts') ?>
<script src="/last.js"></script>
<?php $this->end() ?>
```

When a layout renders a section with a default value, Boiler combines the parts in this order:

1. prepended content
2. default value
3. appended content

Regular `begin()` content becomes the main assigned section content.

## Error handling

- Section names are strings such as `scripts` or `sidebar`.
- Section capture blocks must be closed with `$this->end()`.
- Nested capture blocks are not allowed and raise a render error.
- Calling `$this->end()` without an open section raises a render error.
- Calling `$this->section()` without a default for a section that was never captured raises a render error. Pass a default, even `''`, or check with `$this->has()` when the section is optional.
