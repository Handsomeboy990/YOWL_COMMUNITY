# Fix pour l'erreur Vercel "vite: command not found"

## Problème

```
sh: line 1: vite: command not found
Error: Command "npm run build" exited with 127
```

## Cause

Vite est dans `devDependencies` mais Vercel n'installe que les `dependencies` par défaut en production.

## Solution appliquée

Le fichier `vercel.json` a été mis à jour :

```json
{
  "installCommand": "npm install --include=dev"
}
```

Cela force Vercel à installer les `devDependencies` (incluant Vite) lors du build.

## Prochaines étapes

```bash
cd /home/lauret-chacha/Importants/yowl_community
git add yowl-project/frontend/yowl-frontend/vercel.json
git add VERCEL_FIX.md
git commit -m "fix: install devDependencies on Vercel build"
git push origin main
```

Vercel redéploiera automatiquement et le build devrait réussir !

## Vérification

Dans les logs Vercel, vous devriez voir :
- OK `npm install --include=dev` (au lieu de `npm install`)
- OK Vite installé dans les dépendances
- OK `vite build` exécuté avec succès
- OK Build completed successfully

## Alternative

Si le problème persiste, vous pouvez aussi déplacer Vite dans `dependencies` :

```bash
cd yowl-project/frontend/yowl-frontend
npm install --save-prod vite @vitejs/plugin-vue
```

Mais la solution actuelle est préférable car elle garde une séparation claire entre deps de dev et de prod.
