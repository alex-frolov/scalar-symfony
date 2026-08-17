# Scalar Symfony Bundle

Render modern [Scalar](https://scalar.com) API References in Symfony from **any** OpenAPI document.

Scalar is an open-source, beautiful and interactive API reference renderer — a modern
replacement for the classic Swagger UI. This bundle does one thing: serve the Scalar
reference page from your Symfony app in a single route, pointing at your OpenAPI spec.

No OpenAPI generation included (and none required) — bring your spec from any source:
a static `openapi.yaml`, `swagger-php`, NelmioApiDocBundle, API Platform, or an
external URL.

## Why this bundle?

| Scenario | NelmioApiDocBundle 5 (+Scalar UI) | **scalar-symfony** |
|---|---|---|
| I already use Nelmio | ✅ built-in Scalar renderer | ✅ compatible (just point `url` at your nelmio spec route) |
| I use swagger-php / static spec / API Platform | ❌ would need Nelmio as a dependency | ✅ standalone, no Nelmio required |
| Lightweight docs page, one route | — | ✅ one bundle, one route |
| Symfony 6.4+ | needs upgrade to v5 | ✅ works on 6.4 / 7.x / 8.x |

## Requirements

- PHP >= 8.2
- Symfony 6.4 / 7.x / 8.x (framework-bundle + twig-bundle)
- An OpenAPI document served at a public URL (e.g. `/openapi.yaml`)

## Installation

```bash
composer require alex-frolov/scalar-symfony
```

## Configuration

Create `config/packages/scalar_symfony.yaml`:

```yaml
scalar_symfony:
    # Public URL of your OpenAPI document (required)
    url: '/openapi.yaml'

    # Route path (default: /scalar)
    path: '/scalar'

    # CDN of the Scalar standalone bundle (default: jsdelivr)
    cdn: 'https://cdn.jsdelivr.net/npm/@scalar/api-reference@1.65.1'

    # Well-known options (theme, metadata, integration identifier)
    configuration:
        theme: 'default'          # default | alternate | none ...
        metaData:
            title: 'API Reference'
            description: 'Public API documentation'

    # Any other Scalar option, passed through verbatim
    scalar_options:
        darkMode: false
        layout: 'modern'

    # Access control
    access_control:
        mode: public              # 'public' | 'attribute'
        attribute: ~              # e.g. ROLE_ADMIN (required when mode: attribute)
```

Import the bundle routes (add to `config/routes.yaml`):

```yaml
scalar_symfony:
    resource: '@ScalarSymfonyBundle/config/routes.php'
```

That's it. Your API reference is now served at `/scalar` (route name
`scalar_symfony_reference`).

## Access control

- **`public`** (default) — the reference is available to everyone (recommended for
  public API docs; matches the default Laravel integration behaviour).
- **`attribute`** — the page requires a security attribute:

```yaml
scalar_symfony:
    url: '/openapi.yaml'
    access_control:
        mode: attribute
        attribute: ROLE_ADMIN
```

The attribute mode requires [symfony/security-core](https://symfony.com/doc/current/security.html)
and an enabled `security.authorization_checker` service (i.e. Symfony Security configured).
Requests without the attribute get a `403`.

## How it works

The bundle serves a small HTML page that loads the Scalar standalone bundle from the
CDN and calls `Scalar.createApiReference('#scalar-api-reference', { ... })` with your
configuration and the `url` of the OpenAPI document. The document itself is loaded
client-side by Scalar — the bundle never parses or proxies it.

## Development

```bash
composer install
composer test        # PHPUnit functional tests
composer analyse     # PHPStan level max
composer fix         # PHP-CS-Fixer
```

For the reproducible Docker validation pipeline from the repository root:

```bash
./validate.sh
```

The pipeline runs the regular checks in a pinned Composer image and then tests
PHP 8.2 with Symfony 6.4 and lowest dependencies in a disposable copy. The
images can be overridden when validating a security-patched base image:

```bash
COMPOSER_IMAGE=composer:2.8.12 \
PHP82_BASE_IMAGE=php:8.2-cli-bookworm \
./validate.sh
```

## License

MIT — see [LICENSE](LICENSE).

## Credits

Heavily inspired by [scalar/laravel](https://github.com/scalar/laravel) (Scalar OpenAPI
References in Laravel) and the [Scalar](https://github.com/scalar/scalar) project
(open-source API platform, MIT).
