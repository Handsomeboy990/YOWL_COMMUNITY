<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollVote;
use App\Models\Review;
use App\Support\FeedScore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PollController extends Controller
{
    /**
     * Attach a poll to a review the member owns.
     */
    public function store(Request $request, Review $review)
    {
        if ($review->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Action non autorisée'], 403);
        }

        if ($review->poll) {
            return response()->json([
                'success' => false,
                'message' => 'Cet avis porte déjà un sondage.',
            ], 422);
        }

        $validated = $request->validate([
            'question' => 'required|string|min:5|max:200',
            'options' => 'required|array|min:2|max:4',
            'options.*' => 'required|string|min:1|max:80',
            'closes_at' => 'nullable|date|after:now',
        ]);

        $poll = Poll::create([
            'review_id' => $review->id,
            'question' => $validated['question'],
            'closes_at' => $validated['closes_at'] ?? null,
        ]);

        foreach (array_values($validated['options']) as $position => $label) {
            PollOption::create([
                'poll_id' => $poll->id,
                'label' => $label,
                'position' => $position,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $this->present($poll->fresh('options'), $request->user()?->id),
            'message' => 'Sondage ajouté',
        ], 201);
    }

    /**
     * Cast or change a vote.
     *
     * Changing your mind is allowed while the poll is open: the counters move
     * with the vote, in one transaction, so they cannot drift.
     */
    public function vote(Request $request, Poll $poll)
    {
        if ($poll->isClosed()) {
            return response()->json([
                'success' => false,
                'message' => 'Ce sondage est terminé.',
            ], 422);
        }

        $validated = $request->validate([
            'option_id' => 'required|integer',
        ]);

        $option = $poll->options()->whereKey($validated['option_id'])->first();
        if (! $option) {
            return response()->json([
                'success' => false,
                'message' => 'Cette réponse n\'appartient pas à ce sondage.',
            ], 422);
        }

        DB::transaction(function () use ($poll, $option, $request) {
            $existing = PollVote::where('poll_id', $poll->id)
                ->where('user_id', $request->user()->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                if ($existing->poll_option_id === $option->id) {
                    return;
                }
                PollOption::whereKey($existing->poll_option_id)->decrement('votes');
                $existing->update(['poll_option_id' => $option->id]);
            } else {
                PollVote::create([
                    'poll_id' => $poll->id,
                    'poll_option_id' => $option->id,
                    'user_id' => $request->user()->id,
                ]);
            }

            PollOption::whereKey($option->id)->increment('votes');
        });

        // Voter est un engagement : il compte dans le classement du fil.
        if ($poll->review) {
            FeedScore::refresh($poll->review);
        }

        return response()->json([
            'success' => true,
            'data' => $this->present($poll->fresh('options'), $request->user()->id),
            'message' => 'Vote enregistré',
        ]);
    }

    public function show(Request $request, Poll $poll)
    {
        return response()->json([
            'success' => true,
            'data' => $this->present($poll->load('options'), auth('sanctum')->id()),
            'message' => 'Poll retrieved successfully.',
        ]);
    }

    /**
     * Shape a poll for the client, with the share each option holds.
     *
     * Results are only detailed once the reader has voted or the poll is over:
     * showing them beforehand steers the answer.
     *
     * @return array<string, mixed>
     */
    private function present(Poll $poll, ?int $userId): array
    {
        $total = $poll->totalVotes();
        $mine = $userId
            ? PollVote::where('poll_id', $poll->id)->where('user_id', $userId)->value('poll_option_id')
            : null;
        $reveal = $mine !== null || $poll->isClosed();

        return [
            'id' => $poll->id,
            'question' => $poll->question,
            'closes_at' => $poll->closes_at,
            'closed' => $poll->isClosed(),
            'total_votes' => $total,
            'my_option_id' => $mine,
            'revealed' => $reveal,
            'options' => $poll->options->map(fn (PollOption $option) => [
                'id' => $option->id,
                'label' => $option->label,
                'votes' => $reveal ? $option->votes : null,
                'share' => $reveal && $total > 0 ? round($option->votes * 100 / $total) : null,
            ])->values(),
        ];
    }
}
