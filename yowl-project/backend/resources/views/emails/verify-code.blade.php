<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Code de vérification</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .code { font-size: 28px; font-weight: bold; letter-spacing: 6px; }
    </style>
</head>
<body>
    <p>Bonjour,</p>
    <p>Voici votre code de vérification (valide 15 minutes) :</p>
    <p class="code">{{ $code }}</p>
    <p>Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.</p>
    <p>— L'équipe YOWL</p>
</body>
</html>
