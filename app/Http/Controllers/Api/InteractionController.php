<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Interaction;
use Illuminate\Http\Request;

class InteractionController extends Controller
{
    public function index(Request $request)
    {
        $interactions = Interaction::with('user')
            ->where('customer_id', $request->customer_id)
            ->orderBy('interaction_date', 'desc')
            ->get();

        return response()->json($interactions);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'type' => 'required|string',
            'subject' => 'nullable|string|max:255',
            'content' => 'required|string',
            'interaction_date' => 'required|date',
            'status' => 'nullable|string',
        ]);

        $validated['user_id'] = $request->user()->id;

        $interaction = Interaction::create($validated);

        return response()->json([
            'message' => 'Interaction enregistrée.',
            'interaction' => $interaction->load('user')
        ], 201);
    }
}
