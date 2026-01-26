<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
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
 * php artisan make:dynamic-resource UserResource --modes=minimal,default,detailed
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

        return __DIR__ . '/../..' . $stub;
    }

    /**
     * Get the default namespace for the class.
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . '\Http\Resources';
    }

    /**
     * Build the class with the given name.
     */
    protected function buildClass($name): string
    {
        $stub = parent::buildClass($name);

        $model = $this->option('model');
        if ($model) {
            $stub = $this->replaceModel($stub, $model);
        }

        $modes = $this->option('modes');
        if ($modes) {
            $stub = $this->replaceModes($stub, $modes);
        }

        return $stub;
    }

    /**
     * Replace the model placeholder.
     */
    protected function replaceModel(string $stub, string $model): string
    {
        $modelClass = $this->qualifyModel($model);
        $modelBaseName = class_basename($modelClass);

        return str_replace(
            ['{{ modelClass }}', '{{ model }}', 'DummyModel'],
            [$modelClass, $modelBaseName, $modelBaseName],
            $stub
        );
    }

    /**
     * Qualify the given model class.
     */
    protected function qualifyModel(string $model): string
    {
        $model = ltrim($model, '\\/');
        $model = str_replace('/', '\\', $model);

        $rootNamespace = $this->rootNamespace();

        if (Str::startsWith($model, $rootNamespace)) {
            return $model;
        }

        return $rootNamespace . 'Models\\' . $model;
    }

    /**
     * Replace the modes placeholder.
     */
    protected function replaceModes(string $stub, string $modes): string
    {
        $modesList = array_map('trim', explode(',', $modes));
        $modesCode = $this->generateModesCode($modesList);

        return str_replace('{{ modes }}', $modesCode, $stub);
    }

    /**
     * Generate the modes array code.
     *
     * @param list<string> $modes
     */
    protected function generateModesCode(array $modes): string
    {
        $lines = [];

        foreach ($modes as $mode) {
            $lines[] = sprintf(
                "            '%s' => [\n                'id',\n                // Add fields for %s mode\n            ],",
                $mode,
                $mode
            );
        }

        return implode("\n", $lines);
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
            ['modes', null, InputOption::VALUE_OPTIONAL, 'Comma-separated list of modes to generate', 'minimal,default,detailed'],
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the resource already exists'],
        ];
    }
}
