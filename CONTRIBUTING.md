# Contributing

Thank you for helping improve Laravel Dynamic Resources.

## Development setup

You need PHP 8.3 or newer, Composer, and Git.

```bash
git clone https://github.com/Benyaminrmb/laravel-dynamic-resources.git
cd laravel-dynamic-resources
composer install
composer check
```

## Pull requests

- Keep changes focused and explain the user-visible behavior.
- Add or update tests for every behavior change and regression fix.
- Update README and upgrade guidance when the public API changes.
- Preserve Laravel-native resource behavior unless the package explicitly documents a difference.
- Never add serialization-time database queries.
- Run `composer check` before opening the pull request.

The project uses Pest, PHPStan at its maximum level, and Laravel Pint. CI tests PHP 8.3–8.5 against Laravel 12 and 13, including lowest supported dependencies.

## Compatibility changes

Changes to supported PHP or Laravel versions, public methods, query semantics, response shapes, exceptions, or generated files are breaking changes. Describe them in `CHANGELOG.md` and `UPGRADE.md`.

## Security reports

Do not open public issues for vulnerabilities. Follow [SECURITY.md](SECURITY.md).
