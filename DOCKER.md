# Docker Guide (Simple)

## Lancer le backend
```bash
cp backend/.env.example backend/.env
# Adapter DB_HOST=db, DB_DATABASE=yowl, DB_USERNAME=yowl, DB_PASSWORD=yowl

docker compose up -d --build
```

Accès application: http://localhost:8080

## Commandes utiles
```bash
# Voir les logs
docker compose logs -f app

# Executer une commande artisan
docker compose exec app php artisan migrate

docker compose exec app php artisan tinker
```

## Variables spéciales
- RUN_MIGRATIONS=true : lance les migrations au démarrage (dev)

## Structure des services
- app: php-fpm (Laravel)
- web: nginx reverse proxy (backend via /public)
- db: MySQL 8
- frontend: build Vite servi par Nginx (port 3000)

## Frontend (Vite + Vue)

Le frontend est dockerisé via un Dockerfile multi-stage (build Node puis Nginx statique).

Accès: http://localhost:3000

Variable d'API injectée au build : `VITE_API_URL=http://localhost:8080/api`

### Rebuild frontend après changement d'API
```bash
docker compose build frontend --no-cache
docker compose up -d frontend
```

### Développement local hors Docker (optionnel)
```bash
cd yowl-project/frontend/yowl-frontend
npm install
echo "VITE_API_URL=http://localhost:8080/api" > .env
npm run dev
```

### Mise à jour dépendances front
```bash
docker compose exec frontend sh -c "npm install && npm run build"
docker compose restart frontend
```

## Arrêter
```bash
docker compose down
```

## Nettoyer
```bash
docker compose down -v
```

## Astuces
- Pour exécuter une commande artisan rapide: `docker compose exec app php artisan <cmd>`
- Pour vérifier les permissions : `docker compose exec app ls -l storage`
- Pour voir le build front: `docker compose logs -f frontend`

## Prochaines améliorations possibles
- Ajouter Redis pour cache/queues
- Ajouter un service queue worker (php artisan queue:work)
- Activer Opcache production + config spécifique
- Ajouter un healthcheck sur php-fpm et nginx
