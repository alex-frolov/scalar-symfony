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

### Known limitation: only the page is protected, not the spec

The `attribute` mode protects **only the HTML reference page** (`/scalar`). The
OpenAPI document itself (the `url` you configured, e.g. `/openapi.yaml`) is
fetched **client-side by the Scalar bundle in the user's browser** — it is never
served through this bundle and the bundle does **not** proxy it.

Practical consequences:

- If the document is served at a public URL (the default assumption), it stays
  publicly downloadable even when the reference page is behind `attribute`.
- If the document is protected by its own firewall rules, an authorised user's
  browser may fail to load it (403/CORS), breaking the reference page.

Treat `attribute` as protection of the *page*, not of the *spec*. If the spec
must stay confidential, protect the document with the same rule (or use a
protected proxy endpoint — planned).

## How it works

The bundle serves a small HTML page that loads the Scalar standalone bundle
from the CDN and calls `Scalar.createApiReference('#scalar-api-reference', {...})`
with your configuration and the `url` of the OpenAPI document. The document
itself is loaded client-side by Scalar — the bundle never parses or proxies it.

## Hardening: SRI, CSP and self-hosting

The reference page loads the Scalar standalone bundle from a CDN and runs an
inline `Scalar.createApiReference(...)` call. If the CDN is compromised (or the
`cdn` value is replaced), arbitrary JavaScript executes with your origin's
privileges. This section documents what you can do today. Built-in `sri` /
`nonce` configuration is a planned improvement (see the project TODO).

### Self-hosting (recommended)

Download the standalone bundle once and serve it from your own origin — no
third-party request at page load, no CDN trust at all:

```bash
# Pin the version and download the original package file (not the jsDelivr
# minifier output — its hash is not stable, see SRI below):
curl -fLo public/scalar/api-reference.standalone.js \
  https://cdn.jsdelivr.net/npm/@scalar/api-reference@1.65.1/dist/browser/standalone.js
shasum -a 384 public/scalar/api-reference.standalone.js
```

```yaml
# config/packages/scalar_symfony.yaml
scalar_symfony:
    url: '/openapi.yaml'
    cdn: '/scalar/api-reference.standalone.js'
```

The file is served by your own web server with no extra dependency.

### SRI (Subresource Integrity)

When keeping a third-party CDN, pin the expected content hash so a
compromised or swapped file is rejected by the browser.

- The *root* jsDelivr URL (`…/@scalar/api-reference@1.65.1`) is **minified on
  the fly** by jsDelivr — the response carries the warning *"Do NOT use SRI
  with dynamically generated files!"*. Its hash can change without a package
  release, so it is not SRI-safe.
- The explicit file URL (`…/dist/browser/standalone.js`) is served as-is from
  the immutable npm tarball. Its SHA-384 for 1.65.1 is:

  `sha384-G6dkutu2k5IYVyNESLoFIpgaHx38IJTZ/HhrwN0fecTle9te75y8Kru3rJEJ0ZJV`

The bundle does not render `integrity` yet. To apply SRI today, override the
template (`templates/bundles/ScalarSymfonyBundle/reference.html.twig`) and add
the attributes to the CDN script tag:

```html
<script src="{{ cdn }}"
        integrity="sha384-G6dkutu2k5IYVyNESLoFIpgaHx38IJTZ/HhrwN0fecTle9te75y8Kru3rJEJ0ZJV"
        crossorigin="anonymous"></script>
```

`crossorigin="anonymous"` is required for cross-origin SRI (CORS-enabled
request so the integrity check works).

### CSP and nonce

If your app sends a `Content-Security-Policy` with a restrictive `script-src`,
the inline `Scalar.createApiReference(...)` script must be allowlisted —
prefer a per-request nonce over `'unsafe-inline'`. A nonce-aware config option
is a planned improvement; until then, override the template and add
`nonce="{{ csp_nonce }}"` to both `<script>` tags.

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
