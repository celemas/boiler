# Changelog

## [Unreleased](https://codefloe.com/celema/boiler/compare/0.8.0...HEAD)

### Migration

Section and slot helpers are renamed, `insert()` is now `include()`, closure slots are gone, and layouts print the page with `slot()`. Update templates as follows, renaming the reading `section(` to `yield(` before renaming `begin(` to `section(`:

| Before | After |
| --- | --- |
| `<?php $this->begin('title') ?>` … `<?php $this->end() ?>` | `<?php $this->section('title') ?>` … `<?php $this->end() ?>` |
| `<?= $this->section('title', 'default') ?>` | `<?= $this->yield('title', 'default') ?>` |
| `$this->has('title')` | a check of `$this->yield('title', '')`, which is `''` when there is nothing to print |
| `<?= $this->body() ?>` in a layout | `<?= $this->slot() ?>` |
| `<?php $this->slot() ?>` | `<?= $this->slot() ?>` |
| `$this->insert('card', [...])` | `$this->include('card', [...])` |
| `$this->insert('card', [...], slot: function () { ?>…<?php })` | `$this->component('card', [...])` … `$this->end()` |
| `$this->insert('rows', [...], slot: function (array $row) { ?>…<?php })` | a `foreach` loop at the call site with `$this->component('row')` … `$this->end()` per row |
| `slot: Slot::template('control', [...])` | `'control'` passed as data, which the partial includes per row with `$this->include($control, [...])` |
| `LayoutContext` or `TemplateContext` type hints | `Context` |

A leftover `insert()` call fails the render as an unknown template method, and a leftover `slot:` argument as an unknown named parameter. A leftover reading `section('title')` opens a capture that is never closed, which fails the render too. A leftover `<?php $this->slot() ?>` prints nothing. Without a rename, section output changes in three cases: repeated `append()` calls print in call order, `section()` after `append()` or `prepend()` keeps the additions, and a section captured empty prints nothing instead of the `yield()` default. A section captured by both the page and its layout keeps the page's capture instead of the layout's, as a layout's capture is now a default, and other second captures of a section fail the render instead of keeping the last capture. Custom template methods named `component`, `include`, `rewrite`, or `yield` are now rejected. Strings returned by methods of a wrapped traversable object, such as a menu item with children, are now escaped; they used to print raw.

### Added

- `$this->component('partial', [...])` … `$this->end()` includes a template with the block in between as its slot, which the template prints with `<?= $this->slot() ?>`. The block runs at the call site before the template renders, so it uses the caller's variables. Sections and components share one stack of open blocks closed by `end()`, and `end('partial')` checks the name. A block of only whitespace counts as no slot for `hasSlot()`. `component` is now a reserved template method name.
- Section capture blocks can nest, so an included template that appends its script works inside a section. `$this->end()` closes the innermost open section; `$this->end('name')` also checks that it closes that section and fails the render at that line otherwise. A template can only close the sections it opened itself.
- `$this->yield()` takes a closure as the default, for markup such as an include: `<?= $this->yield('sidebar', fn() => $this->include('sidebar-default')) ?>`. The closure runs only when no main content was captured, and what it prints stands in for that content between any additions. Returning a string from the closure instead of printing it fails the render.
- `$this->rewrite('name')` … `$this->end()` replaces a section's content so far, appended and prepended content included, with the output of the block, inside which `yield()` returns that content. A layout can wrap the page's sidebar in its own markup or add the site name to the page's title: `<?= $this->yield('title') ?> – Blog`. Layouts further out add to the result or rewrite it again; a rewrite counts as a capture, so their `section()` defaults give way to it. When both the content so far and the output are blank, the section stays as it was. Writing to a section inside its own `rewrite()` block fails the render. `rewrite` is now a reserved template method name.
- `Engine` exposes its `defaults` and `trusted` lists as readonly properties, like `autoescape`.

### Changed

