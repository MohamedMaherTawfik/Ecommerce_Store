<?php

namespace App\Http\Controllers\api\admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactUs\ReplyContactUsRequest;
use App\Models\ContactUs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ContactUsController extends Controller
{
    public function index(Request $request)
    {
        try {
            Log::info('ContactUs index request', [
                'admin_id' => auth('sanctum')->id(),
                'has_search' => $request->filled('search'),
                'status' => $request->input('status'),
            ]);

            $query = ContactUs::query()->with('user');

            if ($request->filled('search')) {
                $search = $request->search;

                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%");
                });
            }

            if ($request->filled('status')) {
                if ($request->status === 'replied') {
                    $query->whereNotNull('replied_at');
                } elseif ($request->status === 'not_replied') {
                    $query->whereNull('replied_at');
                }
            }

            $messages = $query
                ->latest()
                ->paginate($request->integer('per_page', 10));

            return response()->json([
                'success' => true,
                'data' => $messages,
            ]);
        } catch (Throwable $e) {

            Log::error('ContactUs index failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load contact messages.',
            ], 500);
        }
    }

    public function show($id)
    {
        try {

            Log::info('ContactUs show request', [
                'contact_id' => $id,
                'admin_id' => auth('sanctum')->id(),
            ]);

            $message = ContactUs::with([
                'user',
                'replies.admin',
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $message,
            ]);
        } catch (Throwable $e) {

            Log::error('ContactUs show failed', [
                'contact_id' => $id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Contact message not found.',
            ], 500);
        }
    }

    public function reply(ReplyContactUsRequest $request, $id)
    {
        try {

            Log::info('ContactUs reply request', [
                'contact_id' => $id,
                'admin_id' => auth('sanctum')->id(),
                'has_message' => filled($request->validated('message')),
            ]);

            $message = ContactUs::findOrFail($id);

            $reply = $message->replies()->create([
                'admin_id' => auth('sanctum')->id(),
                'message' => $request->message,
            ]);

            if (is_null($message->replied_at)) {
                $message->update([
                    'replied_at' => now(),
                ]);
            }

            Log::info('ContactUs reply created successfully', [
                'reply_id' => $reply->id,
                'contact_id' => $message->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reply sent successfully.',
                'data' => $reply->load('admin'),
            ]);
        } catch (Throwable $e) {

            Log::error('ContactUs reply failed', [
                'contact_id' => $id,
                'admin_id' => auth('sanctum')->id(),
                'exception' => $e::class,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send reply.',
            ], 500);
        }
    }
}
