<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomField;
use Illuminate\Http\Request;

class CustomFieldController extends Controller
{
    public function index(Request $request)
    {
        $fields = CustomField::where('model_type', $request->model_type)
            ->orderBy('sort_order', 'asc')
            ->get();
        return response()->json($fields);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'model_type' => 'required|string',
            'name' => 'required|string',
            'field_type' => 'required|string',
            'options' => 'nullable|array',
            'is_required' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $field = CustomField::create($validated);
        return response()->json($field, 201);
    }
}
