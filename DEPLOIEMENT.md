# Mise en ligne

La procédure complète, pas à pas, avec les liens et les valeurs à remplir,
vit dans le guide remis à part. Ce fichier ne répète pas ce qu'il dit : il
donne les faits qu'un déploiement doit connaître, et qui changent avec le
code plutôt qu'avec l'hébergeur.

## La pile retenue

| Rôle | Service | Ce qui est gratuit |
|---|---|---|
| Interface | Vercel | 100 Go de trafic par mois |
| API | Koyeb | un service web, 512 Mo |
| Base de données | Neon (PostgreSQL) | 0,5 Go |
| Médias | Cloudflare R2 | 10 Go, sortie gratuite |
| Cache | Upstash (Redis) | 10 000 commandes par jour |

Les documents antérieurs décrivaient un déploiement Railway écrit avant la
réécriture du Dockerfile. Ils faisaient échouer le build sur le répertoire de
travail et sur le port, et ont été retirés plutôt que corrigés.

## L'API

L'image est construite depuis `yowl-project/backend/Dockerfile`. C'est le
répertoire de travail à déclarer chez l'hébergeur, et le builder doit être
Dockerfile, jamais Buildpack.

Le conteneur fait tourner php-fpm derrière nginx, tenus par supervisord, avec
trois programmes de plus : la file d'attente, le planificateur, et Reverb.
Ce dernier reste à `autostart=false` tant que l'hébergement met le service en
veille, puisqu'il tient une connexion permanente.

Au démarrage, l'entrypoint reconstruit les caches de configuration et de
routes, puis applique les migrations. Rien à lancer à la main.

Le port est lu dans la variable `PORT` et injecté dans la configuration nginx
au démarrage. La sonde de santé est `/api/health`.

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
