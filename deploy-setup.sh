#!/bin/bash

# Script de déploiement YOWL Community
# Ce script prépare votre projet pour le déploiement

echo " Préparation du déploiement YOWL Community..."

# Couleurs pour les messages
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Vérifier si nous sommes dans le bon répertoire
if [ ! -d "yowl-project" ]; then
    echo -e "${RED} Erreur : Ce script doit être exécuté depuis la racine du projet yowl_community${NC}"
    exit 1
fi

echo -e "${GREEN}✓${NC} Répertoire correct"

# 1. Vérifier Git
echo ""
echo " Étape 1/5 : Vérification de Git..."
if ! command -v git &> /dev/null; then
    echo -e "${RED} Git n'est pas installé${NC}"
    exit 1
fi
echo -e "${GREEN}✓${NC} Git installé"

# 2. Générer APP_KEY si nécessaire
echo ""
echo " Étape 2/5 : Génération de APP_KEY..."
cd yowl-project/backend

if [ -f "artisan" ]; then
    echo "Génération de la clé d'application..."
    APP_KEY=$(php artisan key:generate --show)
    echo -e "${GREEN}✓${NC} APP_KEY générée : ${YELLOW}${APP_KEY}${NC}"
    echo ""
    echo -e "${YELLOW}  IMPORTANT : Copiez cette clé pour Railway !${NC}"
    echo "APP_KEY=${APP_KEY}"
    echo ""
else
    echo -e "${RED} Fichier artisan non trouvé${NC}"
fi

cd ../..

# 3. Vérifier les fichiers de configuration
echo ""
echo " Étape 3/5 : Vérification des fichiers de configuration..."

files=(
    "yowl-project/backend/railway.json"
    "yowl-project/backend/Procfile"
    "yowl-project/backend/nixpacks.toml"
    "yowl-project/backend/.env.production"
    "yowl-project/frontend/yowl-frontend/vercel.json"
    "yowl-project/frontend/yowl-frontend/.env.production"
)

for file in "${files[@]}"; do
    if [ -f "$file" ]; then
        echo -e "${GREEN}✓${NC} $file"
    else
        echo -e "${RED}${NC} $file manquant"
    fi
done

# 4. Vérifier le statut Git
echo ""
echo " Étape 4/5 : Statut Git..."
git status --short

# 5. Instructions finales
echo ""
echo " Étape 5/5 : Préparation terminée !"
echo ""
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN} Votre projet est prêt pour le déploiement !${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo " Prochaines étapes :"
echo ""
echo "1️⃣  Commitez les nouveaux fichiers :"
echo -e "   ${YELLOW}git add .${NC}"
echo -e "   ${YELLOW}git commit -m \"feat: add deployment configuration\"${NC}"
echo -e "   ${YELLOW}git push origin main${NC}"
echo ""
echo "2️⃣  Créez un compte sur Railway :"
echo -e "   ${YELLOW}https://railway.app${NC}"
echo ""
echo "3️⃣  Créez un compte sur Vercel :"
echo -e "   ${YELLOW}https://vercel.com${NC}"
echo ""
echo "4️⃣  Suivez le guide de déploiement :"
echo -e "   ${YELLOW}cat DEPLOYMENT_GUIDE.md${NC}"
echo ""
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo "💡 Astuce : Lisez le fichier DEPLOYMENT_GUIDE.md pour des instructions détaillées"
echo ""
