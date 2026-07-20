<?php

namespace App\Services\Email;

use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class EmailTemplateService
{
    public function render(string $key, array $variables, string $fallbackSubject, string $fallbackHtml): array
    {
        $template = $this->find($key);
        $subject = $template?->subject ?: $fallbackSubject;
        $html = $template?->html_body ?: $fallbackHtml;

        return [
            'subject' => $this->replace($subject, $variables),
            'html' => $this->replace($html, $variables),
        ];
    }

    public function find(string $key): ?EmailTemplate
    {
        if (! Schema::hasTable('email_templates')) {
            return null;
        }

        return Cache::remember(
            "email_template:{$key}",
            now()->addMinutes(30),
            fn () => EmailTemplate::where('key', $key)->where('is_active', true)->first()
        );
    }

    public function clear(string $key): void
    {
        Cache::forget("email_template:{$key}");
    }

    private function replace(string $content, array $variables): string
    {
        $defaults = [
            'app_name' => config('app.name'),
            'app_url' => config('app.url'),
        ];

        return preg_replace_callback('/\{\{\s*([A-Za-z0-9_.-]+)\s*\}\}/', function ($matches) use ($variables, $defaults) {
            $value = $variables[$matches[1]] ?? $defaults[$matches[1]] ?? $matches[0];

            return is_scalar($value) ? (string) $value : $matches[0];
        }, $content);
    }
}
