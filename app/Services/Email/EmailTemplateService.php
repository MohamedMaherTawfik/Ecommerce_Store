<?php

namespace App\Services\Email;

use App\Models\EmailTemplate;
use App\Security\HtmlSanitizer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class EmailTemplateService
{
    public function __construct(private readonly HtmlSanitizer $sanitizer) {}

    public function render(string $key, array $variables, string $fallbackSubject, string $fallbackHtml): array
    {
        $template = $this->find($key);
        $subject = $template?->subject ?: $fallbackSubject;
        $html = $template?->html_body ?: $fallbackHtml;

        return [
            'subject' => $this->replace($subject, $variables, false),
            'html' => $this->sanitizer->sanitize($this->replace($html, $variables, true)),
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

    private function replace(string $content, array $variables, bool $escape): string
    {
        $defaults = [
            'app_name' => config('app.name'),
            'app_url' => config('app.url'),
        ];

        return preg_replace_callback('/\{\{\s*([A-Za-z0-9_.-]+)\s*\}\}/', function ($matches) use ($variables, $defaults, $escape) {
            $value = $variables[$matches[1]] ?? $defaults[$matches[1]] ?? $matches[0];

            if (! is_scalar($value)) {
                return $matches[0];
            }

            return $escape
                ? htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                : (string) $value;
        }, $content);
    }
}
