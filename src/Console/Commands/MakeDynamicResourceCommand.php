<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Console\Commands;

use Benyaminrmb\LaravelDynamicResources\Services\ViewSymbolGenerator;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * Artisan command to generate a new DynamicResource.
 *
 * ## Usage
 *
 * ```bash
 * php artisan make:dynamic-resource UserResource
 * php artisan make:dynamic-resource UserResource --model=User
 * php artisan make:dynamic-resource UserResource --views=minimal,default,detailed
 * ```
 */
#[AsCommand(name: 'make:dynamic-resource')]
class MakeDynamicResourceCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:dynamic-resource';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new DynamicResource class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Resource';

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/dynamic-resource.stub');
    }

    /**
     * Resolve the fully-qualified path to the stub.
     */
    protected function resolveStubPath(string $stub): string
    {
        $customPath = $this->laravel->basePath(trim($stub, '/'));

        if (file_exists($customPath)) {
            return $customPath;
        }

        return __DIR__.'/../..'.$stub;
    }

    /**
     * Get the default namespace for the class.
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Http\Resources';
    }

    /**
     * Build the class with the given name.
     */
    protected function buildClass($name): string
    {
        $stub = parent::buildClass($name);
        $model = $this->option('model');
        $modelClass = is_string($model) && $model !== '' ? $this->qualifyModel($model) : null;

        $stub = str_replace(
            ['{{ modelImport }}', '{{ modelDoc }}'],
            [
                $modelClass !== null ? sprintf("use %s;\n", $modelClass) : '',
                $modelClass !== null
                    ? sprintf('@property %s $resource', class_basename($modelClass))
                    : '@property object $resource',
            ],
            $stub,
        );

        $views = $this->option('views');

        if (! is_string($views)) {
            throw new InvalidArgumentException('The views option must be a comma-separated string.');
        }

        return $this->replaceViews($stub, $views);
    }

    /**
     * Qualify the given model class.
     */
    protected function qualifyModel(string $model): string
    {
        $model = ltrim($model, '\\/');
        $model = str_replace('/', '\\', $model);

        $rootNamespace = $this->rootNamespace();

        $qualified = Str::startsWith($model, $rootNamespace)
            ? $model
            : $rootNamespace.'Models\\'.$model;

        if (! class_exists($qualified)) {
            throw new InvalidArgumentException(sprintf("Model class '%s' does not exist.", $qualified));
        }

        return $qualified;
    }

    /**
     * Replace the view placeholders.
     */
    protected function replaceViews(string $stub, string $views): string
    {
        $viewsList = array_values(array_unique(array_filter(array_map('trim', explode(',', $views)))));

        if (! in_array('default', $viewsList, true)) {
            throw new InvalidArgumentException('Generated resources must include a default view.');
        }

        foreach ($viewsList as $view) {
            if (preg_match('/^[a-z][a-z0-9-]*$/', $view) !== 1) {
                throw new InvalidArgumentException(sprintf("Invalid view name '%s'.", $view));
            }
        }

        $viewsCode = $this->generateViewsCode($viewsList);
        $symbols = app(ViewSymbolGenerator::class)->region($viewsList);

        return str_replace(['{{ views }}', '{{ viewSymbols }}'], [$viewsCode, $symbols], $stub);
    }

    /**
     * Generate the views array code.
     *
     * @param  list<string>  $views
     */
    protected function generateViewsCode(array $views): string
    {
        $lines = [];

        foreach ($views as $view) {
            $method = Str::camel($view).'Fields';
            $attribute = $view === 'default' ? '#[View(default: true)]' : '#[View]';
            $lines[] = sprintf(
                "    %s\n    protected function %s(): array\n    {\n        return [\n            'id',\n            // Add fields for the %s view.\n        ];\n    }",
                $attribute,
                $method,
                $view,
            );
        }

        return implode("\n\n", $lines);
    }

    /**
     * Get the console command options.
     *
     * @return array<int, array<int, mixed>>
     */
    protected function getOptions(): array
    {
        return [
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'The model class this resource represents'],
            ['views', null, InputOption::VALUE_OPTIONAL, 'Comma-separated list of views to generate', 'minimal,default,detailed'],
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the resource already exists'],
        ];
    }
}
