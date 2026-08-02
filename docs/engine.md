# The engine

The `Engine` is Boiler's central object and usually the only object you need to instantiate manually. It locates templates on disk and renders them with a shared set of defaults, custom methods, and escape rules.

Assume the following directory structure:

```text
path
`-- to
    |-- templates
    `-- additional
```

## Create an engine

Create an engine with one or more template directories:

```php
$engine = \Celema\Boiler\Engine::create('/path/to/templates');
```

If the directory does not exist, Boiler throws `\Celema\Boiler\Exception\LookupException`.

## Use multiple directories

Pass multiple directories when you want fallback lookup or override behavior:

```php
$engine = \Celema\Boiler\Engine::create([
    '/path/to/templates',
    '/path/to/additional',
]);
```

Boiler searches directories in order. If a template is not found in the first one, it keeps searching the next one.

## Use namespaces

Pass an associative array when you want stable names for specific directories:

```php
$engine = \Celema\Boiler\Engine::create([
    'first' => '/path/to/templates',
    'second' => '/path/to/additional',
]);
```

You can later target a specific directory with `namespace:template`:

```php
$engine->render('second:page');
```

Read [rendering templates](rendering.md) for the lookup rules.

## Customize template lookup

Boiler resolves template names through `Celema\Boiler\Contract\Resolver`. `Engine::create()` and `Engine::unescaped()` always use `Celema\Boiler\Resolver`.

Instantiate `Engine` directly when your application needs different lookup rules:

```php
use Celema\Boiler\Contract\Resolver;
use Celema\Boiler\Engine;
use Celema\Boiler\Environment;
use Celema\Boiler\Exception\LookupException;

$engine = new Engine(
    new class implements Resolver {
        public function resolve(string $path): string
        {
            if ($path === 'home') {
                return '/srv/app/theme/home.php';
            }

            throw new LookupException("Template `{$path}` not found");
        }
    },
    new Environment(),
    true,
);
```

Resolver selection happens at engine construction time. Lookup caching is resolver-specific. `Resolver` caches successful resolutions.

## Use a custom environment

Use `Engine::create()` or `Engine::unescaped()` for the common case. Use the constructor when you need to provide your own resolver or `Contract\Environment`, for example to swap the wrapper or plug in custom filter or escaper registries.

```php
use Celema\Boiler\Engine;
use Celema\Boiler\Environment;
use Celema\Boiler\Resolver;

$environment = new Environment();

$engine = new Engine(
    new Resolver('/path/to/templates'),
    $environment,
    true,
);
```

The constructor expects a resolver instance. The factory methods remain the simplest way to build an engine from directory paths with Boiler's built-in resolver.

## Add default values

Pass defaults as the second argument when values should be available in every render:

```php
$engine = \Celema\Boiler\Engine::create(
    '/path/to/templates',
    ['siteName' => 'Celema'],
);
```

Per-render context overrides defaults with the same key. These values become part of the render context, so layouts, inserts, and section captures inherit them unless more specific context overrides a key.

## Configure trusted classes

Pass a list of class names as the third argument when specific objects should be left unwrapped in escaped renders:

```php
$engine = \Celema\Boiler\Engine::create(
    '/path/to/templates',
    defaults: [],
    trusted: [TrustedHtml::class],
);
```

Use this only for values you fully trust. Trusted objects bypass Boiler's normal object wrapping and can output unescaped string content from methods such as `__toString()`. The list applies wherever such an object appears in the context, including inside arrays and iterators.

Read [displaying values](values.md) for the escaping model.

## Customize the wrapper

Set a custom `Wrapper` on `Environment` when you want to replace Boiler's runtime wrapping, escaping, and filter lookup entirely:

```php
use Celema\Boiler\Contract\Escaper;
use Celema\Boiler\Engine;
use Celema\Boiler\Environment;
use Celema\Boiler\Escapers;
use Celema\Boiler\Resolver;
use Celema\Boiler\Wrapper;

$environment = new Environment();
$environment->setWrapper(new Wrapper(new Escapers([
    'html' => new class implements Escaper {
        public function escape(string $value): string
        {
            return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
    },
])));

$engine = new Engine(
    new Resolver('/path/to/templates'),
    $environment,
    true,
);
```

