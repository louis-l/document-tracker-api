<?php

namespace App\Http\Controllers;

use App\Http\Resources\DocumentResource;
use App\Models\Document;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'filter' => ['nullable', 'string', Rule::in(['all', 'expiring_soon', 'already_expired'])],
        ]);

        $user = $request->user();
        $documentFilter = $request->input('filter');

        return DocumentResource::collection(
            resource: Document::query()
                ->whereBelongsTo($user, 'owner')
                ->when($documentFilter === 'expiring_soon', fn (Builder $query) => $query->whereBetween('expires_at', [now(), now()->addDays(7)]))
                ->when($documentFilter === 'already_expired', fn (Builder $query) => $query->whereDate('expires_at', '<=', now()))
                ->whereNull('archived_at')
                ->orderByDesc('id')
                ->paginate($request->integer('per_page', 10)),
        );
    }

    public function store(Request $request)
    {
        // TODO: Should create a proper Request class for this method.
        $request->validate([
            'document' => ['required', File::types('pdf')->extensions('pdf')->max(20 * 1024)],
            'expires_at' => ['nullable', 'date', 'after:today'],
            // The regex is to enforce only safe characters
            'custom_file_name' => ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_\-. ]+$/'],
        ]);

        $user = $request->user();
        $file = $request->file('document');

        $fileNameWithExtension = $file->getClientOriginalName();
        // Use custom name (with .pdf extension) if provided
        if ($customFileName = $request->string('custom_file_name')) {
            // Make sure the file name ends with extension
            if (! $customFileName->endsWith('.pdf')) {
                $customFileName = $customFileName->append('.pdf');
            }

            $fileNameWithExtension = $customFileName->toString();

            // TODO: Should do extra sanitization like: non-ASCII chars, path traversal/overwriting...
        }

        $document = new Document();
        $document->name = $fileNameWithExtension;
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

    public function archive(Document $document, Request $request)
    {
        $user = $request->user();

        if ($document->owner_id !== $user->id) {
            abort(404, 'Document not found.');
        }

        if ($document->archived_at) {
            abort(403, 'Document is already archived.');
        }

        $document->archived_at = now();
        $document->save();

        return response()->noContent();
    }
}
