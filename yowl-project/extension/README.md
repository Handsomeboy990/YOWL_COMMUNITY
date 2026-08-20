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

## Installation en développement

1. Ouvre `chrome://extensions`.
2. Active le **mode développeur**.
3. **Charger l'extension non empaquetée**, puis choisis ce dossier.
4. Ouvre les réglages de l'extension et renseigne les deux adresses.
5. Clique **Connecter mon compte** : le site remet un jeton à l'extension.

## Réglages

| Réglage | Développement | Production |
|---|---|---|
| Adresse du site | `http://localhost:5173` | l'adresse Vercel |
| Adresse de l'API | `http://localhost:8000/api` | l'adresse Koyeb, suffixe `/api` compris |

## La connexion

L'extension ne demande jamais de mot de passe et n'offre aucun champ où
coller un jeton à la main. Elle ouvre la page `/extension` du site, qui, une
fois la personne connectée, lui envoie le jeton que le navigateur détient
déjà. C'est le navigateur qui garantit l'origine du message, par la liste
`externally_connectable` du manifeste : aucun autre site ne peut se faire
passer pour celui-ci.

Le jeton est révocable des deux côtés : depuis les réglages de l'extension,
ou en changeant de mot de passe sur le site, ce qui invalide tous les autres
appareils.

## Avant publication sur le Chrome Web Store

- Remplacer `https://*.vercel.app/*` dans `externally_connectable` par le
  domaine réel : un joker sur tout `vercel.app` laisserait n'importe quel
  déploiement de n'importe qui parler à l'extension.
- Restreindre `host_permissions` au seul domaine de l'API. Le `https://*/*`
  actuel sert à lire l'adresse de l'onglet en développement.
- Fournir des icônes en 128 pixels, exigées par le magasin.
