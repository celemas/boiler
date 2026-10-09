# Sections

Sections are named content that any template can write and a layout prints, such as the page title, scripts, styles, or a sidebar. The page, its includes, and its layouts all write to the same sections.

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

For a default made of markup, such as an include, pass a closure that prints it. The closure runs only when the default applies:

```php
<aside><?= $this->yield('sidebar', fn() => $this->include('sidebar-default')) ?></aside>
```

The closure prints its content, like a section block; returning a string instead fails the render. Content appended or prepended to the section prints around the default, as for a string default.

### Nested defaults

A closure default can print other sections, so a page can fill a section inside the default without replacing it, like a block nested in another in Twig. To write the markup inline, use a `function` closure; an arrow function holds only an expression.

In `layout.php`:

```php
<head>
<?= $this->yield('head', function () { ?>
    <link rel="stylesheet" href="/style.css">
    <title><?= $this->yield('title', '') ?> – My site</title>
<?php }) ?>
</head>
```

A page that captures the title and appends to `head` keeps the stylesheet:

```php
<?php $this->layout('layout') ?>

<?php $this->section('title') ?>About<?php $this->end() ?>

<?php $this->append('head') ?>
<style>.intro { color: #369; }</style>
<?php $this->end() ?>
```

The head then holds the stylesheet, `<title>About – My site</title>`, and the page's style. `append()` takes the place of `{{ parent() }}` followed by new content in Twig, and `prepend()` of new content followed by it. A page that captures `head` with `section()` replaces the whole default, so the title it holds does not print either. A longer default fits in a partial: `fn() => $this->include('head')`.

### Defaults in a layout

A layout between the page and the layout that prints a section provides a default with `section()`. A layout's capture is a default for the templates it wraps: the page, its inner layouts, and everything they include. Those render first, so when one of them captured the section, the layout's capture is discarded, and so is everything it would add to sections, such as the scripts of a partial it includes. Otherwise its capture becomes the main content, which the next layout out treats the same way, so the innermost capture wins.

Create `mid.php`, a layout between the page and `layout.php`:

```php
<?php $this->layout('layout') ?>

<?php $this->section('sidebar') ?>
<?php $this->include('widget') ?>
<?php $this->end() ?>

<?= $this->slot() ?>
```

A page that uses `mid` as its layout shows the widget in the sidebar. A page that captures `sidebar` itself replaces it, and the widget's script, which it [appends](#append-and-prepend) to `scripts`, is not added either. A page that only appends to `sidebar` keeps the widget and adds to it, and a page that captures `sidebar` empty switches it off. This is how a layout's `@section` works in Blade and a parent's `{% block %}` in Twig.

The code of a discarded default still runs; only its output and its additions to sections are dropped. In the layout that prints a section, pass the default to `yield()` instead: a `section()` call after the `yield()` of the same section comes too late to print.

Any other second capture of a section fails the render at its `section()` call, and the error names where the first capture happened. That covers a template that captures a section twice, a page and one of its partials, and a page and the layout of a partial it includes, which wraps only that partial.

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

Additions keep the order of their calls, also across includes, so two partials that append their scripts print them in the order they were included. A layout's additions stay closer to the main content than those of the page it wraps: page prepends, layout prepends, main content, layout appends, page appends. A script that the layout appends therefore comes before the scripts of the page. An included template with a layout of its own follows the same rule, and its additions and those of its layout stay together at the place of the `include()` call.

`section()` sets only the main content and keeps what was appended or prepended before.

## Rewrite a section

A layout can build on what the templates it wraps wrote to a section, for example to wrap the page's sidebar in its own markup or to add the blog name to the page's title. Write the new content between `rewrite()` and `end()`. Inside, `yield()` returns the section's content so far.

Create `blog.php`, a layout between the page and `layout.php`:

```php
<?php $this->layout('layout') ?>

<?php $this->rewrite('title') ?>
<?= $this->yield('title', 'Untitled') ?> – Blog
<?php $this->end() ?>

<?php $this->rewrite('sidebar') ?>
<?php if ($sidebar = $this->yield('sidebar', '')) : ?>
    <div class="blog"><?= $sidebar ?></div>
<?php endif ?>
<?php $this->end() ?>

<?= $this->slot() ?>
```

The output of the block replaces everything the section held so far, including what the page and its partials appended or prepended, so nothing prints twice. Layouts further out add to the result or rewrite it again. A rewrite counts as a capture, so a layout further out that provides a default with `section()` gives way to it. When both the content so far and the output of the block are blank, the section stays as it was, and a default passed to `yield()` still applies. In Blade, the same works with `@yield` inside `@section` … `@overwrite`.

`yield()` of a section inside a `section()`, `append()`, or `prepend()` block of the same section fails the render, as the section is not complete yet; build on it with `rewrite()` instead. Inside `rewrite()`, writing to the same section fails, as the rewrite replaces what it read.

## Print markup only when there is content

`yield()` returns `''` for a section that holds nothing but whitespace, like `slot()` does for a slot. With `''` as the default, the result tells whether there is anything to print, so markup around a section can depend on it:

```php
<?php $sidebar = $this->yield('sidebar', '') ?>
<?php if ($sidebar) : ?>
    <aside><?= $sidebar ?></aside>
<?php endif ?>
```

This counts everything the section prints: the main content or a default, and what templates appended or prepended. Other content comes back unchanged, including the whitespace around it. To fill in missing main content, pass a default to `yield()` or capture one in a layout instead.

## Nested blocks

Sections and [components](slots.md#pass-a-block-to-a-component) share one stack of open blocks, and `end()` closes the innermost one. Blocks can nest: a section can contain a component, another section, or an include whose template captures sections of its own, such as the widget above.

```php
<?php $this->section('sidebar') ?>
<?php $this->include('widget') ?>
<?php $this->end('sidebar') ?>
```

Pass a name to check which block `end()` closes, like Twig's `{% endblock sidebar %}`: `$this->end('sidebar')` fails the render at that line when another section or component is open.

## Error handling

- Section names are strings such as `scripts` or `sidebar`.
- A section must be closed with `$this->end()` in the template that opened it. An unclosed section raises a render error that points to the line that opened it.
- Calling `$this->end()` without an open section or component raises a render error, and so does `$this->end('name')` when the innermost open block has another name.
- Calling `$this->yield()` without a default for a section that was never captured raises a render error. Pass a default, even `''`, when the section is optional.
- Calling `$this->yield()` inside a `section()`, `append()`, or `prepend()` block of the same section raises a render error, and so does writing to a section inside its own `rewrite()` block.
