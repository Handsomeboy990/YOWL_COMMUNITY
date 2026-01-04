# 🔧 Fix pour l'erreur "could not find driver" sur Railway

## ❌ Problème

```
could not find driver (Connection: pgsql, SQL: select exists...)
```

Cette erreur signifie que PHP n'a pas les extensions PostgreSQL installées.

## ✅ Solution appliquée (VERSION 2 - Dockerfile)

Le Dockerfile a été complètement réécrit pour :
1. Utiliser **PHP 8.2-CLI** (au lieu de FPM)
2. Installer **libpq-dev** (librairies PostgreSQL)
3. Installer les extensions PHP : **pdo**, **pdo_pgsql**, **pgsql**
4. Utiliser `php artisan serve` directement (pas besoin de nginx)

### Fichiers supprimés :
- `railway.json` (conflictuel)
- `Procfile` (conflictuel)

Railway utilisera maintenant automatiquement le **Dockerfile**.

## 📝 Prochaines étapes

1. **Commiter la correction** :
   ```bash
   cd /home/lauret-chacha/Importants/yowl_community
   git add yowl-project/backend/nixpacks.toml
   git commit -m "fix: add PostgreSQL extensions for Railway deployment"
   git push origin main
   ```

2. **Redéployer sur Railway** :
   - Railway détectera automatiquement le push
   - Le build redémarrera avec les bonnes extensions
   - Le déploiement devrait maintenant réussir ✅

## 🔍 Vérification

Une fois redéployé, vérifiez les logs Railway. Vous devriez voir :
- ✅ Build successful
- ✅ Migrations exécutées
- ✅ Container running

## 🆘 Si le problème persiste

Vérifiez dans Railway que :
1. La base de données PostgreSQL est bien provisionnée
2. Les variables d'environnement sont correctes :
   - `DB_CONNECTION=pgsql`
   - `PGHOST`, `PGPORT`, `PGDATABASE`, `PGUSER`, `PGPASSWORD` sont injectées automatiquement

## 💡 Alternative : Utiliser SQLite en développement

Si vous voulez tester rapidement sans PostgreSQL :

```env
DB_CONNECTION=sqlite
DB_DATABASE=/app/storage/database.sqlite
```

Mais pour la production, PostgreSQL est recommandé.
