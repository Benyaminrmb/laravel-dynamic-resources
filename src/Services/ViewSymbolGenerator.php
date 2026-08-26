<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Services;

use Illuminate\Support\Str;
use InvalidArgumentException;

final class ViewSymbolGenerator
{
    public const string START = '    // <dynamic-resource-views>';

    public const string END = '    // </dynamic-resource-views>';

    /** @param list<string> $views */
    public function region(array $views): string
    {
        $constants = [];
        $methods = [];

        foreach ($views as $view) {
            $constant = 'VIEW_'.strtoupper(str_replace('-', '_', $view));
            $method = Str::camel($view);
            $studly = Str::studly($view);

            if (in_array($method, ['view', 'withView', 'withoutView'], true)) {
                throw new InvalidArgumentException(sprintf("View '%s' conflicts with the fluent view API.", $view));
            }

            $constants[] = sprintf("    public const string %s = '%s';", $constant, $view);
            $methods[] = sprintf(
                "    public function %s(): static\n    {\n        return \$this->view(self::%s);\n    }",
                $method,
                $constant,
            );
            $methods[] = sprintf(
                "    public function with%s(): static\n    {\n        return \$this->withView(self::%s);\n    }",
                $studly,
                $constant,
            );
            $methods[] = sprintf(
                "    public function without%s(): static\n    {\n        return \$this->withoutView(self::%s);\n    }",
                $studly,
                $constant,
            );
        }

        return self::START."\n".
            implode("\n", $constants)."\n\n".
            implode("\n\n", $methods)."\n".
            self::END;
    }

    public function replace(string $source, string $region): string
    {
        $pattern = '/'.preg_quote(self::START, '/').'.*?'.preg_quote(self::END, '/').'/s';

        if (preg_match($pattern, $source) !== 1) {
            throw new InvalidArgumentException('Resource is missing the generated view marker region.');
        }

        return (string) preg_replace($pattern, $region, $source, 1);
    }
}
