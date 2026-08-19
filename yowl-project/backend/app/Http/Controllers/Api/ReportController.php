<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Report;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    /**
     * The content types a member is allowed to report.
     *
     * @var array<string, class-string>
     */
    private const REPORTABLE_TYPES = [
        'review' => Review::class,
        'comment' => Comment::class,
    ];

    /**
     * File a report on a review or a comment.
     *
     * A member reports a given piece of content once: a second attempt returns
     * the existing report rather than creating a duplicate.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(array_keys(self::REPORTABLE_TYPES))],
            'id' => 'required|integer|min:1',
            'reason' => ['required', Rule::in(Report::REASONS)],
            'details' => 'nullable|string|max:1000',
        ]);

        $modelClass = self::REPORTABLE_TYPES[$validated['type']];
        $reportable = $modelClass::find($validated['id']);
        if (! $reportable) {
            return response()->json([
                'success' => false,
                'message' => 'Contenu introuvable.',
            ], 404);
        }

        $user = $request->user();
        if ($reportable->user_id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Tu ne peux pas signaler ton propre contenu.',
            ], 422);
        }

        $existing = Report::where('user_id', $user->id)
            ->where('reportable_type', $modelClass)
            ->where('reportable_id', $reportable->id)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'data' => $existing,
                'message' => 'Ce contenu a déjà été signalé par tes soins.',
            ], 200);
        }

        $report = new Report([
            'user_id' => $user->id,
            'reason' => $validated['reason'],
            'details' => $validated['details'] ?? null,
        ]);
        $report->reportable()->associate($reportable);
        $report->save();

        $hidden = \App\Support\AutoModeration::evaluate($report);

        return response()->json([
            'success' => true,
            'data' => $report,
            'auto_hidden' => $hidden,
            'message' => $hidden
                ? 'Signalement transmis. Le contenu a été retiré du fil en attendant une décision.'
                : 'Signalement transmis à la modération.',
        ], 201);
    }
}
