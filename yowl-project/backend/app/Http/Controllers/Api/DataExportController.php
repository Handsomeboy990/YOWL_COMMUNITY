<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bookmark;
use App\Models\Follow;
use App\Models\Report;
use App\Models\Suggestion;
use Illuminate\Http\Request;

class DataExportController extends Controller
{
    /**
     * Everything the platform holds about a member, as one file.
     *
     * Deleting an account was the only right implemented, and portability is
     * the other half: somebody must be able to leave with what they wrote,
     * not only erase it. The file is built on the spot rather than queued,
     * an individual account holding at most a few thousand rows.
     */
    public function export(Request $request)
    {
        $user = $request->user();

        $donnees = [
            'exporte_le' => now()->toIso8601String(),
            'compte' => [
                'identifiant' => $user->id,
                'pseudo' => $user->username,
                'nom' => $user->fullname,
                'email' => $user->email,
                'date_de_naissance' => $user->birthdate?->format('Y-m-d'),
                'inscrit_le' => $user->created_at?->toIso8601String(),
                'email_verifie_le' => $user->email_verified_at?->toIso8601String(),
                'photo' => $user->picture,
                'roles' => $user->getRoleNames(),
                'resume_hebdomadaire' => $user->digest_optin,
            ],
            'avis' => $user->reviews()->get()->map(fn ($review) => [
                'identifiant' => $review->id,
                'contenu' => $review->content,
                'lien' => $review->link,
                'images' => $review->medias,
                'tags' => $review->tags->pluck('name'),
                'jaime' => $review->nb_like,
                'jenaimepas' => $review->nb_dislike,
                'vues' => $review->nb_views,
                'publie' => $review->is_published,
                'publie_le' => $review->created_at?->toIso8601String(),
            ]),
            'commentaires' => $user->comments()->get()->map(fn ($comment) => [
                'identifiant' => $comment->id,
                'avis' => $comment->review_id,
                'reponse_a' => $comment->parent_id,
                'contenu' => $comment->content,
                'ecrit_le' => $comment->created_at?->toIso8601String(),
            ]),
            'reactions' => $user->reviewReactions()->get()->map(fn ($reaction) => [
                'avis' => $reaction->review_id,
                'reaction' => $reaction->reaction,
                'le' => $reaction->created_at?->toIso8601String(),
            ]),
            'abonnements' => [
                'membres' => $user->followedUsers()->pluck('username'),
                'sujets' => $user->followedTags()->pluck('name'),
            ],
            'enregistrements' => Bookmark::where('user_id', $user->id)->pluck('review_id'),
            'signalements_emis' => Report::where('user_id', $user->id)->get()
                ->map(fn ($report) => [
                    'motif' => $report->reason,
                    'precisions' => $report->details,
                    'statut' => $report->status,
                    'le' => $report->created_at?->toIso8601String(),
                ]),
            'suggestions' => Suggestion::where('user_id', $user->id)->pluck('message'),
            'notifications' => $user->notifications()->get()->map(fn ($n) => [
                'type' => $n->type,
                'donnees' => $n->data,
                'lue_le' => $n->read_at?->toIso8601String(),
                'le' => $n->created_at?->toIso8601String(),
            ]),
        ];

        $nom = 'yowl-'.$user->username.'-'.now()->format('Y-m-d').'.json';

        // Téléchargement direct : demander une adresse email pour recevoir
        // ses propres données ajoute une étape sans rien protéger de plus.
        return response()->json($donnees, 200, [
            'Content-Disposition' => 'attachment; filename="'.$nom.'"',
            'Content-Type' => 'application/json; charset=utf-8',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    /**
     * A summary of what the export would contain, shown before downloading.
     */
    public function summary(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'avis' => $user->reviews()->count(),
                'commentaires' => $user->comments()->count(),
                'reactions' => $user->reviewReactions()->count(),
                'abonnements' => Follow::where('user_id', $user->id)->count(),
                'enregistrements' => Bookmark::where('user_id', $user->id)->count(),
                'notifications' => $user->notifications()->count(),
            ],
            'message' => 'Summary retrieved successfully.',
        ]);
    }
}
