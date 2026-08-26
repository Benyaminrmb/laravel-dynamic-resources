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
 * php artisan dynamic-resources:list
 * php artisan dynamic-resources:list --views
 * php artisan dynamic-resources:list --json
 * ```
 */
#[AsCommand(name: 'dynamic-resources:list')]
class ResourceListCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'dynamic-resources:list
        {--filter= : Filter resources by name}
        {--views : Show available views for each resource}
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

        $filter = $this->option('filter');

        if (is_string($filter) && $filter !== '') {
            $resources = array_filter(
                $resources,
                static fn (array $r): bool => str_contains(
                    strtolower($r['class']),
                    strtolower($filter),
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

            if ($this->option('views') && isset($resource['views'])) {
                foreach ($resource['views'] as $view) {
                    $default = $view === ($resource['default_view'] ?? '') ? ' <fg=yellow>(default)</>' : '';
                    $this->line(sprintf('    - %s%s', $view, $default));
                }
            }

            $this->newLine();
        }

        return self::SUCCESS;
    }

    /**
     * Discover all DynamicResource classes.
     *
     * @return array<int, array{class: class-string, views?: list<string>, default_view?: string}>
     */
    protected function discoverResources(): array
    {
        $configuredPaths = config('dynamic-resources.discovery.paths', [app_path('Http/Resources')]);
        $paths = is_array($configuredPaths)
            ? array_values(array_filter($configuredPaths, 'is_string'))
            : [app_path('Http/Resources')];
        $configuredBaseClass = config('dynamic-resources.discovery.base_class', DynamicResource::class);
        $baseClass = is_string($configuredBaseClass) && class_exists($configuredBaseClass)
            ? $configuredBaseClass
            : DynamicResource::class;

        $resources = [];

        foreach ($paths as $path) {
            if (! File::isDirectory($path)) {
                continue;
            }

            $finder = new Finder;
            $finder->files()->name('*.php')->in($path);

            foreach ($finder as $file) {
                $className = $this->getClassNameFromFile($file->getRealPath());

                if ($className === null) {
                    continue;
                }

                if (! class_exists($className)) {
                    continue;
                }

                $reflection = new ReflectionClass($className);

                if ($reflection->isAbstract()) {
                    continue;
                }

                if (! $reflection->isSubclassOf($baseClass)) {
                    continue;
                }

                $resourceData = [
                    'class' => $className,
                ];

                // Get views if requested
                if ($this->option('views')) {
                    try {
                        $instance = new $className(null);

                        if ($instance instanceof DynamicResource) {
                            $resourceData['views'] = $instance->getAvailableViews();
                            $resourceData['default_view'] = $instance->defaultView();
                        }
                    } catch (\Throwable $exception) {
                        $resourceData['views'] = [sprintf('Unable to determine views: %s', $exception->getMessage())];
                        $resourceData['default_view'] = '';
                    }
                }

                $resources[] = $resourceData;
            }
        }

        // Sort by class name
        usort($resources, static fn (array $a, array $b): int => strcmp((string) $a['class'], (string) $b['class']));

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
        return $namespaceMatch[1].'\\'.$classMatch[1];
    }
}
