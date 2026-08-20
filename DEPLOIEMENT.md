# Mise en ligne

La procédure complète, pas à pas, avec les liens et les valeurs à remplir,
vit dans le guide remis à part. Ce fichier ne répète pas ce qu'il dit : il
donne les faits qu'un déploiement doit connaître, et qui changent avec le
code plutôt qu'avec l'hébergeur.

## La pile retenue

| Rôle | Service | Ce qui est gratuit |
|---|---|---|
| Interface | Vercel | 100 Go de trafic par mois |
| API | Render | 750 heures d'instance par mois |
| Base de données | Neon (PostgreSQL) | 0,5 Go |
| Médias | Cloudflare R2 | 10 Go, sortie gratuite |
| Cache | Upstash (Redis) | 10 000 commandes par jour |

Koyeb a d'abord été retenu pour l'API. Son rachat par Mistral en février 2026
a fermé le palier gratuit aux nouveaux comptes et réorienté la plateforme vers
les charges IA : la création d'un service web n'y est plus possible. Render est
la seule offre restante qui accepte un Dockerfile, ne demande pas de carte
bancaire et reste gratuite sans limite de durée.

Les documents antérieurs décrivaient un déploiement Railway écrit avant la
réécriture du Dockerfile. Ils faisaient échouer le build sur le répertoire de
travail et sur le port, et ont été retirés plutôt que corrigés.

## L'API

L'image est construite depuis `yowl-project/backend/Dockerfile`. Le fichier
`render.yaml` à la racine du dépôt décrit le service, ses chemins et celles de
ses variables qui ne sont pas des secrets.

Le conteneur fait tourner php-fpm derrière nginx, tenus par supervisord, avec
trois programmes de plus : la file d'attente, le planificateur, et Reverb.
Ce dernier reste à `autostart=false` tant que l'hébergement met le service en
veille, puisqu'il tient une connexion permanente.

Au démarrage, l'entrypoint reconstruit les caches de configuration et de
routes, puis applique les migrations. Rien à lancer à la main.

Le port est lu dans la variable `PORT` et injecté dans la configuration nginx
au démarrage. La sonde de santé est `/api/health`.

## Le sommeil, et ce qu'il casse

Le palier gratuit de Render endort le conteneur après quinze minutes sans
trafic. Le réveil prend de trente à cinquante secondes, que le premier visiteur
attend. C'est le prix de la gratuité, et aucune offre sans carte bancaire ne
l'évite aujourd'hui.

Ce sommeil a une conséquence moins visible que la lenteur : un conteneur
endormi ne tient ni son planificateur ni sa file d'attente. Les avis programmés
resteraient non publiés, le résumé hebdomadaire ne partirait jamais, et les
aperçus de liens ne seraient jamais récupérés.

D'où la route `POST /cron/<jeton>`, appelée depuis l'extérieur. Elle réveille le
conteneur et lui fait rattraper son retard. Chaque tâche y est déclenchée par sa
date de dernière exécution plutôt que par une expression cron, parce qu'une
horloge extérieure dérive et raterait une fenêtre à la minute près.

Deux horloges possibles :

- Le workflow `.github/workflows/cron.yml`, qui ne demande aucun compte
  supplémentaire. Renseigner les secrets `API_URL` et `CRON_TOKEN` du dépôt.
  GitHub retarde parfois les tâches planifiées et les suspend au bout de
  soixante jours sans activité sur le dépôt.
- Un service dédié comme cron-job.org, plus régulier, qui appelle la même
  adresse toutes les dix minutes.

Sur un hébergement qui ne dort pas, laisser `CRON_TOKEN` vide : la route répond
alors 404 et le planificateur du conteneur suffit.

## L'interface

Racine du projet : `yowl-project/frontend/yowl-frontend`. Le `vercel.json` du
dépôt fait le reste, à une chose près : les deux réécritures en tête du
fichier pointent vers `API_HOST`, à remplacer par l'hôte réel de l'API. Elles
servent `robots.txt` et `sitemap.xml` depuis la racine du site, où un moteur
les cherche, alors qu'ils sont construits par l'API.

## Les variables

Le modèle complet est dans `yowl-project/backend/.env.example` et
`yowl-project/frontend/yowl-frontend/.env.example`, chaque ligne commentée
avec ce qu'elle fait. Les valeurs qui demandent une décision plutôt qu'une
copie :

- `MEDIA_DISK=s3` et les `AWS_*` : sans elles, les fichiers envoyés vivent
  dans le conteneur et disparaissent au premier redémarrage.
- `FRONTEND_URLS` : la liste des origines autorisées par le CORS, séparées
  par des virgules, sans joker. Les déploiements de prévisualisation doivent
  y figurer un par un.
- `CACHE_STORE=redis` : le client phpredis est installé dans l'image. Les
  sessions et la file restent volontairement sur PostgreSQL, pour ne pas
  vider le quota Redis en une matinée.
- `VAPID_PUBLIC_KEY` doit valoir exactement `VITE_VAPID_PUBLIC_KEY`. Un écart
  entre les deux fait échouer les notifications sans le moindre message.
- `BROADCAST_CONNECTION` et `MAIL_MAILER` se ressemblent et acceptent tous deux
  la valeur `log`. Le premier n'accepte que `reverb`, `pusher`, `ably`, `log` ou
  `null` : y écrire `smtp` désactivait la diffusion et, avant le garde-fou
  ajouté depuis, empêchait l'application entière de démarrer.

## Les emails

`MAIL_MAILER=log` n'envoie rien et écrit le message dans les journaux. C'est ce
qu'on laisse tant qu'aucun fournisseur n'est branché : la plateforme fonctionne,
mais personne ne reçoit sa réinitialisation de mot de passe.

Pour envoyer réellement, il faut un relais SMTP. Gmail en est un, mais il compte
par destinataire, plafonne à 500 par jour et délivre mal le courrier
transactionnel envoyé depuis un compte personnel : une réinitialisation de mot
de passe qui tombe dans les indésirables est un compte perdu.

Brevo convient mieux et reste gratuit jusqu'à 300 destinataires par jour :

```
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=<identifiant fourni par Brevo>
MAIL_PASSWORD=<clé SMTP fournie par Brevo>
MAIL_FROM_ADDRESS=<une adresse vérifiée chez Brevo>
```

L'adresse d'expédition doit être vérifiée chez le fournisseur, sinon le message
est refusé à l'envoi.

## Le premier administrateur

```
php artisan yowl:make-admin
```

Depuis une console sur le conteneur. Le mot de passe est saisi par l'opérateur
et n'est écrit nulle part. Un compte existant est promu plutôt que dupliqué.

Ne jamais lancer `migrate:fresh --seed` sur une base qui porte de vraies
données : le seed efface tout et recrée quarante comptes de démonstration.
Le seeder refuse de tourner quand `APP_ENV` vaut `production`, mais
`migrate:fresh`, lui, ne refuse rien.

## L'extension

Elle n'est pas déployée avec le reste. Son dossier est `yowl-project/extension`,
et son `README.md` dit ce qu'il faut restreindre avant de la publier sur le
magasin d'extensions.
