<?php

namespace App\Http\Controllers;

use App\Http\Resources\DocumentResource;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\File;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return DocumentResource::collection(
            resource: Document::query()
                ->whereBelongsTo($user, 'owner')
                ->whereNotNull('expires_at')
                ->whereDate('expires_at', '<=', now()->addDays(7))
                ->get(),
        );
    }

    public function store(Request $request)
    {
        // TODO: Should create a proper Request class for this method.
        $request->validate([
            'document' => ['required', File::types('pdf')->extensions('pdf')->max(20 * 1024)],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);

        $user = $request->user();
        $file = $request->file('document');

        $document = new Document();
        $document->name = $file->getClientOriginalName();
        $document->path = $file->store('documents/'.$user->id);
        $document->owner_id = $user->id;
        $document->expires_at = $request->date('expires_at');
        $document->save();

        return DocumentResource::make($document);
    }

    public function show(Document $document)
    {
        return DocumentResource::make($document);
    }
}
