<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Services\CompanyContext;
use App\Traits\NotifiesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SupportTicketController extends Controller
{
    use NotifiesUsers;
    /**
     * Display a listing of the company's tickets.
     */
    public function index(Request $request)
    {
        $companyId = app(CompanyContext::class)->getCompanyId();

        $tickets = SupportTicket::where('company_id', $companyId)
            ->with(['user'])
            ->orderBy('updated_at', 'desc')
            ->paginate($request->per_page ?? 10);

        return response()->json($tickets);
    }

    /**
     * Store a newly created ticket.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'category' => 'nullable|string',
            'attachments.*' => 'nullable|file|max:5120',
        ]);

        $companyId = app(CompanyContext::class)->getCompanyId();

        $ticket = SupportTicket::create([
            'company_id' => $companyId,
            'user_id' => auth()->id(),
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'priority' => $validated['priority'] ?? 'normal',
            'category' => $validated['category'],
            'status' => 'open',
        ]);

        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('tickets/attachments', 'public');
                $attachments[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ];
            }
        }

        // Create the first message automatically from the description
        $ticket->messages()->create([
            'user_id' => auth()->id(),
            'message' => $validated['description'],
            'attachments' => $attachments,
        ]);

        // Notify super admins
        $this->notifySuperAdmins(
            "Nouveau Ticket de Support",
            "Nouveau ticket de " . auth()->user()->first_name . " : " . $ticket->subject,
            "/admin/tickets/{$ticket->id}",
            "danger"
        );

        return response()->json([
            'message' => 'Ticket créé avec succès.',
            'ticket' => $ticket
        ], 201);
    }

    /**
     * Display the specified ticket with conversation.
     */
    public function show(SupportTicket $ticket)
    {
        $companyId = app(CompanyContext::class)->getCompanyId();

        if ($ticket->company_id !== $companyId) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        return response()->json($ticket->load(['messages.user', 'user', 'assignedTo']));
    }

    /**
     * Reply to a ticket.
     */
    public function reply(Request $request, SupportTicket $ticket)
    {
        $companyId = app(CompanyContext::class)->getCompanyId();

        if ($ticket->company_id !== $companyId) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        $validated = $request->validate([
            'message' => 'required|string',
            'attachments.*' => 'nullable|file|max:5120',
        ]);

        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('tickets/attachments', 'public');
                $attachments[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ];
            }
        }

        $message = $ticket->messages()->create([
            'user_id' => auth()->id(),
            'message' => $validated['message'],
            'attachments' => $attachments,
        ]);

        // Update ticket status to open when user replies, unless it's resolved/closed?
        // Actually, if a user replies, it should probably be 'open' or 'pending' wait status.
        if ($ticket->status === 'resolved' || $ticket->status === 'closed') {
            $ticket->status = 'open';
        }
        
        $ticket->last_reply_at = now();
        $ticket->save();

        // Notify super admins
        $this->notifySuperAdmins(
            "Réponse sur Ticket",
            "Nouvelle réponse de " . auth()->user()->first_name . " sur le ticket : " . $ticket->subject,
            "/admin/tickets/{$ticket->id}",
            "info"
        );

        return response()->json([
            'message' => 'Message envoyé avec succès.',
            'reply' => $message->load('user')
        ]);
    }
}
