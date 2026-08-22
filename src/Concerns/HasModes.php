<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Concerns;

use BadMethodCallException;
use Benyaminrmb\LaravelDynamicResources\Exceptions\UndefinedModeException;

/**
 * @method array<string, array<int|string, mixed>|\Closure> fields()
 * @method string defaultMode()
 */
trait HasModes
{
    /** @var list<string>|null */
    private ?array $activeModes = null;

    private bool $modeExplicitlySet = false;

    public function mode(string $mode): static
    {
        return $this->setActiveModes([$mode]);
    }

    public function withMode(string $mode): static
    {
        $this->assertModeExists($mode);
        $modes = $this->getActiveModes();

        if (! in_array($mode, $modes, true)) {
            $modes[] = $mode;
        }

        $this->activeModes = $modes;
        $this->modeExplicitlySet = true;

        return $this;
    }

    public function withoutMode(string $mode): static
    {
        $modes = array_values(array_filter(
            $this->getActiveModes(),
            static fn (string $activeMode): bool => $activeMode !== $mode,
        ));

        $this->activeModes = $modes === [] ? [$this->defaultMode()] : $modes;
        $this->modeExplicitlySet = true;

        return $this;
    }

    /** @param list<string> $modes */
    public function setActiveModes(array $modes): static
    {
        $modes = array_values(array_unique($modes));
        $modes = $modes === [] ? [$this->defaultMode()] : $modes;

        foreach ($modes as $mode) {
            $this->assertModeExists($mode);
        }

        $this->activeModes = $modes;
        $this->modeExplicitlySet = true;

        return $this;
    }

    public function addMode(string $mode): static
    {
        return $this->withMode($mode);
    }

    public function removeMode(string $mode): static
    {
        return $this->withoutMode($mode);
    }

    /** @return list<string> */
    public function getActiveModes(): array
    {
        return $this->activeModes ?? [$this->defaultMode()];
    }

    public function hasMode(string $mode): bool
    {
        return in_array($mode, $this->getActiveModes(), true);
    }

    public function hasModeExplicitlySet(): bool
    {
        return $this->modeExplicitlySet;
    }

    /**
     * @internal
     *
     * @param  list<string>  $modes
     */
    public function inheritModes(array $modes, bool $explicit): void
    {
        $this->activeModes = $modes;
        $this->modeExplicitlySet = $explicit;
    }

    /** @param list<mixed> $parameters */
    protected function handleModeCall(string $method, array $parameters): mixed
    {
        if ($parameters !== []) {
            throw new BadMethodCallException(sprintf('Mode method %s::%s() does not accept arguments.', static::class, $method));
        }

        if (str_starts_with($method, 'without')) {
            return $this->withoutMode($this->normalizeModeName(substr($method, 7)));
        }

        if (str_starts_with($method, 'with')) {
            return $this->withMode($this->normalizeModeName(substr($method, 4)));
        }

        $mode = $this->normalizeModeName($method);

        if (array_key_exists($mode, $this->fields())) {
            return $this->mode($mode);
        }

        throw new BadMethodCallException(sprintf(
            'Method %s::%s() does not exist. Available modes: %s.',
            static::class,
            $method,
            implode(', ', array_keys($this->fields())),
        ));
    }

    protected function normalizeModeName(string $name): string
    {
        return strtolower((string) preg_replace('/([a-z0-9])([A-Z])/', '$1-$2', $name));
    }

    private function assertModeExists(string $mode): void
    {
        $available = array_keys($this->fields());

        if (! in_array($mode, $available, true)) {
            throw new UndefinedModeException($mode, static::class, $available);
        }
    }
}
