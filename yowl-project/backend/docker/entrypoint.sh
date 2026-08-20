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

# Premier administrateur, cree au demarrage.
#
# Les offres gratuites de la plupart des hebergeurs ne donnent aucun acces
# shell : le seul moment ou l'on peut creer ce compte est le demarrage du
# conteneur. --if-none rend l'operation idempotente, donc ces trois variables
# peuvent rester en place sans qu'un redemarrage refasse quoi que ce soit.
#
# A retirer des que le compte existe : un mot de passe dans les variables
# d'environnement n'a pas vocation a y rester.
if [ -n "${ADMIN_EMAIL:-}" ] && [ -n "${ADMIN_PASSWORD:-}" ]; then
    php artisan yowl:make-admin \
        --if-none \
        --email="$ADMIN_EMAIL" \
        --username="${ADMIN_USERNAME:-admin}" \
        --password="$ADMIN_PASSWORD"
fi

exec "$@"
