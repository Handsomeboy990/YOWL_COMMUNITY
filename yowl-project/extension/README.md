# Extension YOWL

Elle dit ce que la communauté pense de la page que tu lis, et te laisse
répondre sans quitter le site.

## L'idée

La version précédente ouvrait une fenêtre qui rechargeait l'application
entière pour afficher un formulaire de partage. C'était un marque-page avec
des étapes en plus.

Celle-ci part de l'autre bout : **l'extension rend la communauté ambiante**.
Tu navigues, et quand tu arrives sur une page dont on parle sur YOWL, une
pastille apparaît sur l'icône. Tu ouvres le panneau, tu vois qui en parle et
ce qui s'y dit, et tu décides de rejoindre la conversation ou d'en ouvrir une.

## Ce qu'elle fait

| Geste | Ce qui se passe |
|---|---|
| Naviguer | Une pastille sur l'icône compte les discussions ouvertes sur la page |
| Cliquer l'icône | Le panneau montre ces discussions, puis un champ pour écrire |
| Clic droit sur la page | Ouvre le panneau avec l'adresse pré-remplie |
| Clic droit sur un lien | Même chose, sur le lien plutôt que sur la page |
| Clic droit sur une sélection | Le passage devient une citation en tête de l'avis |

## Navigateurs

| Famille | État | Comment |
|---|---|---|
| Chrome, Edge, Brave, Opera, Vivaldi | pris en charge | paquet `chrome` |
| Firefox 121 et suivants | pris en charge | paquet `firefox` |
| Safari | conversion nécessaire | Xcode et un compte développeur Apple |

Un seul jeu de sources. `browser.js` normalise les deux familles d'API :
Chrome expose `chrome` avec des rappels, Firefox expose `browser` avec des
promesses. Le seul écart qui reste est le manifeste, où Chrome exige
`background.service_worker` et Firefox `background.scripts` : `build.sh`
produit les deux.

## Installation en développement

**Chrome, Edge, Brave, Opera**

1. Ouvre `chrome://extensions`, ou `edge://extensions`.
2. Active le **mode développeur**.
3. **Charger l'extension non empaquetée**, puis choisis ce dossier.

**Firefox**

1. Ouvre `about:debugging#/runtime/this-firefox`.
2. **Charger un module temporaire**, puis choisis le `manifest.json` de ce dossier.
3. Le module disparaît à la fermeture de Firefox : c'est le propre d'un module
   temporaire, pas un défaut.

Ensuite, clique l'icône et connecte-toi. Aucune adresse à saisir.

## La connexion

Elle se fait **dans le panneau**, avec l'adresse email et le mot de passe du
compte YOWL.

Une version précédente ouvrait une page du site qui renvoyait un jeton par
`externally_connectable`. Ça imposait de quitter ce qu'on lisait, de retrouver
l'onglet de l'extension et de cliquer un second bouton : la plupart des gens
abandonnaient en route. Firefox ne prend d'ailleurs pas en charge
`externally_connectable`, ce qui condamnait l'approche pour de bon.

Le mot de passe part directement à l'API officielle et n'est conservé nulle
part. Seul le jeton rendu par l'API est gardé, dans le stockage local. Il se
révoque des deux côtés : depuis les réglages de l'extension, ou en changeant de
mot de passe sur le site, ce qui déconnecte tous les appareils.

## Réglages

La page des réglages montre l'état de la connexion et ce que l'extension voit.
Les adresses du site et de l'API sont repliées dans **Réglages avancés** :
elles ne concernent que le développement ou une instance auto-hébergée. Un
membre ordinaire n'a jamais à les ouvrir.

Les valeurs par défaut vivent dans `config.js`, à ajuster avant de construire
les paquets de production.

## Construire les paquets

```
./build.sh          # construit les deux paquets
./build.sh 3.2.0    # change aussi le numéro de version
```

Produit `dist/yowl-chrome-<version>.zip` et `dist/yowl-firefox-<version>.zip`.

## Avant publication

- Mettre `PAR_DEFAUT` dans `config.js` sur les adresses de production.
- Restreindre `host_permissions` au seul domaine de l'API. Un joker demande une
  autorisation très large, que les magasins font remarquer et que les
  utilisateurs refusent.
- Vérifier que `browser_specific_settings.gecko.id` porte un identifiant que tu
  contrôles.
