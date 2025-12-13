# YOWL Community 🌐

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![Vue.js](https://img.shields.io/badge/Vue.js-3.x-green.svg)](https://vuejs.org/)
[![Laravel](https://img.shields.io/badge/Laravel-10.x-red.svg)](https://laravel.com/)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-3.x-38B2AC.svg)](https://tailwindcss.com/)

## 📋 Table des matières

- [À propos](#à-propos)
- [Fonctionnalités](#fonctionnalités)
- [Technologies](#technologies)
- [Prérequis](#prérequis)
- [Installation](#installation)
- [Configuration](#configuration)
- [Utilisation](#utilisation)
- [Architecture](#architecture)
- [Tests](#tests)
- [Déploiement](#déploiement)
- [KPI & Analytics](#kpi--analytics)
- [Roadmap](#roadmap)
- [Contribution](#contribution)
- [Équipe](#équipe)
- [License](#license)

## 🎯 À propos

**YOWL Community** est une plateforme web communautaire permettant aux utilisateurs de partager, commenter et réagir sur n'importe quel contenu trouvé sur internet. L'objectif est de créer un espace digital simple, moderne et interactif où chaque membre peut donner son avis, échanger avec les autres et suivre des tendances.

### Objectifs du projet

- ✅ Créer une plateforme intuitive pour publier et interagir autour de contenus web
- ✅ Favoriser la création d'une communauté active et engagée
- ✅ Mettre en place des outils de suivi et d'analyse (KPI, dashboard)
- ✅ Garantir la compatibilité multi-supports (desktop, mobile, tablette)

## ⚡ Fonctionnalités

### MVP (Version actuelle)

- 👤 **Inscription & Connexion** : Création de compte par email/téléphone avec authentification sécurisée
- 📝 **Publication d'avis** : Commenter n'importe quel contenu web externe
- 💬 **Commentaires & Réponses** : Système de discussion threadé
- 👍 **Réactions** : Like/Dislike sur les publications et commentaires
- 🔍 **Recherche & Filtres** : Recherche par mots-clés, popularité, date et thèmes
- 🔔 **Notifications** : Alertes en temps réel sur les interactions
- 👤 **Profils utilisateurs** : Avatar, pseudo, bio et préférences personnalisables

### V1 (Prochainement)

- 🔗 **Partage externe enrichi** : Intégration réseaux sociaux
- 🛡️ **Modération basique** : Signalement et gestion des contenus inappropriés
- 📊 **Dashboard personnel amélioré** : Statistiques individuelles d'engagement
- 🌍 **Multilingue** : Support FR/EN
- 🔔 **Notifications temps réel** : WebSocket pour notifications instantanées

### V2 (Futur)

- 🎯 **Recommandations personnalisées** : Algorithme de suggestions basé sur les préférences
- 🎮 **Gamification** : Badges, points et classements
- 🔗 **Partage multi-réseaux** : X (Twitter), LinkedIn, WhatsApp, etc.
- 🔌 **API tierces** : Intégration actualités et tendances sociales

## 🛠️ Technologies

### Frontend
- **Framework** : Vue.js 3.x
- **Styling** : Tailwind CSS 3.x
- **State Management** : Pinia
- **Routing** : Vue Router

### Backend
- **Framework** : Laravel 10.x
- **Langage** : PHP 8.2+
- **API** : RESTful API
- **Authentication** : Laravel Sanctum

### Base de données
- **SGBD** : MySQL 8.0+
- **Cache** : Redis (pour optimisation)

### Infrastructure
- **Hébergement** : AWS (Cloud)
- **CI/CD** : GitHub Actions
- **Monitoring** : CloudWatch

## 📦 Prérequis

- **PHP** >= 8.2
- **Composer** >= 2.5
- **Node.js** >= 18.x
- **npm** >= 9.x
- **MySQL** >= 8.0
- **Redis** (optionnel, recommandé pour la mise en cache)

## 🚀 Installation

### 1. Cloner le repository

```bash
git clone https://github.com/EpitechCodingAcademyPromo2025/C-DEV-160-COT-1-1-yowl-lauret.chacha.git
cd yowl-community
```

### 2. Installation du Backend (Laravel)

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

### 3. Configuration de la base de données

Éditez le fichier `.env` avec vos credentials :

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=yowl_community
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 4. Migration et Seeders

```bash
php artisan migrate
php artisan db:seed
```

### 5. Installation du Frontend (Vue.js)

```bash
cd ../frontend
npm install
cp .env.example .env
```

### 6. Configuration de l'API

Éditez le fichier `.env` du frontend :

```env
VITE_API_URL=http://localhost:8000/api
VITE_APP_NAME=YOWL Community
```

## ⚙️ Configuration

### Redis (Cache)

Pour activer Redis :

```bash
# Dans .env backend
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Authentification à deux facteurs (2FA)

La 2FA sera disponible dans une version ultérieure. Configuration à venir.

### RGPD & Sécurité

- Hashage des mots de passe : bcrypt
- Chiffrement des données sensibles : AES-256
- Conformité RGPD intégrée

## 🎮 Utilisation

### Démarrer le serveur de développement

#### Backend
```bash
cd backend
php artisan serve
# Serveur démarré sur http://localhost:8000
```

#### Frontend
```bash
cd frontend
npm run dev
# Application disponible sur http://localhost:5173
```

### Build pour production

```bash
cd frontend
npm run build
```

## 🏗️ Architecture

```
yowl-community/
├── backend/                 # API Laravel
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   └── Middleware/
|   |   |   └── Requests
│   │   ├── Mail/
│   │   └── Models/
|   |   └── Providers/
|   ├── bootstrap/
|   ├── config/
│   ├── database/
│   │   ├── factories/
│   │   └── migrations/
|   |   └── seeders/
│   ├── routes/
│   └── tests/
├── frontend/                # Application Vue.js
│   ├── src/
│   │   ├── components/
|   |   |    ├── cards/
|   |   |    └── layouts/
|   |   |    └── pages/
│   │   ├── router/
│   │   ├── services/
│   │   ├── stores/
│   │   └── views/
│   ├── public/
│   └── tests/
└── docs/                    # Documentation
```

### Entités principales

- **User** : Utilisateurs (membres, modérateurs, administrateurs)
- **Post** : Publications/avis sur contenus externes
- **Comment** : Commentaires et réponses
- **Reaction** : Likes/dislikes
- **Notification** : Alertes utilisateurs
- **Report** : Signalements de contenus

## 🧪 Tests

### Tests Backend (PHPUnit)

```bash
cd backend
php artisan test
```

### Tests Frontend (Vitest)

```bash
cd frontend
npm run test
```

### Recrutement de bêta-testeurs

- Échantillon : 5-10 utilisateurs (13-35 ans)
- Scénarios testés : inscription, commentaire, réaction, recherche
- Méthode : observation + questionnaire

## 🚢 Déploiement

### Variables d'environnement (Production)

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yowl.community

DB_CONNECTION=mysql
DB_HOST=your-rds-endpoint
DB_DATABASE=yowl_production

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
```

### Commandes de déploiement

```bash
# Backend
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Frontend
npm run build
```

## 📊 KPI & Analytics

### Dashboard KPI

Cinq indicateurs clés sont suivis :

1. **Utilisateurs actifs mensuels (MAU)** : Courbe de progression
2. **Commentaires moyens par utilisateur** : Histogramme ou jauge
3. **Taux d'engagement** : Diagramme circulaire (likes/partages/réponses)
4. **Temps moyen par session** : Graphique en barres
5. **Taux de rétention** : Courbes D1, D7, D30

### Exports

- Format CSV
- Format PDF
- API pour intégrations tierces

## 🗓️ Roadmap

| Phase | Durée | Statut |
|-------|-------|--------|
| Analyse des besoins | 24h |  Complété |
| Choix techniques & artistiques | 24h |  Complété |
| Développement MVP | 72h | En cours |
| Phase de test | 48h |  À venir |
| Livraison et mise en production | 24h |  À venir |

##  Contribution

Les contributions sont les bienvenues ! Veuillez suivre ces étapes :

1. Fork le projet
2. Créez votre branche feature (`git checkout -b feat/amazingFeature`)
3. Committez vos changements (`git commit -m 'feat: add this AmazingFeature'`)
4. Push vers la branche (`git push origin feat/amazingFeature`)
5. Ouvrez une Pull Request

### Guidelines

- Respecter les conventions de code (PSR-12 pour PHP, ESLint pour JS)
- Ajouter des tests pour les nouvelles fonctionnalités
- Mettre à jour la documentation si nécessaire

##  Équipe

- **Chef de projet** : Orlando DODAHO
- **Lead Frontend** : Lauret CHACHA
- **Lead Backend** : Gilchrist KANTE
- **Lead QA/Testeurs** : Nervely JEAN CHARLES

##  Budget

| Élément | Coût |
|---------|------|
| Développement logiciel | $7,000 |
| Infrastructure (serveurs) | $100/mois |
| Maintenance et support | $30/mois |
| Hébergement évolutif | +$200/mois |

## 📞 Support

- **Email** : support@yowl.community
- **Temps de réponse** : 24h
- **Garantie bugs** : 2 mois après livraison

## 📄 License

Ce projet est sous licence MIT. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

## 🔗 Liens utiles

- [Documentation complète](https://github.com/EpitechCodingAcademyPromo2025/C-DEV-160-COT-1-1-yowl-lauret.chacha/new/main?filename=README.md#%C3%A0-propos)
- [API Documentation](https://laravel.com/docs/12.x/eloquent-resources)
- [Figma - Maquettes](https://www.figma.com/design/OkaBhCHpHKQAkcJbVHUcub/Yowl?node-id=1-2&t=tfsTIMOekjqZwbp6-1)

---

**Note** : Capacité actuelle : 5,000 utilisateurs simultanés | Scalable jusqu'à 15,000 utilisateurs

Fait avec ❤️ par l'équipe YOWL Community
