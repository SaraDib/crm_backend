<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Traits\NotifiesUsers;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    use NotifiesUsers;
    /**
     * Display a listing of tickets for admin.
     */
    public function index(Request $request)
    {
        $tickets = SupportTicket::with(['company', 'user'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->priority, fn($q) => $q->where('priority', $request->priority))
            ->orderBy('updated_at', 'desc')
            ->paginate($request->per_page ?? 10);

        return response()->json($tickets);
    }

    /**
     * Display the specified ticket with conversation.
     */
    public function show(SupportTicket $ticket)
    {
        return response()->json($ticket->load(['company', 'user', 'messages.user', 'assignedTo']));
    }

    /**
     * Update ticket status or assignment.
     */
    public function update(Request $request, SupportTicket $ticket)
    {
        $validated = $request->validate([
            'status' => 'nullable|in:open,pending,resolved,closed',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $ticket->update(array_filter($validated));

        return response()->json([
            'message' => 'Ticket mis à jour avec succès.',
            'ticket' => $ticket->load(['company', 'user', 'assignedTo', 'messages.user'])
        ]);
    }

    /**
     * Reply to a ticket.
     */
    public function reply(Request $request, SupportTicket $ticket)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'message' => 'required_without:attachments|nullable|string',
            'is_internal' => 'nullable',
            'attachments.*' => 'nullable|file|max:5120',
        ]);

        if ($validator->fails()) {
            \Illuminate\Support\Facades\Log::error('Validation Ticket Reply Failed', [
                'errors' => $validator->errors()->toArray(),
                'input' => $request->all()
            ]);
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

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
            'user_id' => $request->user()->id,
            'message' => $validated['message'],
            'is_internal' => $validated['is_internal'] ?? false,
            'attachments' => $attachments,
        ]);

        // Update ticket status to pending if admin replies
        if (!$validated['is_internal']) {
            $ticket->update([
                'status' => 'pending',
                'last_reply_at' => now(),
            ]);

            // Notify company staff
            $this->notifyCompanyStaff(
                $ticket->company_id,
                "Réponse au Support",
                "L'assistance a répondu à votre ticket : " . $ticket->subject,
                "/support/tickets/{$ticket->id}",
                "info"
            );
        }

        return response()->json([
            'message' => 'Réponse envoyée avec succès.',
            'reply' => $message->load('user')
        ]);
    }
}
