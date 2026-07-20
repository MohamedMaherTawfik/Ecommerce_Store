<?php

namespace App\Http\Controllers\api\admin;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Notifications\TicketReplyNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:open,pending,admin_reply,customer_reply,closed'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $tickets = Ticket::query()
            ->with(['user:id,name,email', 'assignee:id,name'])
            ->withCount('messages')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('ticket_number', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn ($query, $value) => $query->where('status', $value))
            ->when($filters['priority'] ?? null, fn ($query, $value) => $query->where('priority', $value))
            ->when($filters['assigned_to'] ?? null, fn ($query, $value) => $query->where('assigned_to', $value))
            ->latest('last_reply_at')
            ->paginate($filters['per_page'] ?? 20);

        return $this->success($tickets, 'Tickets loaded.');
    }

    public function show(int $id)
    {
        return $this->success(
            Ticket::with(['user:id,name,email', 'assignee:id,name,email', 'messages.user:id,name,email,role'])->findOrFail($id),
            'Ticket loaded.'
        );
    }

    public function reply(Request $request, int $id)
    {
        $data = $request->validate(['message' => ['required', 'string', 'max:10000']]);
        $ticket = Ticket::with('user')->findOrFail($id);

        if ($ticket->status === 'closed') {
            return $this->error('Reopen the ticket before replying.', 422);
        }

        $message = DB::transaction(function () use ($request, $ticket, $data) {
            $message = $ticket->messages()->create([
                'user_id' => $request->user()->id,
                'message' => $data['message'],
                'is_admin' => true,
            ]);
            $ticket->update([
                'status' => 'admin_reply',
                'assigned_to' => $ticket->assigned_to ?: $request->user()->id,
                'last_reply_at' => now(),
            ]);
            $ticket->user->notify(new TicketReplyNotification($ticket, $message));

            return $message;
        });

        return $this->success($message->load('user'), 'Reply sent.');
    }

    public function update(Request $request, int $id)
    {
        $data = $request->validate([
            'status' => ['sometimes', 'in:open,pending,admin_reply,customer_reply,closed'],
            'priority' => ['sometimes', 'in:low,normal,high,urgent'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $ticket = Ticket::findOrFail($id);
        if (array_key_exists('status', $data)) {
            $data['closed_at'] = $data['status'] === 'closed' ? now() : null;
        }
        $ticket->update($data);

        return $this->success($ticket->fresh(['user', 'assignee']), 'Ticket updated.');
    }
}
