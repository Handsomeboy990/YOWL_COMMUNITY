# Déploiement YOWL Community - Checklist Rapide 

##  Avant de commencer

- [ ] Compte GitHub créé et repository poussé
- [ ] Compte Railway.app créé ([railway.app](https://railway.app))
- [ ] Compte Vercel créé ([vercel.com](https://vercel.com))

---

##  Backend (Railway)

### Setup Initial
- [ ] Nouveau projet sur Railway
- [ ] PostgreSQL provisionné
- [ ] Repository GitHub connecté
- [ ] Dossier racine : `yowl-project/backend`

### Variables d'environnement (Railway)
```bash
APP_KEY=         # Générer avec: php artisan key:generate --show
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=pgsql
```

### Après déploiement
- [ ] Tester : `curl https://votre-app.up.railway.app/api/health`
- [ ] Copier l'URL du backend

---

##  Frontend (Vercel)

### Setup Initial
- [ ] Nouveau projet sur Vercel
- [ ] Repository GitHub connecté
- [ ] Framework : **Vite**
- [ ] Root Directory : `yowl-project/frontend/yowl-frontend`
- [ ] Build Command : `npm run build`
- [ ] Output Directory : `dist`

### Variables d'environnement (Vercel)
```bash
VITE_BASE_URL=https://VOTRE-BACKEND.up.railway.app/api
VITE_STORAGE_URL=https://VOTRE-BACKEND.up.railway.app/storage
VITE_APP_NAME=YOWL Community
NODE_ENV=production
```

### Après déploiement
- [ ] Tester : Ouvrir `https://votre-app.vercel.app`
- [ ] Vérifier la connexion au backend

---

##  Configuration CORS

### Sur Railway
```bash
SANCTUM_STATEFUL_DOMAINS=localhost:5173,VOTRE-APP.vercel.app
FRONTEND_URL=https://VOTRE-APP.vercel.app
```

### Dans le code (backend/config/cors.php)
```php
'allowed_origins' => [
    env('FRONTEND_URL', 'http://localhost:5173'),
],
'allowed_origins_patterns' => [
    '/\.vercel\.app$/',
],
```

- [ ] Modifier le fichier CORS
- [ ] Commit et push
- [ ] Attendre le redéploiement automatique

---

##  Tests finaux

- [ ] Backend répond : `curl https://backend.up.railway.app/api/health`
- [ ] Frontend s'affiche : `https://frontend.vercel.app`
- [ ] Inscription/Connexion fonctionne
- [ ] Les requêtes API passent (vérifier console navigateur F12)

---

##  C'est fini !

Votre application est en ligne gratuitement !

**Backend** : https://________________.up.railway.app
**Frontend** : https://________________.vercel.app

---

## En cas de problème

1. Vérifier les logs sur Railway/Vercel
2. Vérifier les variables d'environnement
3. Consulter `DEPLOYMENT_GUIDE.md` pour plus de détails
