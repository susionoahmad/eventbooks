<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DocumentController extends Controller
{
    public function index(Event $event): JsonResponse
    {
        Gate::authorize('view', $event);

        $documents = $event->documents()->with('uploader')->orderBy('created_at', 'desc')->get();

        return response()->json([
            'items' => DocumentResource::collection($documents)
        ]);
    }

    public function store(Request $request, Event $event): JsonResponse
    {
        Gate::authorize('update', $event);

        $request->validate([
            'nama_dokumen' => 'required|string|max:255',
            'tipe_dokumen' => ['required', Rule::in(['kontrak', 'invoice', 'kwitansi', 'faktur_pajak', 'bukti_transfer'])],
            'file' => 'required|file|max:10240', // max 10MB
        ]);

        $file = $request->file('file');
        $filename = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('documents/' . $event->id, $filename, 'public');

        $document = $event->documents()->create([
            'nama_dokumen' => $request->nama_dokumen,
            'tipe_dokumen' => $request->tipe_dokumen,
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'file_type' => $file->getClientMimeType(),
            'uploaded_by' => Auth::id()
        ]);

        return response()->json([
            'message' => 'Document uploaded successfully',
            'data' => new DocumentResource($document->load('uploader'))
        ], 201);
    }

    public function download(Event $event, Document $document)
    {
        Gate::authorize('view', $event);

        if ($document->event_id !== $event->id) {
            return response()->json(['message' => 'Document does not belong to this event'], 400);
        }

        if (!Storage::disk('public')->exists($document->file_path)) {
            return response()->json(['message' => 'File not found on storage'], 404);
        }

        return Storage::disk('public')->download($document->file_path, $document->nama_dokumen);
    }

    public function destroy(Event $event, Document $document): JsonResponse
    {
        Gate::authorize('update', $event);

        if ($document->event_id !== $event->id) {
            return response()->json(['message' => 'Document does not belong to this event'], 400);
        }

        // Delete from storage
        if (Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return response()->json([
            'message' => 'Document deleted successfully'
        ], 200);
    }
}
