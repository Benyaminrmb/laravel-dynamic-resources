<?php

declare(strict_types=1);

use Benyaminrmb\LaravelDynamicResources\DTO\ModeConfiguration;

describe('ModeConfiguration', function (): void {
    it('creates with default values', function (): void {
        $config = new ModeConfiguration(activeModes: ['default']);

        expect($config->activeModes)->toBe(['default'])
            ->and($config->requestedFields)->toBe([])
            ->and($config->excludedFields)->toBe([])
            ->and($config->explicitlySet)->toBeFalse();
    });

    it('adds mode immutably', function (): void {
        $config = new ModeConfiguration(activeModes: ['default']);
        $newConfig = $config->addMode('avatar');

        expect($config->activeModes)->toBe(['default'])
            ->and($newConfig->activeModes)->toBe(['default', 'avatar'])
            ->and($newConfig->explicitlySet)->toBeTrue();
    });

    it('does not add duplicate mode', function (): void {
        $config = new ModeConfiguration(activeModes: ['default', 'avatar']);
        $newConfig = $config->addMode('avatar');

        expect($newConfig->activeModes)->toBe(['default', 'avatar']);
    });

    it('removes mode immutably', function (): void {
        $config = new ModeConfiguration(activeModes: ['default', 'avatar']);
        $newConfig = $config->removeMode('avatar');

        expect($config->activeModes)->toBe(['default', 'avatar'])
            ->and($newConfig->activeModes)->toBe(['default']);
    });

    it('falls back to default when removing last mode', function (): void {
        $config = new ModeConfiguration(activeModes: ['avatar']);
        $newConfig = $config->removeMode('avatar');

        expect($newConfig->activeModes)->toBe(['default']);
    });

    it('checks if mode is active', function (): void {
        $config = new ModeConfiguration(activeModes: ['default', 'avatar']);

        expect($config->hasMode('default'))->toBeTrue()
            ->and($config->hasMode('avatar'))->toBeTrue()
            ->and($config->hasMode('posts'))->toBeFalse();
    });

    it('replaces modes with withModes', function (): void {
        $config = new ModeConfiguration(activeModes: ['default']);
        $newConfig = $config->withModes(['minimal', 'avatar']);

        expect($newConfig->activeModes)->toBe(['minimal', 'avatar'])
            ->and($newConfig->explicitlySet)->toBeTrue();
    });

    it('deduplicates modes in withModes', function (): void {
        $config = new ModeConfiguration(activeModes: ['default']);
        $newConfig = $config->withModes(['minimal', 'avatar', 'minimal', 'avatar']);

        expect($newConfig->activeModes)->toBe(['minimal', 'avatar']);
    });

    it('adds requested fields immutably', function (): void {
        $config = new ModeConfiguration(activeModes: ['default']);
        $newConfig = $config->withRequestedFields(['id', 'name']);

        expect($config->requestedFields)->toBe([])
            ->and($newConfig->requestedFields)->toBe(['id', 'name']);
    });

    it('adds excluded fields immutably', function (): void {
        $config = new ModeConfiguration(activeModes: ['default']);
        $newConfig = $config->withExcludedFields(['created_at']);

        expect($config->excludedFields)->toBe([])
            ->and($newConfig->excludedFields)->toBe(['created_at']);
    });

    it('checks for field restrictions', function (): void {
        $noRestrictions = new ModeConfiguration(activeModes: ['default']);
        $withOnly = new ModeConfiguration(activeModes: ['default'], requestedFields: ['id']);
        $withExcept = new ModeConfiguration(activeModes: ['default'], excludedFields: ['id']);

        expect($noRestrictions->hasFieldRestrictions())->toBeFalse()
            ->and($withOnly->hasFieldRestrictions())->toBeTrue()
            ->and($withExcept->hasFieldRestrictions())->toBeTrue();
    });
});
