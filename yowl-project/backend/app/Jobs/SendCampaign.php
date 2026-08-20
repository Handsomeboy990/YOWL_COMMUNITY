<?php

namespace App\Jobs;

use App\Mail\CampaignMessage;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\User;
use App\Support\CampaignTemplate;
use App\Support\RichText;
use App\Support\Settings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class SendCampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function __construct(public int $campaignId) {}

    /**
     * Send one campaign, recipient by recipient.
     *
     * Each address is marked as it goes rather than at the end: a run that
     * dies at the four hundredth address must not send the first four hundred
     * a second time when it is picked back up.
     */
    public function handle(): void
    {
        $campaign = Campaign::find($this->campaignId);
        if (! $campaign || $campaign->status === Campaign::STATUS_SENT) {
            return;
        }

        $site = Settings::get('community.name', 'YOWL');
        $envoyes = 0;
        $echecs = 0;

        $restants = CampaignRecipient::where('campaign_id', $campaign->id)
            ->whereNull('sent_at')
            ->with('user')
            ->get();

        foreach ($restants as $destinataire) {
            $membre = $destinataire->user;

            // L'etat a pu changer entre la preparation et l'envoi : quelqu'un
            // se desabonne, ferme son compte, ou est desactive.
            if (! $membre || $membre->email_optout || $membre->anonymized_at || ! $membre->is_active) {
                $destinataire->delete();

                continue;
            }

            try {
                Mail::to($membre->email)->send(new CampaignMessage(
                    $membre,
                    CampaignTemplate::render($campaign->subject, $membre, $site),
                    RichText::clean(CampaignTemplate::render($campaign->body, $membre, $site)),
                    $this->unsubscribeUrl($membre),
                    $site,
                ));

                $destinataire->update(['sent_at' => now(), 'error' => null]);
                $envoyes++;
            } catch (\Throwable $exception) {
                // Une adresse invalide ne doit pas arreter les suivantes.
                $destinataire->update(['error' => mb_substr($exception->getMessage(), 0, 255)]);
                $echecs++;
                Log::warning('Campagne '.$campaign->id.' : envoi impossible', [
                    'user' => $membre->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        $campaign->update([
            'status' => Campaign::STATUS_SENT,
            'sent_count' => $campaign->sent_count + $envoyes,
            'failed_count' => $campaign->failed_count + $echecs,
            'sent_at' => now(),
        ]);
    }

    /**
     * A signed address, so unsubscribing needs no account and no token table.
     */
    private function unsubscribeUrl(User $membre): string
    {
        if (! $membre->email_token) {
            $membre->forceFill(['email_token' => bin2hex(random_bytes(24))])->save();
        }

        return rtrim(config('app.frontend_url', env('FRONTEND_URL', '')), '/')
            .'/desinscription/'.$membre->email_token;
    }
}
