#!/usr/bin/env bash
#
# Prépare les paquets à déposer sur les magasins.
#
# Chrome, Edge et Opera acceptent le même fichier. Firefox refuse la clé
# background.service_worker et exige background.scripts : on lui produit un
# manifeste à part plutôt que de maintenir deux dossiers.
#
#   ./build.sh              construit les deux paquets
#   ./build.sh 3.2.0        change aussi le numéro de version au passage

set -euo pipefail

dossier="$(cd "$(dirname "$0")" && pwd)"
sortie="$dossier/dist"
version="${1:-}"

cd "$dossier"

if [ -n "$version" ]; then
  python3 - "$version" <<'PY'
import json, sys, collections, pathlib
p = pathlib.Path('manifest.json')
m = json.load(open(p), object_pairs_hook=collections.OrderedDict)
m['version'] = sys.argv[1]
with open(p, 'w') as f:
    json.dump(m, f, ensure_ascii=False, indent=2)
    f.write('\n')
print('version portée à', sys.argv[1])
PY
fi

version=$(python3 -c "import json; print(json.load(open('manifest.json'))['version'])")

rm -rf "$sortie"
mkdir -p "$sortie/chrome" "$sortie/firefox"

fichiers=(manifest.json background.js browser.js config.js
          popup.html popup.js popup.css options.html options.js icons)

for cible in chrome firefox; do
  for fichier in "${fichiers[@]}"; do
    cp -r "$fichier" "$sortie/$cible/"
  done
done

# Chrome ignore background.scripts, mais l'analyseur du magasin le signale.
python3 - <<'PY'
import json, collections, pathlib
p = pathlib.Path('dist/chrome/manifest.json')
m = json.load(open(p), object_pairs_hook=collections.OrderedDict)
m['background'].pop('scripts', None)
m.pop('browser_specific_settings', None)
with open(p, 'w') as f:
    json.dump(m, f, ensure_ascii=False, indent=2)
    f.write('\n')
PY

# Firefox n'accepte pas service_worker et refuse le paquet s'il est présent.
python3 - <<'PY'
import json, collections, pathlib
p = pathlib.Path('dist/firefox/manifest.json')
m = json.load(open(p), object_pairs_hook=collections.OrderedDict)
m['background'].pop('service_worker', None)
m['background'].pop('type', None)
with open(p, 'w') as f:
    json.dump(m, f, ensure_ascii=False, indent=2)
    f.write('\n')
PY

for cible in chrome firefox; do
  (cd "$sortie/$cible" && zip -qr "../yowl-$cible-$version.zip" .)
  echo "  $sortie/yowl-$cible-$version.zip"
done

echo
echo "Paquets prêts en version $version."
echo "  chrome  : Chrome Web Store, Edge Add-ons, Opera"
echo "  firefox : addons.mozilla.org"
