# 🌐 URLs de Production YOWL Community

Remplissez ce fichier après avoir déployé sur Railway et Vercel.

## Backend (Railway)

**URL de production** : https://________________.up.railway.app

**API Health Check** : https://________________.up.railway.app/api/health

**Database** : PostgreSQL sur Railway (géré automatiquement)

---

## Frontend (Vercel)

**URL de production** : https://________________.vercel.app

**Domaine personnalisé** (optionnel) : https://________________

---

## Informations importantes

### APP_KEY (Backend)
```
base64:bxkyHpkrDV3T7PLzE9ikmPES8J+nTABk6bolPBsdgts=
```
 Cette clé a été générée automatiquement. Copiez-la dans Railway !

### Variables d'environnement à configurer

#### Railway (Backend)
-  APP_KEY (voir ci-dessus)
-  APP_ENV=production
-  APP_DEBUG=false
-  DB_CONNECTION=pgsql (PostgreSQL)
-  SANCTUM_STATEFUL_DOMAINS (ajouter après déploiement Vercel)
-  FRONTEND_URL (ajouter après déploiement Vercel)

#### Vercel (Frontend)
-  VITE_API_URL (URL Railway)
-  VITE_APP_NAME=YOWL Community
-  NODE_ENV=production

---

##  Monitoring

### Railway Dashboard
https://railway.app/dashboard

### Vercel Dashboard
https://vercel.com/dashboard

---

## Historique des déploiements

| Date | Version | Déployé par | Notes |
|------|---------|-------------|-------|
| AAAA-MM-JJ | v1.0.0 | Votre nom | Déploiement initial |
|  |  |  |  |
|  |  |  |  |

---

##  Prochaines étapes

- [ ] Configurer un domaine personnalisé sur Vercel
- [ ] Configurer les emails (SMTP) sur Railway
- [ ] Activer les backups automatiques de la base de données
- [ ] Mettre en place le monitoring (Sentry, LogRocket)
- [ ] Configurer le cache Redis (Railway addon)

---

**Dernière mise à jour** : AAAA-MM-JJ
