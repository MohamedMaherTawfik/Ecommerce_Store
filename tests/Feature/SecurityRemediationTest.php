<?php

namespace Tests\Feature;

use App\Models\DatabaseSetting;
use App\Models\EnvironmentSetting;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

class SecurityRemediationTest extends TestCase
{
    use RefreshDatabase;

    public function test_browser_auth_uses_an_http_only_cookie_without_exposing_a_token(): void
    {
        User::factory()->create([
            'email' => 'cookie-user@example.com',
            'password' => Hash::make('ValidPassword1!'),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/users/login', [
            'email' => '  COOKIE-USER@EXAMPLE.COM ',
            'password' => 'ValidPassword1!',
        ])->assertOk()->assertJsonMissingPath('data.token');

        $cookie = $response->getCookie(config('auth_cookie.name'), false);
        $this->assertNotNull($cookie);
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertNotEmpty($cookie->getValue());
    }

    public function test_secret_settings_are_represented_only_as_configured_state(): void
    {
        $sentinel = 'SENTINEL_SECRET_SHOULD_NEVER_APPEAR';
        EnvironmentSetting::create([
            'group' => 'google_oauth',
            'key' => 'GOOGLE_CLIENT_SECRET',
            'value' => Crypt::encryptString($sentinel),
            'type' => 'password',
            'is_encrypted' => true,
        ]);
        DatabaseSetting::create([
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'store',
            'username' => 'store_user',
            'password' => $sentinel,
            'is_active' => true,
        ]);
        SiteSetting::create([
            'key' => 'LEGACY_PAYMENT_SECRET',
            'value' => $sentinel,
        ]);

        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $application = $this->getJson('/api/admin/settings/application')
            ->assertOk()
            ->assertJsonPath('data.values.GOOGLE_CLIENT_SECRET.configured', true);
        $database = $this->getJson('/api/admin/settings/database')
            ->assertOk()
            ->assertJsonPath('data.password.configured', true);
        $siteSettings = $this->getJson('/api/admin/site-settings')->assertOk();
        $this->getJson('/api/admin/site-settings/LEGACY_PAYMENT_SECRET')->assertForbidden();
        $this->postJson('/api/admin/site-settings/create', [
            'key' => 'NEW_API_KEY',
            'value' => $sentinel,
        ])->assertUnprocessable();

        $this->assertStringNotContainsString($sentinel, $application->getContent());
        $this->assertStringNotContainsString($sentinel, $database->getContent());
        $this->assertStringNotContainsString($sentinel, $siteSettings->getContent());
    }

    public function test_email_template_html_is_sanitized_and_previewed_as_inert_content(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $response = $this->postJson('/api/admin/email-templates', [
            'key' => 'xss_regression',
            'name' => 'XSS regression',
            'subject' => 'Safe subject',
            'html_body' => '<p>Safe {{user_name}}</p><img src="https://example.com/a.png" onerror="xssMarker()"><script>xssMarker()</script><a href="javascript:xssMarker()">bad</a>',
            'variables' => ['user_name'],
            'is_active' => true,
        ])->assertOk();

        $templateId = $response->json('data.id');
        $stored = (string) $response->json('data.html_body');
        $this->assertStringNotContainsStringIgnoringCase('<script', $stored);
        $this->assertStringNotContainsStringIgnoringCase('onerror', $stored);
        $this->assertStringNotContainsStringIgnoringCase('javascript:', $stored);

        $preview = $this->postJson("/api/admin/email-templates/{$templateId}/preview", [
            'variables' => ['user_name' => '<img src=x onerror=xssMarker()>'],
        ])->assertOk();

        $html = (string) $preview->json('data.html');
        $this->assertStringNotContainsStringIgnoringCase('<img src=x', $html);
        $this->assertStringContainsString('&lt;img', $html);

        $component = file_get_contents(resource_path('js/views/admin/email_templates/Index.vue'));
        $this->assertStringNotContainsString('v-html', $component);
        $this->assertStringContainsString('sandbox=""', $component);
    }

    public function test_security_headers_are_environment_aware(): void
    {
        $response = $this->get('/')->assertOk();

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy');
        $response->assertHeader('Content-Security-Policy');
        $response->assertHeaderMissing('Strict-Transport-Security');
        $this->assertStringContainsString("object-src 'none'", (string) $response->headers->get('Content-Security-Policy'));
    }

    public function test_api_errors_are_json_without_an_accept_header_and_hide_internals(): void
    {
        Route::middleware('api')->get('/api/__test/conflict', fn () => abort(409));
        Route::middleware('api')->get('/api/__test/server-error', function () {
            throw new RuntimeException('SENTINEL_INTERNAL_EXCEPTION');
        });

        $responses = [
            $this->get('/api/v1/missing-route')->assertNotFound(),
            $this->post('/api/v1/categories')->assertStatus(405),
            $this->get('/api/v1/users/profile')->assertUnauthorized(),
            $this->post('/api/v1/users/login')->assertUnprocessable(),
            $this->get('/api/__test/conflict')->assertStatus(409),
            $this->get('/api/__test/server-error')->assertStatus(500),
        ];

        foreach ($responses as $response) {
            $response->assertHeader('content-type', 'application/json');
            $response->assertJsonStructure(['success', 'message', 'data', 'errors']);
            $this->assertStringNotContainsString('SENTINEL_INTERNAL_EXCEPTION', $response->getContent());
            $this->assertStringNotContainsString(base_path(), $response->getContent());
        }
    }

    public function test_admin_login_limiter_normalizes_identity_and_resets_after_decay(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('CorrectPassword1!'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        foreach (['ADMIN@example.com', ' admin@example.com ', 'Admin@Example.Com', 'ADMIN@EXAMPLE.COM', 'admin@example.com'] as $identity) {
            $this->postJson('/api/admin/login', [
                'email' => $identity,
                'password' => 'WrongPassword1!',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/admin/login', [
            'email' => ' ADMIN@EXAMPLE.COM ',
            'password' => 'CorrectPassword1!',
        ])->assertTooManyRequests();

        $this->travel(61)->seconds();

        $this->postJson('/api/admin/login', [
            'email' => ' ADMIN@EXAMPLE.COM ',
            'password' => 'CorrectPassword1!',
        ])->assertOk()->assertJsonMissingPath('data.token');
    }

    public function test_production_browser_sources_contain_no_auth_token_storage_or_query_tokens(): void
    {
        $files = collect(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('js'))))
            ->filter(fn ($file) => $file->isFile())
            ->filter(fn ($file) => in_array($file->getExtension(), ['js', 'vue'], true))
            ->reject(fn ($file) => str_contains(str_replace('\\', '/', $file->getPathname()), '/__tests__/'));
        $source = $files->map(fn ($file) => file_get_contents($file->getPathname()))->implode("\n");

        $this->assertDoesNotMatchRegularExpression('/localStorage\.(?:setItem|getItem)\([^\n]*(?:auth.?token|access.?token)/i', $source);
        $this->assertDoesNotMatchRegularExpression('/[?&]token=/i', $source);
        $this->assertDoesNotMatchRegularExpression('/sk_(?:test|live)_[A-Za-z0-9]+/', $source);
    }
}
