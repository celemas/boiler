# Changelog

## [Unreleased](https://codeberg.org/celemas/boiler/compare/0.4.0...HEAD)

### Breaking Changes

- Added `Context::insert()`'s optional `slot` parameter and the reserved template helper names `slot()` and `hasSlot()`. Custom `Context` subclasses that override `insert()` must accept the new parameter, and custom template methods with those names are now shadowed by the built-in slot helpers.

### Added

- Added scoped slots: `insert()` accepts an optional `slot` closure that the inserted template renders, and repeats with per-call data, via `$this->slot([...])`. Added `$this->hasSlot()` to detect an optional slot. See [slots](docs/slots.md).

## [0.4.0](https://codeberg.org/celemas/boiler/src/tag/0.4.0) (2026-05-12)

### Breaking Changes

- Rename package metadata, root namespace, repository URLs, homepage, and author info.

## [0.3.3](https://codeberg.org/celemas/boiler/src/tag/0.3.3) (2026-04-24)

### Breaking

- Tightened undocumented template internals and layout support APIs. The `$this->layout()` helper now requires an array context when a second argument is provided, and the undocumented `Template::layout()` inspection accessor was removed.

### Added

- Improved render error reporting so runtime errors, inserted-template errors, missing layouts, and unclosed sections point to the relevant template file and line.
- Added `Location` and `location()` on Boiler runtime/render exceptions so integrations can read structured template file and line information.

### Fixed

- Allowed inserted templates to render inside active section capture blocks without being reported as unclosed sections, while still detecting inserts that close a parent section unexpectedly.

## [0.3.2](https://codeberg.org/celemas/boiler/src/tag/0.3.2) (2026-04-23)

### Added

- Added `safe: true` support to `Engine::method()` and `Template::method()` so helpers can return safe HTML in escaped renders without manual unwrapping while still allowing safety-preserving string filter chains.

## [0.3.1](https://codeberg.org/celemas/boiler/src/tag/0.3.1) (2026-04-15)

Included repository housekeeping updates and enabled CI runs for pull requests.

## [0.3.0](https://codeberg.org/celemas/boiler/src/tag/0.3.0) (2026-04-11)

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

## [0.2.0](https://codeberg.org/celemas/boiler/src/tag/0.2.0) (2026-03-25)

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

## [0.1.2](https://codeberg.org/celemas/boiler/src/tag/0.1.2) (2026-01-30)

### Added

- Added Composer post-install and post-update hooks to sync Celemas development configuration.

### Changed

- Updated `symfony/html-sanitizer` requirement to `^8.0`.
- Simplified development and CI tooling by relying on `celemas/dev` (updated to `^2.4`).
- Removed `minimum-stability` and `prefer-stable` from `composer.json`.

### Removed

- Removed MkDocs-based documentation tooling.

## [0.1.1](https://codeberg.org/celemas/boiler/src/tag/0.1.1) (2026-01-26)

### Fixed

- Fixed `Engine` handling of `is_null` condition checks.

## [0.1.0](https://codeberg.org/celemas/boiler/src/tag/0.1.0) (2026-01-25)

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
