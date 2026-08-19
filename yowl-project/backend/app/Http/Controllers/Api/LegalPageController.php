<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\LegalPage;
use App\Support\Media;
use App\Support\RichText;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LegalPageController extends Controller
{
    /**
     * The published body, for the public page.
     */
    public function show(string $slug)
    {
        $page = LegalPage::where('slug', $slug)->first();

        if (! $page || ! $page->published_at) {
            return response()->json([
                'success' => false,
                'message' => 'Cette page n\'est pas encore publiée.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'slug' => $page->slug,
                'title' => $page->title,
                'body' => $page->body,
                'updated_at' => $page->published_at,
            ],
            'message' => 'Legal page retrieved successfully.',
        ]);
    }

    /**
     * Every page with its state, for the console listing.
     */
    public function index()
    {
        $pages = collect(LegalPage::SLUGS)->map(function ($title, $slug) {
            $page = LegalPage::where('slug', $slug)->first();

            return [
                'slug' => $slug,
                'title' => $page?->title ?? $title,
                'published' => (bool) $page?->published_at,
                'has_draft' => (bool) $page?->hasUnpublishedDraft(),
                'updated_at' => $page?->updated_at,
                'editor' => $page?->editor?->username,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $pages,
            'message' => 'Legal pages retrieved successfully.',
        ]);
    }

    /**
     * The full content an administrator edits: the draft if there is one,
     * the published body otherwise.
     */
    public function edit(string $slug)
    {
        abort_unless(array_key_exists($slug, LegalPage::SLUGS), 404);

        $page = LegalPage::firstOrCreate(
            ['slug' => $slug],
            ['title' => LegalPage::SLUGS[$slug]]
        );

        return response()->json([
            'success' => true,
            'data' => [
                'slug' => $page->slug,
                'title' => $page->title,
                'body' => $page->body,
                'draft_body' => $page->draft_body,
                'content' => $page->draft_body ?? $page->body ?? '',
                'published' => (bool) $page->published_at,
                'has_draft' => $page->hasUnpublishedDraft(),
                'published_at' => $page->published_at,
            ],
            'message' => 'Legal page retrieved successfully.',
        ]);
    }

    /**
     * Save a draft, or publish.
     *
     * Publishing copies the draft over the public body in one move, so a page
     * is never half updated for the people reading it.
     */
    public function update(Request $request, string $slug)
    {
        abort_unless(array_key_exists($slug, LegalPage::SLUGS), 404);

        $validated = $request->validate([
            'title' => 'required|string|max:160',
            'content' => 'nullable|string|max:200000',
            'action' => ['required', Rule::in(['draft', 'publish'])],
        ]);

        $page = LegalPage::firstOrCreate(['slug' => $slug], ['title' => LegalPage::SLUGS[$slug]]);

        // Le contenu vient d'un editeur riche : il est reconstruit a partir
        // d'une liste blanche avant d'atteindre la base.
        $clean = RichText::clean($validated['content'] ?? '');

        $page->title = $validated['title'];
        $page->draft_body = $clean;
        $page->updated_by = $request->user()->id;

        if ($validated['action'] === 'publish') {
            $page->body = $clean;
            $page->published_at = now();
        }

        $page->save();

        AuditLog::record(
            $validated['action'] === 'publish' ? 'legal.published' : 'legal.drafted',
            $page,
            ['slug' => $slug],
            $request
        );

        return response()->json([
            'success' => true,
            'data' => [
                'slug' => $page->slug,
                'has_draft' => $page->hasUnpublishedDraft(),
                'published' => (bool) $page->published_at,
            ],
            'message' => $validated['action'] === 'publish'
                ? 'Page publiée. Elle est en ligne.'
                : 'Brouillon enregistré. Rien n\'a changé pour les visiteurs.',
        ]);
    }

    /**
     * Throw away the draft and go back to what is published.
     */
    public function discardDraft(Request $request, string $slug)
    {
        $page = LegalPage::where('slug', $slug)->firstOrFail();
        $page->draft_body = null;
        $page->save();

        return response()->json([
            'success' => true,
            'message' => 'Brouillon abandonné.',
        ]);
    }

    /**
     * Upload an image used inside a legal page.
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|file|mimes:jpeg,jpg,png,webp,gif|max:4096',
        ]);

        $path = Media::store($request->file('image'), 'legal');

        return response()->json([
            'success' => true,
            'data' => ['path' => $path, 'url' => Media::url($path)],
            'message' => 'Image envoyée',
        ], 201);
    }
}
