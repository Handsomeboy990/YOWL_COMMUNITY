<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appeal;
use App\Models\AuditLog;
use App\Models\Comment;
use App\Models\Review;
use App\Notifications\AppealAnswered;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AppealController extends Controller
{
    /** @var array<string, class-string> */
    private const TYPES = ['review' => Review::class, 'comment' => Comment::class];

    /**
     * Contest a moderation decision on your own content.
     *
     * Only the author can appeal, only once, and only for content that was
     * actually taken down: an appeal on something still visible would send a
     * moderator chasing a decision nobody made.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(array_keys(self::TYPES))],
            'id' => 'required|integer|min:1',
            'message' => 'required|string|min:20|max:2000',
        ]);

        $class = self::TYPES[$validated['type']];
        $content = $class::find($validated['id']);

        if (! $content || $content->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Ce contenu ne t\'appartient pas.',
            ], 403);
        }

        if ($content instanceof Review && $content->is_published) {
            return response()->json([
                'success' => false,
                'message' => 'Cet avis est en ligne : il n\'y a rien à contester.',
            ], 422);
        }

        // Un avis programmé n'est pas publié non plus, mais aucune décision
        // n'a été prise à son sujet : il attend son heure.
        if ($content instanceof Review && $content->isScheduled()) {
            return response()->json([
                'success' => false,
                'message' => 'Cet avis est programmé, il sera publié à l\'heure prévue.',
            ], 422);
        }

        $existing = Appeal::where('user_id', $request->user()->id)
            ->where('appealable_type', $class)
            ->where('appealable_id', $content->getKey())
            ->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'data' => $existing,
                'message' => 'Tu as déjà contesté cette décision. Elle est en cours d\'examen.',
            ]);
        }

        $appeal = new Appeal([
            'user_id' => $request->user()->id,
            'message' => $validated['message'],
        ]);
        $appeal->appealable()->associate($content);
        $appeal->save();

        return response()->json([
            'success' => true,
            'data' => $appeal,
            'message' => 'Contestation transmise. Un modérateur va la relire.',
        ], 201);
    }

    /**
     * The appeals a member filed, with the answers they got.
     */
    public function mine(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => Appeal::where('user_id', $request->user()->id)
                ->with('appealable')
                ->orderByDesc('created_at')
                ->paginate(10),
            'message' => 'Appeals retrieved successfully.',
        ]);
    }

    /**
     * The appeals queue, oldest pending first.
     *
     * Oldest first on purpose: somebody waiting on a decision about their own
     * account should not be overtaken by newer arrivals.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(Appeal::STATUSES)],
        ]);

        $query = Appeal::with(['user:id,username,picture', 'handler:id,username', 'appealable'])
            ->when($validated['status'] ?? null, fn ($q, $status) => $q->where('status', $status));

        return response()->json([
            'success' => true,
            'data' => $query->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
                ->orderBy('created_at')
                ->paginate(20),
            'pending_count' => Appeal::where('status', Appeal::STATUS_PENDING)->count(),
            'message' => 'Appeals retrieved successfully.',
        ]);
    }

    /**
     * Answer an appeal, in writing.
     *
     * The written answer is required: "decision maintained" with no reason is
     * exactly what makes people leave.
     */
    public function resolve(Request $request, Appeal $appeal)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([Appeal::STATUS_UPHELD, Appeal::STATUS_GRANTED])],
            'response' => 'required|string|min:10|max:2000',
        ]);

        $appeal->update([
            'status' => $validated['status'],
            'response' => $validated['response'],
            'handled_by' => $request->user()->id,
            'handled_at' => now(),
        ]);

        // Accepter le recours remet le contenu en ligne dans le meme geste.
        if ($validated['status'] === Appeal::STATUS_GRANTED) {
            $content = $appeal->appealable;
            if ($content instanceof Review) {
                $content->forceFill(['is_published' => true])->save();
            }
        }

        if ($appeal->user) {
            $appeal->user->notify(new AppealAnswered($request->user(), $appeal->fresh()));
        }

        AuditLog::record('appeal.resolved', $appeal, ['status' => $validated['status']], $request);

        return response()->json([
            'success' => true,
            'data' => $appeal->fresh(['user:id,username', 'handler:id,username']),
            'message' => 'Contestation traitée. Le membre a été prévenu.',
        ]);
    }
}
