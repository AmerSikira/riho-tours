<?php

namespace App\Services\Contracts;

use Illuminate\Support\Str;

class ContractTemplateRenderer
{
    /**
     * Render a contract template by replacing all placeholders.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $computedPlaceholders
     */
    public function render(string $templateHtml, array $data, array $computedPlaceholders = []): string
    {
        $rendered = $templateHtml;

        foreach ($this->extractPlaceholders($templateHtml) as $placeholder) {
            $rawToken = '{{ '.$placeholder.' }}';
            $pattern = '/{{\s*'.preg_quote($placeholder, '/').'\s*}}/';

            if (array_key_exists($placeholder, $computedPlaceholders)) {
                $value = $computedPlaceholders[$placeholder];
                $rendered = preg_replace($pattern, $value, $rendered) ?? $rendered;

                continue;
            }

            $value = data_get($data, $placeholder);

            if (is_numeric($value)) {
                $safe = (string) $value;
            } elseif (is_string($value)) {
                $safe = e($value);
            } elseif (is_null($value)) {
                $safe = '';
            } else {
                $safe = e((string) json_encode($value));
            }

            if (Str::contains($rendered, $rawToken)) {
                $rendered = str_replace($rawToken, $safe, $rendered);

                continue;
            }

            $rendered = preg_replace($pattern, $safe, $rendered) ?? $rendered;
        }

        return $rendered;
    }

    /**
     * Extract unique placeholder keys from template content.
     *
     * @return list<string>
     */
    public function extractPlaceholders(string $templateHtml): array
    {
        preg_match_all('/{{\s*([a-zA-Z0-9_\.]+)\s*}}/', $templateHtml, $matches);

        $keys = $matches[1] ?? [];

        return array_values(array_unique($keys));
    }
}
