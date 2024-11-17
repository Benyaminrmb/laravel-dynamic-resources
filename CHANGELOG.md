# Changelog

All notable changes to `laravel-dynamic-resources` will be documented in this file.

## [0.1.0] - 2024-11-17

### Added
- ✨ New modular mode system with chainable methods
- 🔌 Support for dynamic mode combinations using `with` and `without` prefixes
- 🔗 Ability to combine multiple modes in both single resources and collections
- 💫 Automatic mode inheritance for nested resources
- 📚 Comprehensive documentation for new features

### Changed
- 🏗️ Refactored mode handling to support multiple active modes
- 🔄 Updated `fields()` method to use array keys for modes
- ⚡ Improved performance by merging fields from active modes
- 🔧 Modified magic method handling for better extensibility

### Breaking Changes
- Changed the structure of the `fields()` method to use mode names as array keys
- Removed single mode limitation in favor of multiple mode support
- Updated method signatures for better type safety and PHP 8.2 compatibility

### Migration Guide

#### Before (0.1.x)
```php
protected function fields(): array
{
    return match($this->mode) {
        'minimal' => [
            'id',
            'username',
        ],
        'minimal-with-avatar' => [
            'id',
            'username',
            'profile',
        ],
        default => [
            'id',
            'username',
        ]
    };
}
```

#### After (0.1.x)
```php
protected function fields(): array
{
    return [
        'minimal' => [
            'id',
            'username',
        ],
        'avatar' => [
            'profile' => UploadResource::make($this->channel->profile)->minimal(),
        ],
        'default' => [
            'id',
            'username',
        ]
    ];
}
```

#### Usage Changes
```php
// Old way (0.0.x)
UserResource::make($user)->setMode('minimal-with-avatar');

// New way (0.1.x)
UserResource::make($user)
    ->minimal()
    ->withAvatar();
```

### Fixed
- 🐛 Fixed mode inheritance in nested resources
- 🔒 Improved type safety throughout the codebase
- ♻️ Better handling of default modes when others are removed
- 🧹 Cleaned up method signatures for better IDE support

For more details about the changes and new features, please refer to the updated README.md file.
