<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ta semaine sur YOWL</title>
</head>
<body style="margin:0;padding:0;background:#f5f3f1;font-family:Helvetica,Arial,sans-serif;color:#1e2a38;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f3f1;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:12px;overflow:hidden;">
                <tr>
                    <td style="background:#1e2a38;padding:24px;">
                        <span style="color:#ffffff;font-size:22px;font-weight:bold;letter-spacing:-0.5px;">YOWL</span>
                    </td>
                </tr>

                <tr>
                    <td style="padding:28px 24px 8px;">
                        <h1 style="margin:0 0 8px;font-size:20px;line-height:1.3;">Bonjour {{ $user->username }},</h1>
                        <p style="margin:0;color:#56616f;font-size:15px;line-height:1.6;">
                            @if ($activity['received'] > 0)
                                Tu as reçu {{ $activity['received'] }} réaction{{ $activity['received'] > 1 ? 's' : '' }}
                                et {{ $activity['comments'] }} commentaire{{ $activity['comments'] > 1 ? 's' : '' }} cette semaine.
                            @else
                                Voici ce qui a animé les sujets que tu suis cette semaine.
                            @endif
                        </p>
                    </td>
                </tr>

                @forelse ($reviews as $review)
                    <tr>
                        <td style="padding:12px 24px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5ddd7;border-radius:8px;">
                                <tr>
                                    <td style="padding:14px 16px;">
                                        <p style="margin:0 0 6px;font-size:12px;color:#8a939e;">
                                            {{ $review->user?->username ?? 'un membre' }}
                                        </p>
                                        <p style="margin:0 0 10px;font-size:15px;line-height:1.5;">
                                            {{ \Illuminate\Support\Str::limit($review->content, 140) }}
                                        </p>
                                        <p style="margin:0;font-size:12px;color:#8a939e;">
                                            {{ $review->nb_like }} j'aime &middot; {{ $review->comments_count ?? 0 }} commentaires
                                        </p>
                                        <a href="{{ config('app.frontend_url') }}/reviews/{{ $review->id }}"
                                           style="display:inline-block;margin-top:12px;color:#d9491b;font-size:14px;font-weight:bold;text-decoration:none;">
                                            Lire l'avis
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td style="padding:12px 24px;">
                            <p style="margin:0;color:#56616f;font-size:15px;line-height:1.6;">
                                Rien de neuf sur tes sujets cette semaine. Suis quelques membres de plus
                                pour remplir ton fil.
                            </p>
                        </td>
                    </tr>
                @endforelse

                <tr>
                    <td style="padding:20px 24px 28px;">
                        <a href="{{ config('app.frontend_url') }}/feed"
                           style="display:inline-block;background:#ff6b35;color:#ffffff;font-size:15px;font-weight:bold;text-decoration:none;padding:12px 22px;border-radius:8px;">
                            Ouvrir mon fil
                        </a>
                    </td>
                </tr>

                <tr>
                    <td style="padding:16px 24px;background:#faf8f6;border-top:1px solid #e5ddd7;">
                        <p style="margin:0;font-size:12px;color:#8a939e;line-height:1.6;">
                            Tu reçois ce message parce que tu es membre de YOWL.
                            <a href="{{ $unsubscribeUrl }}" style="color:#8a939e;">Ne plus recevoir ce résumé</a>.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