- `$this->slot()` returns the slot content instead of printing it, like `yield()`: write `<?= $this->slot() ?>`. A leftover `<?php $this->slot() ?>` prints nothing.
- Layouts print the page they wrap with `$this->slot()` instead of `$this->body()`, so one call prints whatever a template wraps. `body()` and the `LayoutContext` class are gone; layouts run in a `Context` like other templates. In a layout, `hasSlot()` is false when the page printed nothing but whitespace, and `slot()` then returns `''`. `body` is no longer a reserved template method name.
- `$this->insert()` is renamed to `$this->include()`, the name Twig, Blade, and most other template engines use for rendering a partial in place. It works as before: the included template shares the caller's context, and the values you pass merge on top. A leftover `insert()` call fails the render as an unknown template method. `include` is now a reserved template method name; `insert` is free.
- The section helpers are renamed after the side they work on, as in Blade: `$this->section('name')` … `$this->end()` captures a section (formerly `begin()`), and `$this->yield('name', 'default')` prints it (formerly the reading `section()`). A leftover reading `section('name')` call opens a capture that is never closed and fails the render. `yield` is now a reserved template method name; `begin` is free.
- `$this->yield('name')` (formerly the reading `$this->section('name')`) without a default now raises a render error with a clear message and the calling location when the section was never captured. Previously it failed with an undefined array key warning followed by an `Error`. Pass a default, even `''`, for optional sections. The default parameter is now `?string $default = null`; passing `null` is the same as omitting it.
- A section captured empty with `section()` … `end()` overrides the default passed to `yield()`, as an empty `@section` does in Blade. It used to print the default. To fall back to the default, skip the `section()` call instead of capturing nothing.
- A layout's `section()` capture is a default for the templates it wraps: the page, its inner layouts, and what they include. When one of them captured the section, the layout's capture is discarded together with everything it adds to sections, such as the scripts of a partial it includes; otherwise the innermost capture wins. The last capture used to replace earlier ones, so a layout that captured a section as a fallback replaced the page's content, as the layout renders after the page. Any other second capture of a section in one render now fails the render at that capture, and the error names where the first one happened. A capture that fails before its `end()` does not count.
- `yield()` returns `''` for a section that holds nothing but whitespace, like `slot()` does for a slot, so with `''` as the default its result tells whether there is anything to print. Other content is returned unchanged.
- `yield()` for a section inside a `section()`, `append()`, or `prepend()` block of the same section fails the render, as the section is not complete yet. It used to return the incomplete content, so a layout that wrapped the page's section this way lost its wrapper or printed appended content twice. Build on the content with `rewrite()` instead.
- `ArrayProxy` implements `IteratorAggregate` instead of `Iterator`, so its public `current()`, `key()`, `next()`, `rewind()`, and `valid()` methods are gone. `IteratorProxy` implements `IteratorAggregate` instead of extending `IteratorIterator`, and its `unwrap()` returns the wrapped `Traversable` itself instead of `?Iterator`. `foreach`, `count()`, array access, and the predicate methods work as before.
- `RenderException` now carries the integer code of the exception it wraps instead of `0`, so error handlers that derive a status from the code keep working for exceptions thrown inside templates. Non-integer codes, such as PDO's SQLSTATE, still become `0`.
- `Engine::method()` and `Template::method()` reject the names of built-in template helpers, such as `include`, `section`, `yield`, `escape`, or `slot`, in any letter case, with `UnexpectedValueException`. A method registered under one of them was never reachable, because the helper took precedence.
- `isset($items['key'])` and `$items['key'] ?? $default` on a wrapped array treat a `null` value as missing, like a plain array. `exists()` still tests for the key alone.

### Removed