`Contract\Wrapper` covers wrapping, unwrapping, escaping, and filter lookup. Configure advanced wrapper behavior on `Environment` through `setWrapper()`, `setEscapers()`, and `setFilters()`.

`Wrapper` accepts an optional escaper registry and optional pre-registered filters. Boiler's built-in `Escapers` registry includes the `html` escaper, exposes its constructor-configured default via `default`, and supports constructor-seeded entries plus incremental `->register()` calls when you only need extra named escapers instead of a full wrapper replacement.

When you only want to customize escapers, set them on the environment and let Boiler build the wrapper lazily.

Provide an escaper under the `html` name when you want to replace Boiler's built-in HTML escaper:

```php
use Celema\Boiler\Contract\Escaper;
use Celema\Boiler\Engine;
use Celema\Boiler\Environment;
use Celema\Boiler\Escapers;
use Celema\Boiler\Resolver;

$escapers = new Escapers([
    'caps' => new class implements Escaper {
        public function escape(string $value): string
        {
            return strtoupper(
                htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            );
        }
    },
], default: 'caps');

$environment = new Environment();
$environment->setEscapers($escapers);

$engine = new Engine(
    new Resolver('/path/to/templates'),
    $environment,
    true,
);
```

You can also register another named escaper on the registry before you pass it to the environment:

```php
use Celema\Boiler\Contract\Escaper;
use Celema\Boiler\Engine;
use Celema\Boiler\Environment;
use Celema\Boiler\Escapers;
use Celema\Boiler\Resolver;

$escapers = new Escapers();

$escapers->register('caps', new class implements Escaper {
    public function escape(string $value): string
    {
        return strtoupper(
            htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        );
    }
});

$environment = new Environment();
$environment->setEscapers($escapers);

$engine = new Engine(
    new Resolver('/path/to/templates'),
    $environment,
    true,
);
```

You can also register escapers directly on the engine when the environment is managing escapers:

```php
use Celema\Boiler\Contract\Escaper;

$engine = \Celema\Boiler\Engine::create('/path/to/templates')
    ->escape('caps', new class implements Escaper {
        public function escape(string $value): string
        {
            return strtoupper(
                htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            );
        }
    });
```

`Environment::setWrapper()` is mutually exclusive with `Environment::setFilters()`, `Environment::setEscapers()`, and engine-managed registration methods such as `Engine::filter()` and `Engine::escape()`. Once the wrapper is materialized through `wrapper()` or rendering, the environment is sealed.

When you inject custom filter or escaper registries and still want to call `Engine::filter()` or `Engine::escape()`, those registries must also implement `Contract\RegistersFilters` or `Contract\RegistersEscapers`.

## Register filters

Filters are value transformations you can apply to template values. Register filters on the engine with the fluent `filter()` method:

```php
use Celema\Boiler\Contract\Filter;

$engine = \Celema\Boiler\Engine::create('/path/to/templates')
    ->filter('upper', new class implements Filter {
        public function apply(string $value, mixed ...$args): string
        {
            return strtoupper($value);
        }

        public function safe(): bool
        {
            return false;
        }
    });
```

`Engine::filter()` delegates filter registration to the injected environment. If you configure a custom wrapper through `Environment::setWrapper()`, filter registration must be handled by that wrapper setup instead.

Lookups go through `Contract\Filters`, which only needs a `get(string $name): Contract\Filter` method. Registration is an optional capability exposed through `Contract\RegistersFilters`.

Escaper lookups go through `Contract\Escapers`, which expose `default` and `get(string $name): Contract\Escaper`. Escaper registration is exposed separately through `Contract\RegistersEscapers`.

A filter implements `Celema\Boiler\Contract\Filter` with two methods:

