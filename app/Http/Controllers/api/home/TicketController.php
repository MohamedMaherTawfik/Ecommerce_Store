<?php

namespace App\Http\Controllers\api\home;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketReplyNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $tickets = Ticket::query()
            ->where('user_id', $request->user()->id)
            ->withCount('messages')
            ->latest('last_reply_at')
            ->paginate(15);

        return $this->success($tickets, 'Tickets loaded.');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:10000'],
            'priority' => ['sometimes', 'string', 'in:low,normal,high,urgent'],
        ]);

        $ticket = DB::transaction(function () use ($request, $data) {
            $ticket = Ticket::create([
                'ticket_number' => 'TKT-'.now()->format('ymd').'-'.Str::upper(Str::random(8)),
                'user_id' => $request->user()->id,
                'subject' => $data['subject'],
                'priority' => $data['priority'] ?? 'normal',
                'status' => 'open',
                'last_reply_at' => now(),
            ]);

            $message = $ticket->messages()->create([
                'user_id' => $request->user()->id,
                'message' => $data['message'],
                'is_admin' => false,
            ]);

            Notification::send($this->supportAgents(), new TicketReplyNotification($ticket, $message));

            return $ticket;
        });

        return $this->success($ticket->load('messages.user'), 'Ticket created.');
    }

    public function show(Request $request, int $id)
    {
        $ticket = Ticket::with(['messages.user', 'assignee'])
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return $this->success($ticket, 'Ticket loaded.');
    }

    public function reply(Request $request, int $id)
    {
        $data = $request->validate(['message' => ['required', 'string', 'max:10000']]);
        $ticket = Ticket::where('user_id', $request->user()->id)->findOrFail($id);

        if ($ticket->status === 'closed') {
            return $this->error('Reopen the ticket before replying.', 422);
        }

        $message = DB::transaction(function () use ($request, $ticket, $data) {
            $message = $ticket->messages()->create([
                'user_id' => $request->user()->id,
                'message' => $data['message'],
                'is_admin' => false,
            ]);
            $ticket->update(['status' => 'customer_reply', 'last_reply_at' => now()]);

            Notification::send($this->supportAgents(), new TicketReplyNotification($ticket, $message));

            return $message;
        });

        return $this->success($message->load('user'), 'Reply sent.');
    }

    public function updateStatus(Request $request, int $id)
    {
        $data = $request->validate(['status' => ['required', 'in:closed,open']]);
        $ticket = Ticket::where('user_id', $request->user()->id)->findOrFail($id);
        $ticket->update([
            'status' => $data['status'],
            'closed_at' => $data['status'] === 'closed' ? now() : null,
        ]);

        return $this->success($ticket, $data['status'] === 'closed' ? 'Ticket closed.' : 'Ticket reopened.');
    }

    private function supportAgents()
    {
        $roles = DB::table('role_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('permissions.name', 'tickets.view')
            ->pluck('role_permissions.role');

        return User::where('is_active', true)
            ->where(fn ($query) => $query->where('role', 'admin')->orWhereIn('role', $roles))
            ->get();
    }
}
