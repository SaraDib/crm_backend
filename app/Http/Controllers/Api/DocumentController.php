<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $documents = Document::where('documentable_id', $request->documentable_id)
            ->where('documentable_type', $request->documentable_type)
            ->with('user')
            ->get();

        return response()->json($documents);
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'documentable_id' => 'required',
            'documentable_type' => 'required',
            'name' => 'nullable|string',
            'category' => 'nullable|string',
        ]);

        $file = $request->file('file');
        $path = $file->store('documents/' . $request->user()->companies()->first()->id, 'public');

        $document = Document::create([
            'documentable_id' => $request->documentable_id,
            'documentable_type' => $request->documentable_type,
            'name' => $request->name ?: $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $file->getClientOriginalExtension(),
            'file_size' => $file->getSize(),
            'user_id' => $request->user()->id,
            'category' => $request->category,
        ]);

        return response()->json([
            'message' => 'Document ajouté avec succès.',
            'document' => $document->load('user')
        ], 201);
    }

    public function destroy(Document $document)
    {
        Storage::disk('public')->delete($document->file_path);
        $document->delete();
        return response()->json(['message' => 'Document supprimé.']);
    }
}
