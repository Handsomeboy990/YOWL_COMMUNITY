# YOWL Project Documentation

## Présentation
YOWL est une plateforme web permettant aux utilisateurs de publier, consulter et commenter des reviews techniques, avec gestion des tags, likes/dislikes, et authentification sécurisée.

---

## Structure du projet

- **backend/** : API Laravel (PHP)
    - Authentification, gestion des utilisateurs, reviews, tags, commentaires
    - Dossiers principaux :
        - `app/Http/Controllers/` : Contrôleurs API
        - `app/Models/` : Modèles Eloquent
        - `routes/` : Définition des routes API
        - `database/migrations/` : Schéma de la base de données
        - `tests/` : Tests unitaires et fonctionnels
    - Fichiers importants : `.env`, `composer.json`, `phpunit.xml`

- **frontend/yowl-frontend/** : Application Vue.js
    - Interface utilisateur, gestion des reviews, authentification, navigation
    - Dossiers principaux :
        - `src/` : Composants Vue, stores Pinia, pages
        - `public/` : Fichiers statiques
        - `e2e/` : Tests end-to-end
    - Fichiers importants : `package.json`, `vite.config.js`, `tailwind.config.js`

---

## Installation

### Backend (Laravel)
1. Installer les dépendances :
   ```sh
   composer install
   ```
2. Configurer `.env` (base de données, mail, etc.)
3. Générer la clé d'application :
   ```sh
   php artisan key:generate
   ```
4. Lancer les migrations :
   ```sh
   php artisan migrate
   ```
5. Lancer le serveur :
   ```sh
   php artisan serve
   ```

### Frontend (Vue.js)
1. Installer les dépendances :
   ```sh
   npm install
   ```
2. Lancer le serveur de développement :
   ```sh
   npm run dev
   ```

---

## Fonctionnalités principales

- Authentification JWT (inscription, login, vérification email)
- Création, modification, suppression de reviews
- Ajout de tags, likes/dislikes, commentaires
- Filtrage et recherche par tags
- Pagination des reviews
- Interface responsive et moderne (Tailwind CSS)

---

## API Endpoints (exemples)

- `POST /api/register` : Inscription
- `POST /api/login` : Connexion
- `GET /api/reviews` : Liste des reviews
- `POST /api/reviews` : Créer une review
- `PUT /api/reviews/{id}` : Modifier une review
- `DELETE /api/reviews/{id}` : Supprimer une review
- `GET /api/tags` : Liste des tags

---

## Tests
- Backend : PHPUnit
- Frontend : Vitest, Playwright (e2e)

---

## Bonnes pratiques
- Respect du pattern MVC côté Laravel
- Utilisation de Pinia pour la gestion d’état côté Vue
- Séparation claire entre API et frontend
- Variables sensibles dans `.env`

---

## Pour aller plus loin
- Ajouter l’upload d’images via URL (mode JSON)
- Mettre en place la vérification OTP par email
- Ajouter des notifications temps réel

---

## Auteurs
- Chacha Lauret
- ...

## Licence
MIT
