# ScalarSymfonyBundle

Render modern [Scalar](https://scalar.com) API References in Symfony from
any OpenAPI document.

- [Installation](#installation)
- [Configuration](#configuration)
- [Access control](#access-control)
- [How it works](#how-it-works)
- [Development](#development)

## Installation

```bash
composer require alex-frolov/scalar-symfony
```

## Configuration

```yaml
# config/packages/scalar_symfony.yaml
scalar_symfony:
    # Public URL of your OpenAPI document (required)
    url: '/openapi.yaml'

    # Route path (default: /scalar)
    path: '/scalar'

    # CDN of the Scalar standalone bundle (default: jsdelivr)
    cdn: 'https://cdn.jsdelivr.net/npm/@scalar/api-reference@1.65.1'

    # Well-known options (theme, metadata, integration identifier)
    configuration:
        theme: 'default'
        metaData:
            title: 'API Reference'
            description: 'Public API documentation'

    # Any other Scalar option, passed through verbatim
    scalar_options:
        darkMode: false
        layout: 'modern'

    # Access control
    access_control:
        mode: public        # 'public' | 'attribute'
        attribute: ~        # e.g. ROLE_ADMIN (required when mode: attribute)
```

Import the bundle routes:

```yaml
# config/routes.yaml
scalar_symfony:
    resource: '@ScalarSymfonyBundle/config/routes.php'
```

The reference is served at `/scalar` (route name `scalar_symfony_reference`).

## Access control

- `public` (default) — the reference is available to everyone.
- `attribute` — the page requires a security attribute:

```yaml
scalar_symfony:
    url: '/openapi.yaml'
    access_control:
        mode: attribute
        attribute: ROLE_ADMIN
```

Requires [symfony/security-core](https://symfony.com/doc/current/security.html)
and Symfony Security enabled (`security.authorization_checker` service).
Requests without the attribute get a `403`.

## How it works

The bundle serves a small HTML page that loads the Scalar standalone bundle
from the CDN and calls `Scalar.createApiReference('#scalar-api-reference', {...})`
with your configuration and the `url` of the OpenAPI document. The document
itself is loaded client-side by Scalar — the bundle never parses or proxies it.

## Development

```bash
composer install
composer test        # PHPUnit functional tests
composer analyse     # PHPStan level max
composer fix         # PHP-CS-Fixer
```

Run the full reproducible validation pipeline from the repository root:

```bash
./validate.sh
```

It also checks PHP 8.2, Symfony 6.4, and lowest dependencies in a temporary
copy of the bundle.

## License

MIT — see [LICENSE](../LICENSE).

## Credits

Heavily inspired by [scalar/laravel](https://github.com/scalar/laravel) (Scalar
OpenAPI References in Laravel) and the [Scalar](https://github.com/scalar/scalar)
project (open-source API platform, MIT).
