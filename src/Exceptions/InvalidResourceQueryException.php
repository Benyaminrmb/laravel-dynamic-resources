<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Exceptions;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class InvalidResourceQueryException extends BadRequestHttpException implements DynamicResourceException
{
    public function __construct(
        string $message,
        public readonly string $parameter,
    ) {
        parent::__construct($message);
    }

    public static function modeNotAllowed(string $mode): self
    {
        return new self(sprintf("The requested resource mode '%s' is not allowed.", $mode), 'mode');
    }

    public static function malformed(string $parameter): self
    {
        return new self(sprintf("The resource query parameter '%s' is malformed.", $parameter), $parameter);
    }

    public static function limitExceeded(string $parameter, int $limit): self
    {
        return new self(sprintf("The resource query parameter '%s' exceeds the limit of %d entries.", $parameter, $limit), $parameter);
    }

    public function getSuggestion(): ?string
    {
        return 'Use a permitted mode and a comma-separated list of valid field paths.';
    }

    public function toArray(): array
    {
        return [
            'type' => 'invalid_resource_query',
            'parameter' => $this->parameter,
            'message' => $this->getMessage(),
        ];
    }
}
