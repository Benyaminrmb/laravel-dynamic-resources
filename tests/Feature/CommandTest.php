<?php

declare(strict_types=1);

use Benyaminrmb\LaravelDynamicResources\Tests\Fixtures\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use InvalidArgumentException as InvalidArgument;

beforeEach(function (): void {
    $this->generatedResource = app_path('Http/Resources/GeneratedUserResource.php');
    File::delete($this->generatedResource);
});

afterEach(function (): void {
    File::delete($this->generatedResource);
    File::delete(app_path('Http/Resources/PlainResource.php'));
});

it('generates a valid resource with model metadata and requested modes', function (): void {
    class_alias(User::class, 'App\\Models\\User');

    $this->artisan('make:dynamic-resource', [
        'name' => 'GeneratedUserResource',
        '--model' => 'User',
        '--views' => 'minimal,default,detailed',
    ])->assertSuccessful();

    $source = File::get($this->generatedResource);

    expect($source)
        ->toContain('use App\\Models\\User;')
        ->toContain('@property User $resource')
        ->toContain('protected function minimalFields(): array')
        ->toContain('#[View(default: true)]')
        ->toContain("public const string VIEW_DETAILED = 'detailed';")
        ->toContain('public function minimal(): static')
        ->toContain('return $this->view(self::VIEW_MINIMAL);');
});

it('lists discovered dynamic resources as json', function (): void {
    $this->artisan('make:dynamic-resource', [
        'name' => 'GeneratedUserResource',
        '--views' => 'minimal,default',
    ])->assertSuccessful();
    require_once $this->generatedResource;

    $exitCode = Artisan::call('dynamic-resources:list', ['--views' => true, '--json' => true]);

    expect($exitCode)->toBe(0)
        ->and(Artisan::output())
        ->toContain('GeneratedUserResource')
        ->toContain('minimal');
});

it('lists resources and their modes in the console table', function (): void {
    $this->artisan('make:dynamic-resource', [
        'name' => 'GeneratedUserResource',
        '--views' => 'minimal,default',
    ])->assertSuccessful();
    require_once $this->generatedResource;

    $this->artisan('dynamic-resources:list', ['--views' => true])
        ->expectsOutputToContain('Found 1 DynamicResource class(es)')
        ->expectsOutputToContain('App\Http\Resources\GeneratedUserResource')
        ->expectsOutputToContain('minimal')
        ->expectsOutputToContain('(default)')
        ->assertSuccessful();
});

it('reports an empty resource list when discovery finds no matches', function (): void {
    config(['dynamic-resources.discovery.paths' => [app_path('Missing/Resources')]]);

    $this->artisan('dynamic-resources:list')
        ->expectsOutput('No DynamicResource classes found.')
        ->assertSuccessful();
});

it('filters discovered resources by class name', function (): void {
    $this->artisan('make:dynamic-resource', [
        'name' => 'GeneratedUserResource',
    ])->assertSuccessful();
    require_once $this->generatedResource;

    Artisan::call('dynamic-resources:list', ['--filter' => 'does-not-match', '--json' => true]);

    expect(trim(Artisan::output()))->toBe('[]');
});

it('generates a resource without model metadata', function (): void {
    $path = app_path('Http/Resources/PlainResource.php');

    $this->artisan('make:dynamic-resource', ['name' => 'PlainResource'])
        ->assertSuccessful();

    expect(File::get($path))
        ->toContain('@property object $resource')
        ->not->toContain('use App\Models');
});

it('rejects generation without a default view', function (): void {
    expect(fn (): int => Artisan::call('make:dynamic-resource', [
        'name' => 'GeneratedUserResource',
        '--views' => 'minimal,detailed',
    ]))->toThrow(InvalidArgument::class, 'must include a default view');
});

it('rejects invalid mode and model names', function (array $arguments, string $message): void {
    expect(fn (): int => Artisan::call('make:dynamic-resource', $arguments))
        ->toThrow(InvalidArgument::class, $message);
})->with([
    'invalid view' => [[
        'name' => 'GeneratedUserResource',
        '--views' => 'default,Not Valid',
    ], 'Invalid view name'],
    'unknown model' => [[
        'name' => 'GeneratedUserResource',
        '--model' => 'MissingModel',
    ], 'does not exist'],
]);

it('checks and synchronizes generated IDE symbols', function (): void {
    $this->artisan('make:dynamic-resource', [
        'name' => 'GeneratedUserResource',
        '--views' => 'minimal,default',
    ])->assertSuccessful();

    $this->artisan('dynamic-resources:sync', ['--check' => true])->assertSuccessful();

    $source = File::get($this->generatedResource);
    File::put($this->generatedResource, str_replace(
        "public const string VIEW_MINIMAL = 'minimal';",
        "public const string VIEW_MINIMAL = 'stale';",
        $source,
    ));

    $this->artisan('dynamic-resources:sync', ['--check' => true])->assertFailed();
    $this->artisan('dynamic-resources:sync')->assertSuccessful();

    expect(File::get($this->generatedResource))
        ->toContain("public const string VIEW_MINIMAL = 'minimal';");
});
