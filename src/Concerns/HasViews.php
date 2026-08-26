<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Concerns;

use Benyaminrmb\LaravelDynamicResources\Exceptions\UndefinedViewException;

trait HasViews
{
    /** @var list<string>|null */
    private ?array $activeViews = null;

    private bool $viewExplicitlySet = false;

    /** @param string|list<string> $views */
    public function view(string|array $views): static
    {
        $views = is_string($views) ? [$views] : array_values(array_unique($views));
        $views = $views === [] ? [$this->defaultView()] : $views;

        foreach ($views as $view) {
            $this->assertViewExists($view);
        }

        $this->activeViews = $views;
        $this->viewExplicitlySet = true;

        return $this;
    }

    /** @param string|list<string> $views */
    public function withView(string|array $views): static
    {
        $active = $this->getActiveViews();

        foreach (is_string($views) ? [$views] : $views as $view) {
            $this->assertViewExists($view);

            if (! in_array($view, $active, true)) {
                $active[] = $view;
            }
        }

        $this->activeViews = $active;
        $this->viewExplicitlySet = true;

        return $this;
    }

    /** @param string|list<string> $views */
    public function withoutView(string|array $views): static
    {
        $removed = is_string($views) ? [$views] : $views;
        $active = array_values(array_filter(
            $this->getActiveViews(),
            static fn (string $view): bool => ! in_array($view, $removed, true),
        ));

        $this->activeViews = $active === [] ? [$this->defaultView()] : $active;
        $this->viewExplicitlySet = true;

        return $this;
    }

    /** @return list<string> */
    public function getActiveViews(): array
    {
        return $this->activeViews ?? [$this->defaultView()];
    }

    public function hasView(string $view): bool
    {
        return in_array($view, $this->getActiveViews(), true);
    }

    public function hasViewExplicitlySet(): bool
    {
        return $this->viewExplicitlySet;
    }

    /**
     * @internal
     *
     * @param  list<string>  $views
     */
    public function inheritViews(array $views, bool $explicit): void
    {
        $this->activeViews = $views;
        $this->viewExplicitlySet = $explicit;
    }

    private function assertViewExists(string $view): void
    {
        if (! in_array($view, $this->getAvailableViews(), true)) {
            throw new UndefinedViewException($view, static::class, $this->getAvailableViews());
        }
    }
}
