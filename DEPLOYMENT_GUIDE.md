#  Guide de Déploiement YOWL Community

Ce guide vous aide à déployer **gratuitement** votre projet YOWL Community sur Railway (backend) et Vercel (frontend).

##  Prérequis

- Compte GitHub (gratuit)
- Compte Railway.app (gratuit - 500h/mois)
- Compte Vercel (gratuit - illimité)

---

##  PARTIE 1 : Déploiement Backend (Laravel) sur Railway

### Étape 1 : Créer un compte Railway

1. Allez sur [railway.app](https://railway.app)
2. Cliquez sur **"Start a New Project"**
3. Connectez-vous avec GitHub
4. Autorisez Railway à accéder à vos repos

### Étape 2 : Créer le projet sur Railway

1. Cliquez sur **"New Project"**
2. Sélectionnez **"Provision PostgreSQL"** (base de données gratuite)
3. Attendez que PostgreSQL soit provisionné
4. Cliquez sur **"New"** → **"GitHub Repo"**
5. Sélectionnez votre repository `yowl-community`
6. Choisissez le dossier **`yowl-project/backend`** comme racine

### Étape 3 : Configuration des variables d'environnement

Dans Railway, allez dans votre service backend → **Variables** :

```env
APP_NAME=YOWL Community
APP_ENV=production
APP_KEY=base64:VOTRE_CLE_ICI
APP_DEBUG=false
APP_URL=${{RAILWAY_PUBLIC_DOMAIN}}

DB_CONNECTION=pgsql
DB_HOST=${{PGHOST}}
DB_PORT=${{PGPORT}}
DB_DATABASE=${{PGDATABASE}}
DB_USERNAME=${{PGUSER}}
DB_PASSWORD=${{PGPASSWORD}}

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

LOG_LEVEL=error
```

** IMPORTANT : Générer APP_KEY**

Localement, exécutez :
```bash
cd yowl-project/backend
php artisan key:generate --show
```

Copiez la clé générée et collez-la dans `APP_KEY` sur Railway.

### Étape 4 : Déployer

1. Railway détecte automatiquement votre configuration
2. Le build démarre automatiquement
3. Attendez 2-5 minutes
4. Votre backend sera disponible sur : `https://votre-app.up.railway.app`

### Étape 5 : Vérification

Testez votre API :
```bash
curl https://votre-app.up.railway.app/api/health
```

---

##  PARTIE 2 : Déploiement Frontend (Vue.js) sur Vercel

### Étape 1 : Créer un compte Vercel

1. Allez sur [vercel.com](https://vercel.com)
2. Cliquez sur **"Sign Up"**
3. Connectez-vous avec GitHub
4. Autorisez Vercel

### Étape 2 : Importer le projet

1. Cliquez sur **"Add New..."** → **"Project"**
2. Importez votre repository `yowl-community`
3. **Framework Preset** : Sélectionnez **"Vite"**
4. **Root Directory** : Cliquez sur **"Edit"** et sélectionnez `yowl-project/frontend/yowl-frontend`
5. **Build Command** : `npm run build`
6. **Output Directory** : `dist`

### Étape 3 : Variables d'environnement

Dans les **Environment Variables** de Vercel :

```env
VITE_API_URL=https://votre-backend.up.railway.app/api
VITE_APP_NAME=YOWL Community
NODE_ENV=production
```

** Remplacez** `votre-backend.up.railway.app` par l'URL réelle de Railway (Étape 1.4)

### Étape 4 : Déployer

1. Cliquez sur **"Deploy"**
2. Attendez 2-3 minutes
3. Votre frontend sera disponible sur : `https://votre-app.vercel.app`

---

##  PARTIE 3 : Configuration CORS (Liaison Backend ↔ Frontend)

### Sur Railway (Backend)

Ajoutez ces variables dans Railway :

```env
SANCTUM_STATEFUL_DOMAINS=localhost:5173,votre-app.vercel.app
FRONTEND_URL=https://votre-app.vercel.app
SESSION_DOMAIN=.vercel.app
```

### Mettre à jour le fichier CORS

Éditez `backend/config/cors.php` :

```php
'allowed_origins' => [
    env('FRONTEND_URL', 'http://localhost:5173'),
],

'allowed_origins_patterns' => [
    '/\.vercel\.app$/',
],

'supports_credentials' => true,
```

Committez et poussez :
```bash
git add backend/config/cors.php
git commit -m "feat: configure CORS for production"
git push origin main
```

Railway redéploiera automatiquement.

---

##  PARTIE 4 : Vérification finale

### Test 1 : Backend seul
```bash
curl https://votre-backend.up.railway.app/api/health
```

### Test 2 : Frontend
Ouvrez `https://votre-app.vercel.app` dans votre navigateur.

### Test 3 : Connexion Backend ↔ Frontend
1. Allez sur votre frontend
2. Essayez de vous connecter / créer un compte
3. Vérifiez que les requêtes API fonctionnent

---

##  Déploiements futurs (Automatiques)

Après la configuration initiale :

1. **Faites vos modifications** localement
2. **Committez** :
   ```bash
   git add .
   git commit -m "feat: nouvelle fonctionnalité"
   git push origin main
   ```
3. **Railway et Vercel déploient automatiquement** ! 

---

##  Monitorer vos applications

### Railway
- Dashboard : [railway.app/dashboard](https://railway.app/dashboard)
- Logs en temps réel
- Métriques CPU/RAM

### Vercel
- Dashboard : [vercel.com/dashboard](https://vercel.com/dashboard)
- Analytics
- Logs de déploiement

---

##  Dépannage

### Erreur 500 sur Railway
1. Vérifiez les logs : Railway Dashboard → Votre service → **Deployments** → **View Logs**
2. Vérifiez que `APP_KEY` est défini
3. Vérifiez la connexion PostgreSQL

### Frontend ne se connecte pas au Backend
1. Vérifiez `VITE_API_URL` sur Vercel
2. Vérifiez les CORS sur Railway
3. Ouvrez la console du navigateur (F12) pour voir les erreurs

### Base de données vide
```bash
# Connectez-vous à Railway CLI (optionnel)
railway login
railway link
railway run php artisan migrate:fresh --seed
```

---

##  Coûts

| Service | Tier Gratuit | Limites |
|---------|-------------|---------|
| **Railway** | 500h/mois | PostgreSQL 1GB, déploiements illimités |
| **Vercel** | Illimité | 100GB bande passante/mois |
| **TOTAL** | **0€/mois** | Parfait pour MVP et tests |

---

##  Félicitations !

Votre projet YOWL Community est maintenant **en ligne et gratuit** !

- **Backend** : https://votre-backend.up.railway.app
- **Frontend** : https://votre-app.vercel.app

---

## 📞 Support

Si vous avez des questions :
1. Consultez les logs sur Railway/Vercel
2. Vérifiez la documentation Laravel/Vue.js
3. Contactez l'équipe YOWL Community

**Fait avec ❤️ par l'équipe YOWL Community**
