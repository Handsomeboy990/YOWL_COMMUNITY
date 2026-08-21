<?php

/*
|--------------------------------------------------------------------------
| Messages d'authentification
|--------------------------------------------------------------------------
|
| Chacun dit ce qui s'est passé, puis quoi faire ensuite. Un refus qui
| n'indique pas la suite laisse la personne devant un formulaire dont elle
| ne sait pas quoi changer, et elle réessaie à l'identique.
|
| Ils ne disent jamais si l'adresse existe : répondre « ce compte n'existe
| pas » transforme le formulaire en annuaire, où l'on teste des adresses
| pour savoir qui est inscrit.
|
*/

return [
    'failed' => "L'adresse email ou le mot de passe ne correspond pas. Vérifiez les deux, "
        ."puis réessayez. Si le mot de passe vous échappe, utilisez « Mot de passe oublié ».",

    'password' => 'Le mot de passe est incorrect.',

    'throttle' => 'Trop de tentatives de connexion. Réessayez dans :seconds secondes, '
        ."ou réinitialisez votre mot de passe si vous ne le retrouvez pas.",

    'banned' => 'Ce compte a été désactivé et ne peut plus être utilisé. '
        ."S'il s'agit d'une erreur, répondez au dernier message reçu de notre part "
        .'ou passez par le formulaire de suggestion.',

    'unverified' => "Ce compte n'a pas encore été vérifié. Un code à six chiffres vous a été "
        .'envoyé par email au moment de votre inscription : saisissez-le ci-dessous. '
        ."Si vous ne le retrouvez pas, demandez-en un nouveau.",
];
