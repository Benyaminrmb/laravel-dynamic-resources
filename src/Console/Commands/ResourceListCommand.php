<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Console\Commands;

use Benyaminrmb\LaravelDynamicResources\DynamicResource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Finder\Finder;

/**
 * List all DynamicResource classes in the application.
 *
 * ## Usage
 *
 * ```bash
 * php artisan resource:list
 * php artisan resource:list --modes
 * php artisan resource:list --json
 * ```
 */
#[AsCommand(name: 'resource:list')]
class ResourceListCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'resource:list
        {--filter= : Filter resources by name}
        {--modes : Show available modes for each resource}
        {--json : Output as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all DynamicResource classes in the application';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $resources = $this->discoverResources();

        if ($this->option('filter')) {
            $filter = (string) $this->option('filter');
            $resources = array_filter(
                $resources,
                static fn(array $r): bool => str_contains(
                    strtolower((string) $r['class']),
                    strtolower($filter)
                )
            );
        }

        if ($this->option('json')) {
            $this->line(json_encode(array_values($resources), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        }

        if ($resources === []) {
            $this->info('No DynamicResource classes found.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Found %d DynamicResource class(es):', count($resources)));
        $this->newLine();

        foreach ($resources as $resource) {
            $this->line(sprintf('  <fg=green>%s</>', $resource['class']));

            if ($this->option('modes') && isset($resource['modes'])) {
                foreach ($resource['modes'] as $mode) {
                    $default = $mode === 'default' ? ' <fg=yellow>(default)</>' : '';
                    $this->line(sprintf('    - %s%s', $mode, $default));
                }
            }

            $this->newLine();
        }

        return self::SUCCESS;
    }

    /**
     * Discover all DynamicResource classes.
     *
     * @return array<int, array{class: class-string, modes?: list<string>}>
     */
    protected function discoverResources(): array
    {
        $paths = (array) config('dynamic-resources.discovery.paths', [app_path('Http/Resources')]);
        $baseClass = (string) config(
            'dynamic-resources.discovery.base_class',
            DynamicResource::class
        );

        $resources = [];

        foreach ($paths as $path) {
            if (!File::isDirectory($path)) {
                continue;
            }

            $finder = new Finder();
            $finder->files()->name('*.php')->in($path);

            foreach ($finder as $file) {
                $className = $this->getClassNameFromFile($file->getRealPath());

                if ($className === null) {
                    continue;
                }

                if (!class_exists($className)) {
                    continue;
                }

                $reflection = new ReflectionClass($className);

                if ($reflection->isAbstract()) {
                    continue;
                }

                if (!$reflection->isSubclassOf($baseClass)) {
                    continue;
                }

                $resourceData = [
                    'class' => $className,
                ];

                // Get modes if requested
                if ($this->option('modes')) {
                    try {
                        /** @var DynamicResource $instance */
                        $instance = $reflection->newInstanceWithoutConstructor();
                        $resourceData['modes'] = array_keys($instance->fields());
                    } catch (\Throwable) {
                        $resourceData['modes'] = ['Unable to determine modes'];
                    }
                }

                $resources[] = $resourceData;
            }
        }

        // Sort by class name
        usort($resources, static fn(array $a, array $b): int => strcmp((string) $a['class'], (string) $b['class']));

        return $resources;
    }

    /**
     * Get the fully qualified class name from a file.
     *
     * @return class-string|null
     */
    protected function getClassNameFromFile(string $path): ?string
    {
        $contents = (string) file_get_contents($path);

        // Extract namespace
        if (preg_match('/namespace\s+([^;]+);/', $contents, $namespaceMatch) !== 1) {
            return null;
        }

        // Extract class name
        if (preg_match('/class\s+(\w+)/', $contents, $classMatch) !== 1) {
            return null;
        }

        /** @var class-string */
        return $namespaceMatch[1] . '\\' . $classMatch[1];
    }
}
