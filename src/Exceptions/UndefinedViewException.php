<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Exceptions;

use RuntimeException;

class UndefinedViewException extends RuntimeException implements DynamicResourceException
{
    /** @param class-string $resourceClass
     * @param  list<string>  $availableViews
     */
    public function __construct(
        public readonly string $view,
        public readonly string $resourceClass,
        public readonly array $availableViews = [],
    ) {
        $available = $availableViews === [] ? 'none defined' : implode(', ', $availableViews);
        $suggestion = $this->getSuggestion();

        parent::__construct(sprintf(
            "View '%s' is not defined in %s. Available views: %s.%s",
            $view,
            $resourceClass,
            $available,
            $suggestion === null ? '' : " {$suggestion}",
        ));
    }

    public function getSuggestion(): ?string
    {
        $closest = null;
        $distance = PHP_INT_MAX;

        foreach ($this->availableViews as $available) {
            $candidate = levenshtein($this->view, $available);
            if ($candidate < $distance && $candidate <= 3) {
                $closest = $available;
                $distance = $candidate;
            }
        }

        return $closest === null ? null : "Did you mean '{$closest}'?";
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'type' => 'undefined_view',
            'view' => $this->view,
            'resource' => $this->resourceClass,
            'available_views' => $this->availableViews,
            'suggestion' => $this->getSuggestion(),
        ];
    }
}
