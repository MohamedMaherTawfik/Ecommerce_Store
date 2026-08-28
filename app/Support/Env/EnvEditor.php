<?php

namespace App\Support\Env;

use Illuminate\Support\Facades\File;
use RuntimeException;

class EnvEditor
{
    public function get(string $key, mixed $default = null): mixed
    {
        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            return $default;
        }

        $lines = preg_split('/\r\n|\r|\n/', File::get($envPath)) ?: [];

        foreach ($lines as $line) {
            if (! str_starts_with(trim($line), "{$key}=")) {
                continue;
            }

            [, $value] = array_pad(explode('=', $line, 2), 2, '');

            return $this->normalizeStoredValue($value);
        }

        return $default;
    }

    /**
     * @param  array<string, scalar|null>  $pairs
     */
    public function setMany(array $pairs): void
    {
        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            File::put($envPath, '', true);
        }

        $content = File::get($envPath);
        $lines = preg_split('/\r\n|\r|\n/', $content) ?: [];
        $handled = [];

        foreach ($lines as $index => $line) {
            $trimmed = ltrim($line);

            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            [$candidateKey] = array_pad(explode('=', $line, 2), 1, null);

            if ($candidateKey === null) {
                continue;
            }

            $candidateKey = trim($candidateKey);

            if (! array_key_exists($candidateKey, $pairs)) {
                continue;
            }

            if (in_array($candidateKey, $handled, true)) {
                $lines[$index] = null;

                continue;
            }

            $lines[$index] = $candidateKey.'='.$this->formatValue($pairs[$candidateKey]);
            $handled[] = $candidateKey;
        }

        foreach ($pairs as $key => $value) {
            if (in_array($key, $handled, true)) {
                continue;
            }

            $lines[] = $key.'='.$this->formatValue($value);
        }

        $updated = rtrim(implode(PHP_EOL, array_values(array_filter($lines, static fn ($line) => $line !== null)))).PHP_EOL;

        if (File::put($envPath, $updated, true) === false) {
            throw new RuntimeException("Unable to update environment file at {$envPath}");
        }
    }

    private function normalizeStoredValue(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"'))
            || (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        return stripcslashes($value);
    }

    private function formatValue(mixed $value): string
    {
        $stringValue = (string) ($value ?? '');

        if ($stringValue === '') {
            return '';
        }

        if (preg_match('/[\s#="\'`$\\\\]/', $stringValue) === 1) {
            return '"'.addcslashes($stringValue, '\\"$').'"';
        }

        return $stringValue;
    }
}
