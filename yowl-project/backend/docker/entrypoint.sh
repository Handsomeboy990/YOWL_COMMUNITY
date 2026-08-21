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

# Attendre que la base reponde avant de migrer.
#
# Une base managee refuse parfois les premieres connexions : elle sort de
# veille, negocie son TLS, ou l'hebergeur n'a pas fini de cabler le reseau.
# Avec set -e, un seul refus tuait le conteneur, et l'hebergeur ne rapportait
# qu'un echec de reveil sans dire ce qui n'avait pas repondu.
# Les migrations evitent le pooler.
#
# Neon fait tourner PgBouncer en mode transaction sur son endpoint "-pooler",
# qui ne supporte pas le DDL : la premiere instruction echoue, la transaction
# est empoisonnee, et les suivantes remontent un 25P02 qui masque la cause.
# Le pooler reste utilise a l'execution, ou il economise les connexions.
if [ "${DB_CONNECTION:-}" = "pgsql" ] || [ -n "${PGHOST:-}" ]; then
    CONNEXION_MIGRATION=pgsql_unpooled
else
    CONNEXION_MIGRATION="${DB_CONNECTION:-sqlite}"
fi

# Une base ephemere en production est un piege silencieux : le conteneur ecrit
# dans un fichier qui meurt avec lui, les migrations repartent de zero a chaque
# redemarrage, et la base managee reste vide sans que rien ne le signale.
#
# Le refus vit ici plutot que dans le code applicatif : ce script ne tourne
# qu'au demarrage d'un conteneur, la ou l'intention ne fait aucun doute. Place
# dans un fournisseur de services, il tuait aussi composer install, car
# package:discover lance artisan sur un depot fraichement clone.
if [ "${APP_ENV:-}" = "production" ] && [ "$CONNEXION_MIGRATION" = "sqlite" ]; then
    echo "-----------------------------------------------------------------"
    echo "ARRET : la base de donnees est SQLite en production."
    echo ""
    echo "Un fichier SQLite vit dans ce conteneur et disparait avec lui : les"
    echo "migrations repartiraient de zero a chaque redemarrage et la base"
    echo "managee resterait vide, sans que rien ne le signale."
    echo ""
    echo "Renseigne DB_CONNECTION=pgsql, DB_HOST, DB_PORT, DB_DATABASE,"
    echo "DB_USERNAME et DB_PASSWORD. Les variables PGHOST, PGDATABASE,"
    echo "PGUSER et PGPASSWORD sont acceptees en repli."
    echo "-----------------------------------------------------------------"
    exit 1
fi

attendre_la_base() {
    essai=1
    while [ "$essai" -le 10 ]; do
        if php artisan db:show --database="$CONNEXION_MIGRATION" --json > /dev/null 2>&1; then
            echo "Base joignable au bout de $essai tentative(s)."
            return 0
        fi
        echo "Base injoignable, tentative $essai sur 10, nouvelle tentative dans 3 s..."
        essai=$((essai + 1))
        sleep 3
    done

    # Dire ce qu'on a essayé d'atteindre, sans jamais le mot de passe.
    echo "-----------------------------------------------------------------"
    echo "ECHEC : la base de donnees n'a pas repondu apres 10 tentatives."
    echo "  pilote  : ${DB_CONNECTION:-absent, repli sur PGHOST ou sqlite}"
    echo "  hote    : ${DB_HOST:-${PGHOST:-non renseigne}}"
    echo "  port    : ${DB_PORT:-${PGPORT:-5432}}"
    echo "  base    : ${DB_DATABASE:-${PGDATABASE:-non renseignee}}"
    echo "  identifiant : ${DB_USERNAME:-${PGUSER:-non renseigne}}"
    echo "  mot de passe : $([ -n "${DB_PASSWORD:-${PGPASSWORD:-}}" ] && echo renseigne || echo ABSENT)"
    echo "  sslmode : ${DB_SSLMODE:-${PGSSLMODE:-prefer}}"
    echo "  connexion utilisee pour migrer : $CONNEXION_MIGRATION"
    echo ""
    echo "Neon exige sslmode=require. Verifie aussi que l'hote est bien celui"
    echo "de la branche active dans la console Neon."
    echo "-----------------------------------------------------------------"
    return 1
}

attendre_la_base || exit 1

php artisan migrate --force --database="$CONNEXION_MIGRATION"

# Les six pages du site : a propos, FAQ, charte, confidentialite, conditions,
# mentions legales. Le pied de page y renvoie, et une base fraichement migree
# ne les contient pas : les six liens repondaient 404.
#
# Seul ce qui manque est cree, jamais ce qui existe : les modifications faites
# depuis la console survivent au redemarrage. Le retour au texte livre passe
# par yowl:seed-pages --reset.
php artisan yowl:seed-pages

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
