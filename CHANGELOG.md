# Changelog

All notable changes to this project are documented here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and releases follow [Semantic Versioning](https://semver.org/).

## [Unreleased]

## [2.0.0] - Unreleased

### Added

- Explicit `mode()`, `withMode()`, and `withoutMode()` APIs alongside magic mode methods.
- Explicit `fromRequest()` sparse field selection with per-resource mode allowlists.
- Bounded dotted field and exclusion paths for nested resources and collections.
- HTTP 400 errors for invalid resource query parameters.
- Laravel 12 and 13 support across PHP 8.3, 8.4, and 8.5.
- Circular mode-inheritance detection and comprehensive CI quality checks.

### Changed

- Nested resources now retain their own default or explicitly selected mode.
- Collections now preserve Laravel-native response metadata and pagination behavior.
- `additional()` data is emitted at the response top level.
- Mode inheritance inside `fields()` now uses `fieldsFrom()`.
- `resource:list` is now `dynamic-resources:list`.
- Runtime dependencies use focused Illuminate components instead of `laravel/framework`.

### Removed

- Legacy `ModularResource`, `ModularResourceCollection`, and legacy service provider.
- Middleware-driven query selection and the `resource.fields` middleware alias.
- Unimplemented attributes, transformation contracts, automatic relationship loading, and fake cache/debug configuration.
- Laravel 11 and PHP versions below 8.3.

## [0.1.5] - 2024-11-17

### Added

- Original composable mode API and nested resource support.

[Unreleased]: https://github.com/Benyaminrmb/laravel-dynamic-resources/compare/v2.0.0...HEAD
[2.0.0]: https://github.com/Benyaminrmb/laravel-dynamic-resources/compare/v0.1.5...v2.0.0
[0.1.5]: https://github.com/Benyaminrmb/laravel-dynamic-resources/releases/tag/v0.1.5
