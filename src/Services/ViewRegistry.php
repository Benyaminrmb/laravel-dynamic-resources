<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Services;

use Benyaminrmb\LaravelDynamicResources\Attributes\View;
use Benyaminrmb\LaravelDynamicResources\DTO\ViewDefinition;
use Benyaminrmb\LaravelDynamicResources\DynamicResource;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;

final class ViewRegistry
{
    /** @var array<class-string<DynamicResource>, array<string, ViewDefinition>> */
    private static array $cache = [];

    /** @param class-string<DynamicResource> $resourceClass
     * @return array<string, ViewDefinition>
     */
    public function for(string $resourceClass): array
    {
        return self::$cache[$resourceClass] ??= $this->discover($resourceClass);
    }

    /** @param class-string<DynamicResource> $resourceClass */
    public function defaultFor(string $resourceClass): string
    {
        foreach ($this->for($resourceClass) as $definition) {
            if ($definition->default) {
                return $definition->name;
            }
        }

        throw new RuntimeException(sprintf('%s must define exactly one default #[View].', $resourceClass));
    }

    /** @param class-string<DynamicResource> $resourceClass
     * @return list<string>
     */
    public function requestableFor(string $resourceClass): array
    {
        return array_values(array_map(
            static fn (ViewDefinition $view): string => $view->name,
            array_filter($this->for($resourceClass), static fn (ViewDefinition $view): bool => $view->requestable),
        ));
    }

    /** @param class-string<DynamicResource> $resourceClass
     * @return array<string, ViewDefinition>
     */
    private function discover(string $resourceClass): array
    {
        $definitions = [];
        $defaults = 0;

        foreach ((new ReflectionClass($resourceClass))->getMethods() as $method) {
            $attributes = $method->getAttributes(View::class);

            if ($attributes === []) {
                continue;
            }

            $this->validateMethod($method);
            $attribute = $attributes[0]->newInstance();
            $name = $attribute->name ?? $this->inferName($method->getName());

            if (preg_match('/^[a-z][a-z0-9-]*$/', $name) !== 1) {
                throw new InvalidArgumentException(sprintf("Invalid view name '%s' on %s::%s().", $name, $resourceClass, $method->getName()));
            }

            if (isset($definitions[$name])) {
                throw new InvalidArgumentException(sprintf("Duplicate view '%s' on %s.", $name, $resourceClass));
            }

            $defaults += (int) $attribute->default;
            $definitions[$name] = new ViewDefinition($name, $method, $attribute->default, $attribute->requestable);
        }

        if ($definitions === [] || $defaults !== 1) {
            throw new RuntimeException(sprintf('%s must define exactly one default #[View].', $resourceClass));
        }

        return $definitions;
    }

    private function validateMethod(ReflectionMethod $method): void
    {
        if ($method->isStatic() || $method->isPublic() || $method->getNumberOfRequiredParameters() !== 0) {
            throw new InvalidArgumentException(sprintf(
                'View method %s::%s() must be protected, non-static, and parameterless.',
                $method->getDeclaringClass()->getName(),
                $method->getName(),
            ));
        }
    }

    private function inferName(string $method): string
    {
        $name = preg_replace('/Fields$/', '', $method) ?? $method;

        return strtolower((string) preg_replace('/([a-z0-9])([A-Z])/', '$1-$2', $name));
    }
}
