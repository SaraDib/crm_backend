<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatMessageController extends Controller
{
    /**
     * Get unique users who have chatted with the current user.
     */
    public function getChats(Request $request)
    {
        $user = $request->user();
        
        $userIds = \App\Models\ChatMessage::where('sender_id', $user->id)
            ->orWhere('receiver_id', $user->id)
            ->get()
            ->flatMap(function ($msg) use ($user) {
                return [$msg->sender_id, $msg->receiver_id];
            })
            ->unique()
            ->filter(fn($id) => $id != $user->id);

        $contacts = \App\Models\User::whereIn('id', $userIds)->get();
        
        // Add last message info
        $contacts->each(function($contact) use ($user) {
            $lastMsg = \App\Models\ChatMessage::where(function($q) use ($user, $contact) {
                $q->where('sender_id', $user->id)->where('receiver_id', $contact->id);
            })->orWhere(function($q) use ($user, $contact) {
                $q->where('sender_id', $contact->id)->where('receiver_id', $user->id);
            })->orderBy('created_at', 'desc')->first();
            
            $contact->last_message = $lastMsg ? $lastMsg->message : '';
            $contact->last_message_time = $lastMsg ? $lastMsg->created_at : null;
        });

        return response()->json($contacts);
    }

    /**
     * Get recent messages with a specific user.
     */
    public function index(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $currentUser = $request->user();
        $otherUserId = $request->user_id;

        // Security check
        if ($currentUser->user_type === 'company') {
            $companyId = $currentUser->companies()->first()?->id;
            $otherUserInCompany = DB::table('company_users')
                ->where('company_id', $companyId)
                ->where('user_id', $otherUserId)
                ->exists();

            if (!$otherUserInCompany) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        } else {
            $otherUser = \App\Models\User::find($otherUserId);
            if ($otherUser->user_type !== 'system') {
                $companyId = app(\App\Services\CompanyContext::class)->getCompanyId();
                if (!$companyId || !DB::table('company_users')->where('company_id', $companyId)->where('user_id', $otherUserId)->exists()) {
                    return response()->json(['message' => 'Unauthorized de système'], 403);
                }
            }
        }

        $messages = \App\Models\ChatMessage::where(function($q) use ($currentUser, $otherUserId) {
                $q->where('sender_id', $currentUser->id)->where('receiver_id', $otherUserId);
            })
            ->orWhere(function($q) use ($currentUser, $otherUserId) {
                $q->where('sender_id', $otherUserId)->where('receiver_id', $currentUser->id);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }

    /**
     * Send a new message.
     */
    public function store(Request $request)
    {
        \Illuminate\Support\Facades\Log::info("Chat store request", $request->all());
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string',
        ]);

        $sender = $request->user();
        $receiverId = $request->receiver_id;
        $companyId = null;

        if ($sender->user_type === 'company') {
            $companyId = $sender->companies()->first()?->id;
            $receiverInSameCompany = DB::table('company_users')
                ->where('company_id', $companyId)
                ->where('user_id', $receiverId)
                ->exists();

            if (!$receiverInSameCompany) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        } else {
            $receiver = \App\Models\User::find($receiverId);
            if ($receiver->user_type === 'system') {
                $companyId = null;
            } else {
                $companyId = app(\App\Services\CompanyContext::class)->getCompanyId();
                if (!$companyId || !DB::table('company_users')->where('company_id', $companyId)->where('user_id', $receiverId)->exists()) {
                    return response()->json(['message' => 'Unauthorized'], 403);
                }
            }
        }

        $message = \App\Models\ChatMessage::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiverId,
            'company_id' => $companyId,
            'message' => $request->message,
        ]);

        return response()->json($message, 201);
    }

    /**
     * Get available contacts to chat with.
     */
    public function getContacts(Request $request)
    {
        $user = $request->user();
        $companyId = null;

        if ($user->user_type === 'company') {
            $companyId = $user->companies()->first()?->id;
        } else {
            // For system users, check if a company context is set (e.g. from middleware or header)
            $companyId = app(\App\Services\CompanyContext::class)->getCompanyId();
            
            // If no company context, default to showing other system users
            if (!$companyId) {
                $contacts = \App\Models\User::where('user_type', 'system')
                    ->where('id', '!=', $user->id)
                    ->get();
                return response()->json($contacts);
            }
        }

        if (!$companyId) {
            return response()->json([]);
        }

        // Show all members of the same company
        $userIds = DB::table('company_users')
            ->where('company_id', $companyId)
            ->where('user_id', '!=', $user->id)
            ->pluck('user_id');

        $contacts = \App\Models\User::whereIn('id', $userIds)->get();

        return response()->json($contacts);
    }

    /**
     * Mark messages from a specific user as read.
     */
    public function markAsRead(Request $request)
    {
        $request->validate([
            'sender_id' => 'required|exists:users,id',
        ]);

        \App\Models\ChatMessage::where('sender_id', $request->sender_id)
            ->where('receiver_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['message' => 'Messages marked as read']);
    }
}