- `apply(string $value, mixed ...$args): string` transforms the value.
- `safe(): bool` returns `true` when the filter output is safe HTML from arbitrary input and should skip auto-escaping.

When a filter should keep already-safe HTML safe without claiming to sanitize arbitrary input, implement `Celema\Boiler\Contract\PreservesSafety` as well.

Use filters for transformations. When you need a different escaping context, register a named escaper instead.

Filters are available as virtual methods on wrapped string values in templates:

```php
<?= $title->upper() ?>
<?= $html->sanitize() ?>
<?= $body->stripTags('<b>') ?>
```

In escaped renders, Boiler wraps string values for you. When you need filters on a raw value or in an unescaped render, call `$this->wrap($value)` first:

```php
<?= $this->wrap($html)->sanitize() ?>
```

Filters can be chained. Safe output only stays safe through filters that explicitly preserve safety:

```php
<?= $html->sanitize()->upper() ?>
<?= $html->sanitize()->stripTags('<b>') ?>
```

Boiler ships with built-in filters:

- `sanitize` removes unsafe HTML (requires `symfony/html-sanitizer`). This filter is safe.
- `lower` lowercases text via `mb_strtolower()`. This filter is not safe on arbitrary input, but it preserves already-safe output.
- `upper` uppercases text via `mb_strtoupper()`. This filter is not safe on arbitrary input, but it preserves already-safe output.
- `stripTags` removes HTML tags via `strip_tags()`. This filter is not safe on arbitrary input, but it preserves already-safe output.
- `trim` trims leading and trailing characters via `trim()`. This filter is not safe on arbitrary input, but it preserves already-safe output.

Read [displaying values](values.md) for more on filters and escaping.

## Control escaping

Boiler escapes strings and `Stringable` values by default:

```php
$engine = \Celema\Boiler\Engine::create('/path/to/templates');
$html = $engine->render('page');
```

Create an unescaped engine when automatic escaping should be off by default:

```php
$engine = \Celema\Boiler\Engine::unescaped('/path/to/templates');
$html = $engine->render('page');
```

Override the engine default per render:

```php
$engine = \Celema\Boiler\Engine::create('/path/to/templates');
$engine->renderUnescaped('page');

$engine = \Celema\Boiler\Engine::unescaped('/path/to/templates');
$engine->renderEscaped('page');
```

## Render templates

Render a template by name and optionally pass a context array:

```php
$engine->render('template');
$engine->render('template', ['value1' => 1, 'value2' => 2]);
```

Read [rendering templates](rendering.md) for path syntax, subdirectories, overrides, and namespaces.

## Register custom template methods

Custom methods are available as `$this->methodName()` inside templates, inserts, and layouts:

```php
$engine->method('upper', function (string $value): string {
    return strtoupper($value);
});
```

Boiler unwraps proxy arguments before it calls your method, so the callable receives normal PHP values instead of proxy objects.

In escaped renders, Boiler wraps the return value again before exposing it to the template. In unescaped renders, it returns the unwrapped value.

Pass `safe: true` when a helper returns safe HTML:

```php
use function App\Template\icon;

$engine->method('icon', icon(...), safe: true);
```

That lets you render the helper directly in an escaped template:

```php
<h2><?= $this->icon('check') ?></h2>
```

In escaped renders, safe methods must return `string` or `Stringable`. Boiler exposes that return value as a safe wrapped string, so direct output skips auto-escaping and safety-preserving string filters can still be chained. In unescaped renders, Boiler still returns the unwrapped value.

Use `safe: true` only when the helper itself guarantees safe HTML for the values it accepts.

## Useful methods

### Check whether a template exists

```php
if ($engine->exists('template')) {
    $engine->render('template');
}
```

### Get the resolved file path for a template

```php
$filePath = $engine->resolve('template');
```

### Get a reusable `Template` instance

```php
$template = $engine->template('template');

assert($template instanceof \Celema\Boiler\Template);
```

A `Template` instance can be rendered multiple times safely.
