# Slots

A slot is what a template wraps. The template prints it with `<?= $this->slot() ?>`, wherever and as often as it likes. Two helpers give a template a slot:

| Helper | The slot is |
| --- | --- |
| [`layout()`](layouts.md) in the page | the page the layout wraps |
| `component('card')` … `end()` at the call site | the block between the two calls |

Where [sections](sections.md) let any template push named content _up_ into a layout, a slot passes one block _down_ into the template that wraps it. Slots are the tool for a reusable wrapper around varying markup: a card body, a field row, a table row. Write the wrapper once and keep the varying markup at the call site.

Assume the following directory structure:

```text
path
`-- to
    `-- templates
        |-- page.php
        |-- card.php
        |-- row.php
        |-- rows.php
        `-- input.php
```

## Pass a block to a component

Use `component()` … `end()` to include a template with the block in between as its slot.

Create `page.php`:

```php
<?php $this->component('card', ['title' => 'News']) ?>
<p><?= $text ?></p>
<?php $this->end() ?>
```

Create `card.php`:

```php
<div class="card">
    <h2><?= $title ?></h2>
    <?= $this->slot() ?>
</div>
```

The block runs once, at the call site, before `card.php` renders, so it uses the page's variables. The card receives the finished markup: it can print it anywhere, check it with `hasSlot()`, or place it in a section. Any template can serve as a component; there are no component classes or prop declarations. Like `include()`, `component()` shares the calling template's context and merges the values you pass on top. Plain values such as the title go in as data.

Sections and components share one stack of open blocks, and `end()` closes the innermost one. Pass the template name to check which block it closes: `$this->end('card')` fails the render at that line when another block is open.

## Repeat a block

To wrap every item of a list, write the loop at the call site and pass the markup of each item to a component.

Create `page.php`:

```php
<ul>
<?php foreach ($items as $item): ?>
<?php $this->component('row') ?>
<input name="<?= $item['name'] ?>" value="<?= $item['value'] ?>">
<?php $this->end() ?>
<?php endforeach ?>
</ul>
```

Create `row.php`:

```php
<li class="row"><?= $this->slot() ?></li>
```

When the partial should own the loop, for example to sort or group the items, pass it the name of a template that renders one item. The partial includes that template once per item.

Change `page.php` to:

```php
<?php $this->include('rows', ['items' => $items, 'row' => 'input']) ?>
```

Create `rows.php`:

```php
<ul>
<?php foreach ($items as $item): ?>
    <li><?php $this->include($row, ['item' => $item]) ?></li>
<?php endforeach ?>
</ul>
```

Create `input.php`:

```php
<input name="<?= $item['name'] ?>" value="<?= $item['value'] ?>">
```

## Optional slots

Use `hasSlot()` when a template should work with or without a slot:

```php
<div>
<?php if ($this->hasSlot()): ?>
    <?= $this->slot() ?>
<?php else: ?>
    <em>No content</em>
<?php endif; ?>
</div>
```

`hasSlot()` is false when the slot holds nothing but whitespace, such as an empty component block or a page that only captures sections, and `slot()` then returns an empty string.

## Error handling

- Calling `$this->slot()` in a template that has no slot, such as one included with `include()`, raises a render error. Guard with `hasSlot()` whenever the slot is optional.
- A `component()` block must be closed with `$this->end()` in the template that opened it. An unclosed block raises a render error that points to the `component()` call.
- `component()` resolves its template right away, so a missing template fails at that call, before the block runs.
