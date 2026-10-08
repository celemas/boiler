# Sections

Sections are named content that any template can write and a layout prints, such as the page title, scripts, styles, or a sidebar. The page, its inserts, and its layouts all write to the same sections.

Assume the following directory structure:

```text
path
`-- to
    `-- templates
        |-- page.php
        |-- layout.php
        `-- widget.php
```

## Write and print a section

Capture a section with `section()` … `end()`, and print it with `yield()`.

Create `page.php`:

```php
<?php $this->layout('layout') ?>

<?php $this->section('title') ?>About<?php $this->end() ?>

<p><?= $text ?></p>
```

Create `layout.php`:

```php
<title><?= $this->yield('title') ?></title>
<main><?= $this->slot() ?></main>
```

Everything outside a section is the page content, which the layout prints as its [slot](slots.md). The section block runs while the page renders, so it uses the same variables as the page. When the layout calls `$this->yield()`, Boiler prints the captured string.

A plain value such as the title can also reach the layout as data: `$this->layout('layout', ['title' => 'About'])`. Sections are for markup, and for content that several templates add to.

## Default content

Pass a default when the section is optional:

```php
<title><?= $this->yield('title', 'My site') ?></title>
```

The default applies only while no template captured the section with `section()`. A section captured empty prints nothing, as an empty `@section` does in Blade, so skip the `section()` call when the default should show.

A render captures each section once. A second `section()` capture of the same section fails the render at its `section()` call, and the error names where the first capture happened. A layout renders after the page it wraps, so a layout that captured a section itself would replace the page's content instead of providing a fallback. Pass the fallback to `yield()`, and add content with `append()` or `prepend()`.

Check for a section when you need conditional markup:

```php
<?php if ($this->hasSection('sidebar')) : ?>
    <aside><?= $this->yield('sidebar') ?></aside>
<?php endif; ?>
```

## Append and prepend

Use `append()` or `prepend()` instead of `section()` to add content after or before the main content of a section. A partial can add its own script to the layout this way.

Create `widget.php`:

```php
<div class="widget"></div>

<?php $this->append('scripts') ?>
<script src="/widget.js"></script>
<?php $this->end() ?>
```

Print the scripts in `layout.php`:

```php
<?= $this->yield('scripts', '') ?>
```

Boiler combines the parts in this order:

1. prepended content
2. the main content: what `section()` captured, or else the default passed to `yield()`
3. appended content

Additions keep the order of their calls, also across inserts, so two partials that append their scripts print them in the order they were inserted. A layout's additions stay closer to the main content than those of the page it wraps: page prepends, layout prepends, main content, layout appends, page appends. A script that the layout appends therefore comes before the scripts of the page. An inserted template with a layout of its own follows the same rule, and its additions and those of its layout stay together at the place of the `insert()` call.

`section()` sets only the main content and keeps what was appended or prepended before.

## Nested blocks

Sections and [components](slots.md#pass-a-block-to-a-component) share one stack of open blocks, and `end()` closes the innermost one. Blocks can nest: a section can contain a component, another section, or an insert whose template captures sections of its own, such as the widget above.

```php
<?php $this->section('sidebar') ?>
<?php $this->insert('widget') ?>
<?php $this->end('sidebar') ?>
```

Pass a name to check which block `end()` closes, like Twig's `{% endblock sidebar %}`: `$this->end('sidebar')` fails the render at that line when another section or component is open.

## Error handling

- Section names are strings such as `scripts` or `sidebar`.
- A section must be closed with `$this->end()` in the template that opened it. An unclosed section raises a render error that points to the line that opened it.
- Calling `$this->end()` without an open section or component raises a render error, and so does `$this->end('name')` when the innermost open block has another name.
- Calling `$this->yield()` without a default for a section that was never captured raises a render error. Pass a default, even `''`, or check with `$this->hasSection()` when the section is optional.
