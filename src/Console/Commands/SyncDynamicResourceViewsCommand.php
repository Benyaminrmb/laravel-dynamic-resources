<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Console\Commands;

use Benyaminrmb\LaravelDynamicResources\DynamicResource;
use Benyaminrmb\LaravelDynamicResources\Services\ViewSymbolGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Finder\Finder;
use Throwable;

#[AsCommand(name: 'dynamic-resources:sync')]
class SyncDynamicResourceViewsCommand extends Command
{
    protected $signature = 'dynamic-resources:sync {--check : Only check whether generated symbols are current}';

    protected $description = 'Synchronize IDE-visible constants and fluent methods for dynamic resource views';

    public function handle(ViewSymbolGenerator $generator): int
    {
        $stale = [];

        foreach ($this->resourceFiles() as $path) {
            try {
                $source = File::get($path);
                $class = $this->classFromSource($source);
                if ($class === null) {
                    continue;
                }
                if (! class_exists($class)) {
                    require_once $path;
                }
                if (! is_subclass_of($class, DynamicResource::class)) {
                    continue;
                }

                /** @var DynamicResource $resource */
                $resource = new $class(null);
                $updated = $generator->replace($source, $generator->region($resource->getAvailableViews()));

                if ($updated === $source) {
                    continue;
                }

                $stale[] = $path;
                if (! $this->option('check')) {
                    File::put($path, $updated);
                    $this->line(sprintf('Synced %s', $class));
                }
            } catch (Throwable $exception) {
                $this->error(sprintf('%s: %s', $path, $exception->getMessage()));

                return self::FAILURE;
            }
        }

        if ($this->option('check') && $stale !== []) {
            $this->error('Dynamic resource IDE symbols are stale. Run php artisan dynamic-resources:sync.');

            return self::FAILURE;
        }

        $this->info($stale === [] ? 'Dynamic resource IDE symbols are current.' : 'Dynamic resource IDE symbols synchronized.');

        return self::SUCCESS;
    }

    /** @return list<string> */
    private function resourceFiles(): array
    {
        $paths = config('dynamic-resources.discovery.paths', [app_path('Http/Resources')]);
        $paths = is_array($paths) ? array_filter($paths, 'is_string') : [];
        $files = [];

        foreach ($paths as $path) {
            if (! File::isDirectory($path)) {
                continue;
            }
            foreach ((new Finder)->files()->name('*.php')->in($path) as $file) {
                $files[] = $file->getRealPath();
            }
        }

        return $files;
    }

    /** @return class-string|null */
    private function classFromSource(string $source): ?string
    {
        if (preg_match('/namespace\s+([^;]+);/', $source, $namespace) !== 1 ||
            preg_match('/class\s+(\w+)/', $source, $class) !== 1) {
            return null;
        }

        /** @var class-string */
        return $namespace[1].'\\'.$class[1];
    }
}
