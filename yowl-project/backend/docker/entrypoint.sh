#!/bin/sh
set -e

# L'hebergeur impose le port par une variable. Il est injecte dans la
# configuration nginx au demarrage plutot que code en dur.
export PORT="${PORT:-8000}"
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/conf.d/default.conf

# Les caches sont reconstruits ici, pas a la construction de l'image : ils
# capturent les variables d'environnement, qui n'existent pas au build.
php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan migrate --force

exec "$@"