- `$this->has()` is gone. Check the result of `yield()` instead: `<?php if ($sidebar = $this->yield('sidebar', '')) : ?><aside><?= $sidebar ?></aside><?php endif ?>`, or pass a default when the check only guarded a missing section. `has()` was also true for a section that held only appended or prepended content or only whitespace, so it answered neither whether main content was captured nor whether there was anything to print. `has` is free as a template method name.
- Closure slots, `Slot::template()`, and the `Slot` class are gone, `include()` (formerly `insert()`) no longer takes a `slot` argument, and `slot()` no longer takes data. Pass a block with `component()` … `end()` instead. To repeat a block per row, write the loop at the call site with a component per row, or pass the name of a row template to the partial, which includes it per row. See [slots](docs/slots.md#repeat-a-block).
- The `TemplateContext` class is gone. Templates run in `Context`, which is now final; replace `TemplateContext` type hints, such as `/** @var TemplateContext $this */`, with `Context`. The internal `BaseTemplate` class is merged into `Template`, which keeps only its documented API: its constructor no longer takes the internal `$sections` parameter, so `engine` is the second parameter, and the internal `sections` and `blocks` properties and helper methods are gone. The constructor of `Context` is internal; templates receive their context and never create one.

### Fixed

- Repeated `append()` calls print in call order instead of in reverse, so sibling partials that append their scripts keep their order. A layout's additions still stay closer to the main content than the page's: page prepends, layout prepends, main content, layout appends, page appends. The additions of an included template and of its own layouts stay together at the place of the include, so a partial's layout does not put its scripts ahead of the main layout's. `section()` (formerly `begin()`) after `append()` or `prepend()` sets only the main content instead of discarding the additions. Templates that relied on either behavior print their section content in a different order.
- Keys in loops over wrapped arrays and iterators are now escaped. They used to reach templates raw, so in an escaped render `<option value="<?= $key ?>">` let markup or a quote in a key through, for example from user-provided option values or category names. String keys of wrapped arrays and all keys of wrapped iterators are now wrapped like values; integer array keys stay integers. This is a breaking change for templates that compare keys with `===`, use them as offsets into unwrapped arrays, or pass them to `string` parameters under `strict_types`: use `$key->is()` or `$this->unwrap($key)`. `iterator_to_array()` on a wrapped array with string keys now fails; use `$this->unwrap()` instead. Wrapped arrays accept wrapped keys for array access and `exists()`.
- Methods of a wrapped traversable object, such as a collection or a menu item with children, return wrapped values like those of other wrapped objects, so the strings they return are escaped. `IteratorIterator` used to forward the calls to the object and return raw values, so `<?= $item->title() ?>` printed markup unescaped. Properties, `isset()`, string conversion, and invocation now work on such objects too. The wrapped iterator's own methods, `toArray()`, `getIterator()`, `unwrap()`, `is()`, and `in()`, take precedence over same-named methods of the object.
- Nested loops over the same wrapped array no longer end the outer loop after its first pass. This includes a partial that loops over a list it shares with the calling template's loop. Wrapped `IteratorAggregate` values such as `ArrayObject` get a fresh iterator for every loop, as in plain PHP; iterators and generators keep their single cursor.
- Unwrapping a wrapped `IteratorAggregate` returns the original object instead of its internal iterator. This applies to `$this->unwrap()`, `is()`, and arguments passed to template methods, so a method typed `ArrayObject $items` accepts a wrapped `ArrayObject`.
- `isset()`, `empty()`, and `??` on properties of wrapped objects now follow the object. `isset()` used to be false even for a set property, and `??` on an undeclared property threw instead of returning the fallback. The object's own `__isset()` is used when it has one.
- Reading or setting a property of a wrapped object touches only that property. Boiler used to look the name up in a copy of all the object's properties on every access, which ran the get hook of every hooked property each time and slowed down templates that read many properties. Which properties are accessible is unchanged, with one exception: a write-only virtual property can now be set through the wrapped object instead of failing with `No such property`.
- Stacked layouts pass the context on: an outer layout now receives the context of the layout it wraps, including the values that layout got from its `$this->layout()` call. Previously every layout received only the page context plus its own `layout()` values, so a value the page passed to the inner layout was undefined in the outer one.
- `yield()` no longer stores its default in the section, so every `yield()` call prints its own default when no `section()` captured the main content. Before, the first default stuck to a section with additions and was printed by every later call.
- `method()` on a template from `Engine::template()` registered the method on the engine, so it leaked into every later render and replaced same-named methods of other templates. It now applies to that template, its includes, and its layouts. Engine methods, including ones registered later, stay available unless the template overrides them.
- A template from `Engine::template()`, or one created with `new Template($path, engine: $engine)`, now renders with the engine's defaults and trusted classes, like `Engine::render()`. It used to get only the context and trusted classes passed to its render call, so a template that read a default raised an undefined variable warning, and instances of the engine's trusted classes were escaped. Trusted classes passed to the render call add to the engine's.
- A `Template` instance can render again from within its own render, for example from a template method that renders a tree of nodes with the same template. It used to fail with `layout already set` or `No open section or component to close`, because the render state lived on the instance. A `Template` created with `new Template($path, engine: $engine)` now also sees the engine's template methods, like one from `Engine::template()`.
- Context values named `context` or `templatePath` now reach the template. Boiler's internal render arguments used to take their place, so `$context` held the whole context array and `$templatePath` the template's file path.
- A layout cycle, such as `a` using the layout `b` and `b` using `a` again, raises `LogicException` at the `layout()` call that closes it. It used to loop until `max_execution_time`, and forever in a worker or CLI process without a time limit. A template can now appear only once in a chain of layouts, so a layout that wraps itself also fails, even if its context would end the recursion.
- A lookup that misses in every template directory raises a `LookupException` naming the reason for each directory, such as `Template not found: /app/templates/page; Template not found: /vendor/templates/page`. It used to name only the last directory searched.
- A `Template` created from a relative or symlinked path keeps the line numbers in its error messages and locations, and detects a layout cycle back to itself before running again. It now resolves its path when it is created, so `$template->path` holds the resolved file path. `new Template($path, engine: $engine)` with a missing file now raises `LookupException` when it is created, like a standalone template, instead of failing during the render.
- A directory whose name matches a template name no longer counts as a template. `exists()` returned true for it, and rendering it emitted include warnings and returned an empty string; it now raises `LookupException` like a missing template.
- The docs promised `LookupException` for an include that cannot be resolved. Like every error raised while a template runs, it arrives as `RenderException` with the `LookupException` as `getPrevious()`; the docs now describe this contract.
- The PHPDoc callback types of `ArrayProxy::map()` and `reduce()` described a sort comparator returning `int`, so Psalm rejected valid callbacks in code that calls them.

## [0.8.0](https://codefloe.com/celema/boiler/src/tag/0.8.0) (2026-08-14)

### Added

- Added `Proxy::is()` for strict comparison of the raw value behind a proxy: `$item->status->is('active')`, `$item->status->is(Status::Active)`. `===` compares the proxy object itself and is always false against a plain value, and `==` compares the escaped string, which breaks once the value contains HTML special characters. See [comparing wrapped values](docs/values.md#comparing-wrapped-values).
- Added `Proxy::in()`, the list version of `is()`: strict comparison of the raw value against each element of a plain or wrapped array, unwrapping proxy elements: `$item->status->in([Status::Draft, Status::Pending])`.
- Added `StringProxy::matches()`, running `preg_match()` on the raw value: `$slug->matches('/^[a-z0-9-]+$/')`. An invalid pattern throws instead of returning false with a warning.
- Added `StringProxy::contains()`, `startsWith()`, and `endsWith()`, testing the raw value and accepting a wrapped needle. The native `str_*` functions coerce a proxy via `__toString()` in templates without `strict_types` and silently search the escaped text.
- Added `ArrayProxy::contains()` for strict element membership on the raw array, unwrapping a proxy argument: `$tags->contains('featured')`. Wrapped iterators deliberately have no `contains()`, because checking would consume a single-pass generator.

### Changed

- The `Proxy` interface gained the `is()` and `in()` methods; custom implementations must add them. On wrapped strings they join `escape` and `unwrap` as reserved names that shadow filters registered under them, and on wrapped objects they shadow same-named methods of the proxied object.
- `matches`, `contains`, `startsWith`, and `endsWith` are reserved names on wrapped strings as well; a filter registered under one of them is shadowed by the new methods.

## [0.7.0](https://codefloe.com/celema/boiler/src/tag/0.7.0) (2026-08-03)

### Fixed

- Trusted classes are now honored at any depth. The list was only consulted for top-level context values, so an object of a trusted class stayed unwrapped as `$user` but was wrapped in an `ObjectProxy` as `$users[0]` — where magic `__get` and `__call` no longer resolve. Trust now applies to array elements and iterator values as well.

### Changed

- `Contract\Wrapper` gained `withTrusted(list<class-string> $trusted): static`, returning a wrapper that leaves instances of those classes alone. `Context` builds its wrapper through it, which is how trust reaches nested values. Custom `Contract\Wrapper` implementations must add the method.
- Trust applies to automatic wrapping only. `$this->wrap()` keeps returning a proxy for an instance of a trusted class, the way it already does in unescaped renders.

## [0.6.0](https://codefloe.com/celema/boiler/src/tag/0.6.0) (2026-07-18)

### Changed

- Renamed the Composer package to `celema/boiler` and moved PHP classes from `Celemas\Boiler` to `Celema\Boiler`.

### Removed

- Removed the previous Composer package name and PHP namespace; consumers must update their dependency and imports.

## [0.5.0](https://codefloe.com/celema/boiler/src/tag/0.5.0) (2026-06-21)

### Breaking Changes

- Added `Context::insert()`'s optional `slot` parameter and the reserved template helper names `slot()` and `hasSlot()`. Custom `Context` subclasses that override `insert()` must accept the new `Closure|Slot|null` parameter, and custom template methods with those names are now shadowed by the built-in slot helpers.

### Added

- Added scoped slots: `insert()` accepts an optional `slot` closure that the inserted template renders, and repeats with per-call data, via `$this->slot([...])`. Added `$this->hasSlot()` to detect an optional slot. See [slots](docs/slots.md).
- Added `Slot::template()` for slots that render another template with merged slot context.
- Slot errors, including calling `$this->slot()` without a provided slot, are reported at the `insert()` call site.

## [0.4.0](https://codefloe.com/celema/boiler/src/tag/0.4.0) (2026-05-12)

### Breaking Changes

- Rename package metadata, root namespace, repository URLs, homepage, and author info.

## [0.3.3](https://codefloe.com/celema/boiler/src/tag/0.3.3) (2026-04-24)

### Breaking

- Tightened undocumented template internals and layout support APIs. The `$this->layout()` helper now requires an array context when a second argument is provided, and the undocumented `Template::layout()` inspection accessor was removed.

### Added

- Improved render error reporting so runtime errors, inserted-template errors, missing layouts, and unclosed sections point to the relevant template file and line.
- Added `Location` and `location()` on Boiler runtime/render exceptions so integrations can read structured template file and line information.

### Fixed

- Allowed inserted templates to render inside active section capture blocks without being reported as unclosed sections, while still detecting inserts that close a parent section unexpectedly.

## [0.3.2](https://codefloe.com/celema/boiler/src/tag/0.3.2) (2026-04-23)

### Added

- Added `safe: true` support to `Engine::method()` and `Template::method()` so helpers can return safe HTML in escaped renders without manual unwrapping while still allowing safety-preserving string filter chains.

## [0.3.1](https://codefloe.com/celema/boiler/src/tag/0.3.1) (2026-04-15)

Included repository housekeeping updates and enabled CI runs for pull requests.

## [0.3.0](https://codefloe.com/celema/boiler/src/tag/0.3.0) (2026-04-11)

### Breaking

- Renamed `whitelist` to `trusted` across the `Engine` and standalone `Template` APIs. Named argument calls must now use `trusted: [...]`.
- Renamed `Engine::registerMethod()` and standalone `Template::registerMethod()` to `method()`.
- Changed `Engine::__construct()` to accept a `Contract\Resolver` and `Contract\Environment` instead of template directories. Use `Engine::create()` or `Engine::unescaped()` when you want Boiler's built-in setup.
- Renamed `Engine::getFile()` to `Engine::resolve()`.
- Renamed `Context::esc()` to `Context::escape()` and `Context::context()` to `Context::get()`.
- Changed `Wrapper` from a static helper into an instance-based API and replaced the template escape API's `htmlspecialchars()` flags and encoding arguments with named escapers.
- `symfony/html-sanitizer` is now optional. Install it explicitly when you want the built-in `sanitize` filter.
- Boiler now requires the `ext-mbstring` extension.

### Added

- Added `Contract\Resolver` and `Resolver` for template lookup.
- Added `Contract\Environment` and `Environment` for advanced wrapper, filter, and escaper configuration.
- Added `Contract\Wrapper`, `Contract\Escaper`, `Contract\Escapers`, `Contract\Filter`, `Contract\Filters`, `Contract\RegistersEscapers`, and `Contract\RegistersFilters`, plus the default `Escapers` and `Filters` registries.
- Added `Contract\PreservesSafety` for filters that preserve already-safe HTML without claiming to sanitize arbitrary input.
- Added advanced wrapper configuration through `Environment::setWrapper()`, `Environment::setFilters()`, and `Environment::setEscapers()`.
- Added `Engine::filter()` and `Engine::escape()` for registering custom filters and escapers. Wrapped strings can call registered filters as virtual methods.
- Added `Context::wrap()` so templates can opt into wrapper proxy behavior for raw values.
- Added built-in `lower`, `upper`, `stripTags`, and `trim` filters, plus the optional `sanitize` filter when `symfony/html-sanitizer` is installed.

### Fixed

- Made registered template methods available in inserted templates and layouts.
- Hardened template path resolution to reject traversal outside configured roots, including sibling directories with shared prefixes.
- Raised a render error for unclosed section capture blocks.
- Unwrapped wrapped proxy values before array assignments and before invoking object methods, setters, or `__invoke()`.

### Removed

- Removed `Context::clean()` and the `Sanitizer` class; use `$this->wrap($value)->sanitize()` or other filter pipelines instead.
- Removed `Contract\Engine`, `Contract\Template`, and `Contract\MethodRegister`.
- Removed support for subclassing `Engine`, `Template`, and `TemplateContext`; these classes are now final.

## [0.2.0](https://codefloe.com/celema/boiler/src/tag/0.2.0) (2026-03-25)

### Breaking

- Renamed `Context::raw()` to `Context::unwrap()`.
- Changed registered template methods to receive normal PHP values instead of wrapped proxies. In escaped renders, Boiler now wraps returned values again before exposing them to templates.
- Replaced the old `ValueProxy` wrapper with dedicated `StringProxy` and `ObjectProxy` types, and renamed `ProxyInterface` to `Proxy`.

### Added

- Added support for extending `Engine` and `Template` in custom integrations.
- Added `$this->unwrap($value)` so templates can recover original values from escaped proxies.

### Changed

- Improved template render hot-path performance.

### Fixed

- Fixed path traversal bypass for template names ending in `.php`.
- Fixed `ArrayProxy::offsetSet` dropping string keys when assigning via array syntax.
- Fixed `Sections::end()` consuming an unrelated output buffer when called without a matching `begin()`.
- Allowed resources in template context values without triggering unsupported type errors.
- Reset per-render template state so a `Template` instance can be reused safely across multiple renders.

## [0.1.2](https://codefloe.com/celema/boiler/src/tag/0.1.2) (2026-01-30)

### Added

- Added Composer post-install and post-update hooks to sync Celemas development configuration.

### Changed

- Updated `symfony/html-sanitizer` requirement to `^8.0`.
- Simplified development and CI tooling by relying on `celemas/dev` (updated to `^2.4`).
- Removed `minimum-stability` and `prefer-stable` from `composer.json`.

### Removed

- Removed MkDocs-based documentation tooling.

## [0.1.1](https://codefloe.com/celema/boiler/src/tag/0.1.1) (2026-01-26)

### Fixed

- Fixed `Engine` handling of `is_null` condition checks.

## [0.1.0](https://codefloe.com/celema/boiler/src/tag/0.1.0) (2026-01-25)

Initial version.

### Added

- Native PHP 8.5+ template engine (no custom template syntax)
- `Engine` API to render templates from one or more directories (including namespaced paths and override resolution)
- Global template context with support for default values
- Automatic escaping of strings and `Stringable` values, with per-engine and per-render escape controls
- Layouts (including stacked layouts) and inserts/partials
- Sections with default values and append/prepend capabilities
- HTML sanitization helper powered by `symfony/html-sanitizer`
- Support for custom template methods and optional trusted classes
