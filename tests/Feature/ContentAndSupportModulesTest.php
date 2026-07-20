<?php

namespace Tests\Feature;

use App\Mail\TemplateMail;
use App\Models\BlogPost;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContentAndSupportModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_and_admin_can_complete_ticket_workflow_with_ownership_enforced(): void
    {
        $customer = User::factory()->create(['role' => 'user']);
        $other = User::factory()->create(['role' => 'user']);
        $admin = User::factory()->create(['role' => 'admin']);

        Sanctum::actingAs($customer);
        $ticketId = $this->api()->postJson('/api/v1/support/tickets', [
            'subject' => 'Order help',
            'message' => 'Where is my order?',
            'priority' => 'high',
        ])->assertOk()->json('data.id');

        Sanctum::actingAs($other);
        $this->api()->getJson("/api/v1/support/tickets/{$ticketId}")->assertNotFound();

        Sanctum::actingAs($admin);
        $this->api()->postJson("/api/admin/tickets/{$ticketId}/reply", ['message' => 'We are checking it.'])
            ->assertOk();
        $this->api()->patchJson("/api/admin/tickets/{$ticketId}", ['status' => 'closed', 'priority' => 'urgent'])
            ->assertOk();

        Sanctum::actingAs($customer);
        $this->api()->patchJson("/api/v1/support/tickets/{$ticketId}/status", ['status' => 'open'])
            ->assertOk();
        $this->assertDatabaseHas('ticket_messages', ['ticket_id' => $ticketId, 'is_admin' => true]);
    }

    public function test_blog_crud_publish_search_and_taxonomies_work(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);
        $categoryId = $this->api()->postJson('/api/admin/blog/categories', ['name' => 'Guides'])
            ->assertOk()->json('data.id');
        $tagId = $this->api()->postJson('/api/admin/blog/tags', ['name' => 'Laravel'])
            ->assertOk()->json('data.id');

        $postId = $this->api()->postJson('/api/admin/blog/posts', [
            'title' => 'Production Guide',
            'excerpt' => 'A practical guide',
            'content' => '<p>Safe content</p><script>alert(1)</script>',
            'blog_category_id' => $categoryId,
            'tag_ids' => [$tagId],
            'status' => 'published',
            'meta_title' => 'Production Guide SEO',
            'meta_description' => 'Production guide meta description.',
        ])->assertOk()->json('data.id');

        $this->assertDatabaseMissing('blog_posts', ['id' => $postId, 'content' => '<p>Safe content</p><script>alert(1)</script>']);
        $slug = BlogPost::find($postId)->slug;

        Sanctum::actingAs(User::factory()->create(['role' => 'user']));
        $this->api()->getJson('/api/v1/blog?search=Production')->assertOk()->assertJsonPath('data.total', 1);
        $this->api()->getJson("/api/v1/blog/{$slug}")->assertOk()->assertJsonPath('data.meta_title', 'Production Guide SEO');
    }

    public function test_email_templates_support_crud_preview_and_test_send(): void
    {
        Mail::fake();
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
        $template = EmailTemplate::where('key', 'welcome')->firstOrFail();

        $this->api()->postJson("/api/admin/email-templates/{$template->id}/preview", [
            'variables' => ['user_name' => 'Buyer', 'user_email' => 'buyer@example.com'],
        ])->assertOk()->assertJsonPath('data.subject', 'Welcome to Laravel');

        $this->api()->postJson("/api/admin/email-templates/{$template->id}/test-send", [
            'email' => 'qa@example.com',
            'variables' => ['user_name' => 'QA'],
        ])->assertOk();

        Mail::assertQueued(TemplateMail::class);
    }
}
