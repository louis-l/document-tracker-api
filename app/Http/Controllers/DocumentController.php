<?php

namespace App\Http\Controllers;

use App\Http\Resources\DocumentResource;
use App\Models\Document;
use Illuminate\Http\Request;

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
        $document = $request
            ->user()
            ->documents()
            ->create(
                $request->all()
            );

        return DocumentResource::make($document);
    }

    public function show(Document $document)
    {
        return DocumentResource::make($document);
    }
}
