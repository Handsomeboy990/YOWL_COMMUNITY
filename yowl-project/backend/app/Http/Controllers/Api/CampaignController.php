<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendCampaign;
use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\User;
use App\Support\CampaignTemplate;
use App\Support\RichText;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CampaignController extends Controller
{
    /**
     * Campaigns already written, newest first.
     */
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => Campaign::with('author:id,username')
                ->orderByDesc('created_at')
                ->paginate(15),
            'message' => 'Campaigns retrieved successfully.',
        ]);
    }

    /**
     * What the console needs to compose: templates, segments, placeholders.
     */
    public function options()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'purposes' => CampaignTemplate::PURPOSES,
                'templates' => CampaignTemplate::all(),
                'segments' => Campaign::SEGMENTS,
                'placeholders' => CampaignTemplate::PLACEHOLDERS,
                'reachable' => $this->reachable()->count(),
                'opted_out' => User::where('email_optout', true)->count(),
            ],
            'message' => 'Options retrieved successfully.',
        ]);
    }

    /**
     * How many members a given audience actually reaches.
     *
     * Called while composing, so nobody discovers after the fact that their
     * segment matched four people or eleven thousand.
     */
    public function audience(Request $request)
    {
        $validated = $request->validate([
            'audience' => ['required', Rule::in(Campaign::AUDIENCES)],
            'segment' => ['nullable', Rule::in(array_keys(Campaign::SEGMENTS))],
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer',
        ]);

        return response()->json([
            'success' => true,
            'data' => ['count' => $this->recipientQuery($validated)->count()],
            'message' => 'Audience computed successfully.',
        ]);
    }

    /**
     * Save a campaign as a draft.
     */
    public function store(Request $request)
    {
        $validated = $this->validated($request);
        $validated['body'] = RichText::clean($validated['body']);
        $validated['created_by'] = $request->user()->id;
        $validated['status'] = Campaign::STATUS_DRAFT;

        $campaign = Campaign::create($validated);
        AuditLog::record('campaign.created', $campaign, ['subject' => $campaign->subject], $request);

        return response()->json([
            'success' => true,
            'data' => $campaign,
            'message' => 'Campagne enregistrée en brouillon.',
        ], 201);
    }

    public function update(Request $request, Campaign $campaign)
    {
        if ($campaign->status !== Campaign::STATUS_DRAFT) {
            return response()->json([
                'success' => false,
                'message' => 'Une campagne envoyée ne se modifie plus.',
            ], 422);
        }

        $validated = $this->validated($request);
        $validated['body'] = RichText::clean($validated['body']);
        $campaign->update($validated);

        return response()->json([
            'success' => true,
            'data' => $campaign->fresh(),
            'message' => 'Campagne mise à jour.',
        ]);
    }

    /**
     * Send the campaign to yourself, before sending it to anybody else.
     */
    public function test(Request $request, Campaign $campaign)
    {
        $site = Settings::get('community.name', 'YOWL');
        $moi = $request->user();

        \Illuminate\Support\Facades\Mail::to($moi->email)->send(new \App\Mail\CampaignMessage(
            $moi,
            '[Test] '.CampaignTemplate::render($campaign->subject, $moi, $site),
            RichText::clean(CampaignTemplate::render($campaign->body, $moi, $site)),
            rtrim(config('app.frontend_url'), '/').'/desinscription/test',
            $site,
        ));

        return response()->json([
            'success' => true,
            'message' => 'Test envoyé à '.$moi->email.'.',
        ]);
    }

    /**
     * Freeze the recipient list, then queue the sending.
     *
     * The list is written before the queue starts rather than resolved inside
     * the job: an audience computed at send time would drift while the run is
     * in progress, and nobody could say afterwards who was actually written to.
     */
    public function send(Request $request, Campaign $campaign)
    {
        if ($campaign->status !== Campaign::STATUS_DRAFT) {
            return response()->json([
                'success' => false,
                'message' => 'Cette campagne a déjà été envoyée.',
            ], 422);
        }

        $destinataires = $this->recipientQuery([
            'audience' => $campaign->audience,
            'segment' => $campaign->segment,
            'user_ids' => $campaign->user_ids,
        ])->pluck('id');

        if ($destinataires->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cette sélection ne touche personne.',
            ], 422);
        }

        foreach ($destinataires->chunk(500) as $lot) {
            CampaignRecipient::insertOrIgnore(
                $lot->map(fn ($id) => ['campaign_id' => $campaign->id, 'user_id' => $id])->all()
            );
        }

        $campaign->update([
            'status' => Campaign::STATUS_SENDING,
            'recipients_count' => $destinataires->count(),
        ]);

        SendCampaign::dispatch($campaign->id);

        AuditLog::record('campaign.sent', $campaign, [
            'recipients' => $destinataires->count(),
            'subject' => $campaign->subject,
        ], $request);

        return response()->json([
            'success' => true,
            'data' => $campaign->fresh(),
            'message' => 'Envoi lancé vers '.$destinataires->count().' membre(s).',
        ]);
    }

    public function destroy(Campaign $campaign)
    {
        if ($campaign->status === Campaign::STATUS_SENDING) {
            return response()->json([
                'success' => false,
                'message' => 'Un envoi en cours ne se supprime pas.',
            ], 422);
        }

        $campaign->delete();

        return response()->json(['success' => true, 'message' => 'Campagne supprimée.']);
    }

    /**
     * Stop receiving campaigns, without signing in.
     *
     * The link lives in every campaign email, so it has to work for somebody
     * who no longer remembers having an account.
     */
    public function unsubscribe(string $token)
    {
        $membre = User::where('email_token', $token)->first();

        if (! $membre) {
            return response()->json([
                'success' => false,
                'message' => 'Ce lien de désinscription n\'est plus valable.',
            ], 404);
        }

        $membre->forceFill(['email_optout' => true])->save();

        return response()->json([
            'success' => true,
            'message' => 'C\'est fait. Tu ne recevras plus d\'email de ce type.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'subject' => 'required|string|max:150',
            'body' => 'required|string|max:50000',
            'purpose' => ['required', Rule::in(array_keys(CampaignTemplate::PURPOSES))],
            'audience' => ['required', Rule::in(Campaign::AUDIENCES)],
            'segment' => ['nullable', 'required_if:audience,segment', Rule::in(array_keys(Campaign::SEGMENTS))],
            'user_ids' => 'nullable|required_if:audience,selected|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
        ]);
    }

    /**
     * Members who can be written to at all: active, not anonymised, not opted out.
     */
    private function reachable()
    {
        return User::query()
            ->whereNull('anonymized_at')
            ->where('is_active', true)
            ->where('email_optout', false)
            ->whereNotNull('email');
    }

    /**
     * @param  array<string, mixed>  $criteres
     */
    private function recipientQuery(array $criteres)
    {
        $query = $this->reachable();

        if (($criteres['audience'] ?? 'all') === 'selected') {
            return $query->whereIn('id', $criteres['user_ids'] ?? []);
        }

        if (($criteres['audience'] ?? 'all') === 'segment') {
            return match ($criteres['segment'] ?? null) {
                'active_30' => $query->where(fn ($q) => $q
                    ->whereHas('reviews', fn ($r) => $r->where('created_at', '>=', now()->subDays(30)))
                    ->orWhereHas('comments', fn ($c) => $c->where('created_at', '>=', now()->subDays(30)))),
                'never_published' => $query->whereDoesntHave('reviews'),
                'authors' => $query->whereHas('reviews'),
                'digest' => $query->where('digest_optin', true),
                'newcomers' => $query->where('created_at', '>=', now()->subDays(30)),
                default => $query,
            };
        }

        return $query;
    }
}
