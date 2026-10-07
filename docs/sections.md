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

<?php $this->section('scripts') ?>
<script src="/page.js"></script>
<?php $this->end() ?>

<p><?= $text ?></p>
```

Create `layout.php`:

```php
<body>
    <?= $this->body() ?>
    <?= $this->yield('scripts') ?>
</body>
```

Rendering `page` inserts the captured section content into the layout. Section capture runs while the page template executes, so the captured block can access the same variables as that template. When a layout later calls `$this->yield()`, Boiler outputs the captured string rather than rendering a separate template with its own context.

## Default content

Use a default when the section may be missing:

```php
<?= $this->yield('scripts', '<script src="/default.js"></script>') ?>
```

Check for a section first when you need conditional markup:

```php
<?php if ($this->hasSection('scripts')) : ?>
    <aside><?= $this->yield('scripts') ?></aside>
<?php endif; ?>
```

## Append and prepend

Use `append()` or `prepend()` instead of `section()` when you want to add content relative to existing section content:

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

Regular `section()` content becomes the main assigned section content.

## Nested sections

Capture blocks can nest. A section can contain another section, or an insert whose template captures sections of its own, such as a widget that appends its script:

```php
<?php $this->section('sidebar') ?>
<?php $this->insert('widget') ?>
<?php $this->end() ?>
```

`$this->end()` closes the innermost open section. Pass a name to check which one it closes: `$this->end('sidebar')` fails the render at that line when another section is open.

## Error handling

- Section names are strings such as `scripts` or `sidebar`.
- Section capture blocks must be closed with `$this->end()` in the template that opened them.
- Calling `$this->end()` without an open section raises a render error, and so does `$this->end('name')` when the innermost open section has another name.
- Calling `$this->yield()` without a default for a section that was never captured raises a render error. Pass a default, even `''`, or check with `$this->hasSection()` when the section is optional.
