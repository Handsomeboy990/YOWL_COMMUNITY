# 🔧 Fix pour l'erreur "could not find driver" sur Railway

## ❌ Problème

```
could not find driver (Connection: pgsql, SQL: select exists...)
```

Cette erreur signifie que PHP n'a pas les extensions PostgreSQL installées.

## ✅ Solution appliquée

Le fichier `nixpacks.toml` a été mis à jour pour inclure les extensions PostgreSQL :

```toml
[phases.setup]
nixPkgs = [
  "php82",
  "php82Extensions.pdo",           # Extension PDO
  "php82Extensions.pdo_pgsql",     # Driver PDO PostgreSQL
  "php82Extensions.pgsql",         # Extension PostgreSQL
  "php82Packages.composer"
]
```

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
