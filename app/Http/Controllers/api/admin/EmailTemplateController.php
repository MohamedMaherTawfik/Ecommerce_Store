<?php

namespace App\Http\Controllers\api\admin;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Mail\TemplateMail;
use App\Models\EmailTemplate;
use App\Services\Email\EmailTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class EmailTemplateController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly EmailTemplateService $templates) {}

    public function index()
    {
        return $this->success(EmailTemplate::orderBy('name')->get(), 'Email templates loaded.');
    }

    public function show(int $id)
    {
        return $this->success(EmailTemplate::findOrFail($id), 'Email template loaded.');
    }

    public function store(Request $request)
    {
        $template = EmailTemplate::create($this->data($request));
        $this->templates->clear($template->key);

        return $this->success($template, 'Email template created.');
    }

    public function update(Request $request, int $id)
    {
        $template = EmailTemplate::findOrFail($id);
        $oldKey = $template->key;
        $template->update($this->data($request, $template));
        $this->templates->clear($oldKey);
        $this->templates->clear($template->key);

        return $this->success($template, 'Email template updated.');
    }

    public function destroy(int $id)
    {
        $template = EmailTemplate::findOrFail($id);
        $key = $template->key;
        $template->delete();
        $this->templates->clear($key);

        return $this->success([], 'Email template deleted.');
    }

    public function preview(Request $request, int $id)
    {
        $template = EmailTemplate::findOrFail($id);
        $variables = $request->validate(['variables' => ['sometimes', 'array']])['variables'] ?? [];
        $rendered = $this->templates->render($template->key, $variables, $template->subject, $template->html_body);

        return $this->success($rendered, 'Email preview generated.');
    }

    public function testSend(Request $request, int $id)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'variables' => ['sometimes', 'array'],
        ]);
        $template = EmailTemplate::findOrFail($id);
        $rendered = $this->templates->render(
            $template->key,
            $data['variables'] ?? [],
            $template->subject,
            $template->html_body
        );
        Mail::to($data['email'])->queue(new TemplateMail($rendered['subject'], $rendered['html']));

        return $this->success([], 'Test email queued.');
    }

    private function data(Request $request, ?EmailTemplate $template = null): array
    {
        return $request->validate([
            'key' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/', Rule::unique('email_templates', 'key')->ignore($template?->id)],
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'html_body' => ['required', 'string'],
            'text_body' => ['nullable', 'string'],
            'variables' => ['nullable', 'array'],
            'variables.*' => ['string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
