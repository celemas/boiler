# Layouts

Use layouts when multiple templates share a common outer structure. A template can assign one layout, and layouts can themselves use another layout.

Assume the following directory structure:

```text
path
`-- to
    `-- templates
        |-- page.php
        |-- outer.php
        `-- inner.php
```

## Assign a layout

In `page.php`, call `$this->layout()` to assign `inner.php` as the layout before outputting the page content:

```php
<?php $this->layout('inner') ?>

<p><?= $text ?></p>
```

Create `inner.php`:

```php
<body>
    <?= $this->slot() ?>
    <footer><?= $text ?></footer>
</body>
```

Render the page:

```php
$engine->render('page', ['text' => 'Boiler']);
```

This produces:

```html
<body>
	<p>Boiler</p>
	<footer>Boiler</footer>
</body>
```

The layout prints the rendered page content with `$this->slot()`, as it would print any other [slot](slots.md). It also receives all values from the page template context by default.

## Override layout context

Pass a second argument when the layout should receive extra values or override existing ones:

```php
<?php $this->layout('inner', ['text' => 'Changed']) ?>
```

The layout now sees `Changed` for `$text`, while the page template still sees its original value.

## Stack layouts

Layouts can assign another layout:

```php
<?php $this->layout('outer') ?>

<div class="inner">
    <?= $this->slot() ?>
</div>
```

Create `outer.php`:

```php
<body>
    <main>
        <?= $this->slot() ?>
    </main>
</body>
```

Boiler renders layouts from the innermost template outward. Each layout receives the context of the template it wraps, including the values passed to that template's `$this->layout()` call. A value the page passes to `inner` therefore also reaches `outer`, unless `inner` overrides it in its own `$this->layout('outer', [...])` call.

## Error handling

- A template can set only one layout. Calling `$this->layout()` twice raises a runtime error.
- If the referenced layout cannot be found, Boiler raises `LookupException`.
- Layout lookup follows the same rules as normal template rendering, including namespaces and directory overrides.
- Standalone `Template` instances resolve layouts relative to the directory of the template file.
