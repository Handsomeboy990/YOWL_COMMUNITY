<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subjectLine }}</title>
</head>
{{-- Styles en ligne : les clients mail ignorent la plupart des feuilles. --}}
<body style="margin:0;padding:0;background:#f4f6fa;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;color:#1e2a38;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6fa;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                   style="max-width:600px;background:#ffffff;border-radius:16px;overflow:hidden;">
                <tr>
                    <td style="background:#1e2a38;padding:22px 28px;">
                        <span style="color:#ffffff;font-size:19px;font-weight:700;letter-spacing:-0.01em;">
                            {{ $siteName }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td style="padding:28px;font-size:16px;line-height:1.65;">
                        {!! $bodyHtml !!}
                    </td>
                </tr>
                <tr>
                    <td style="padding:20px 28px;border-top:1px solid #e5e7eb;font-size:12px;line-height:1.6;color:#6b7280;">
                        <p style="margin:0 0 8px;">
                            Tu reçois ce message parce que tu es membre de {{ $siteName }}.
                        </p>
                        <p style="margin:0;">
                            <a href="{{ $unsubscribeUrl }}" style="color:#cc4a15;">
                                Ne plus recevoir ce type d'email
                            </a>
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
