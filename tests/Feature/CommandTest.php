<?php

declare(strict_types=1);

use Benyaminrmb\LaravelDynamicResources\Tests\Fixtures\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    $this->generatedResource = app_path('Http/Resources/GeneratedUserResource.php');
    File::delete($this->generatedResource);
});

afterEach(function (): void {
    File::delete($this->generatedResource);
});

it('generates a valid resource with model metadata and requested modes', function (): void {
    class_alias(User::class, 'App\\Models\\User');

    $this->artisan('make:dynamic-resource', [
        'name' => 'GeneratedUserResource',
        '--model' => 'User',
        '--modes' => 'minimal,default,detailed',
    ])->assertSuccessful();

    $source = File::get($this->generatedResource);

    expect($source)
        ->toContain('use App\\Models\\User;')
        ->toContain('@property User $resource')
        ->toContain("'minimal' => [")
        ->toContain("'default' => [")
        ->toContain("'detailed' => [");
});

it('lists discovered dynamic resources as json', function (): void {
    $this->artisan('make:dynamic-resource', [
        'name' => 'GeneratedUserResource',
        '--modes' => 'minimal,default',
    ])->assertSuccessful();
    require_once $this->generatedResource;

    $exitCode = Artisan::call('dynamic-resources:list', ['--modes' => true, '--json' => true]);

    expect($exitCode)->toBe(0)
        ->and(Artisan::output())
        ->toContain('GeneratedUserResource')
        ->toContain('minimal');
});
