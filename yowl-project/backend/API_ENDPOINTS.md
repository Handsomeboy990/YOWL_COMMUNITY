# YOWL Backend API Endpoints

Ce document liste tous les endpoints disponibles dans l'API backend YOWL, avec leur usage principal.

---

## Authentification

- `POST /login` : Connexion utilisateur
	**Payload :**
	```json
	{
		"email": "user@example.com",
		"password": "secret"
	}
	```
	**Réponse :**
	```json
	{
		"success": true,
		"token": "...",
		"user": { ... }
	}
	```
- `POST /register` : Inscription utilisateur
	**Payload :**
	```json
	{
		"username": "john",
		"fullname": "John Doe",
		"email": "john@example.com",
		"password": "secret"
	}
	```
	**Réponse :**
	```json
	{
		"success": true,
		"user": { ... }
	}
	```
- `POST /logout` : Déconnexion
	**Réponse :**
	```json
	{
		"success": true
	}
	```

---

## Utilisateurs

- `GET /users/{user}` : Récupérer les informations d'un utilisateur
	**Réponse :**
	```json
	{
		"id": 1,
		"username": "john",
		"fullname": "John Doe",
		"email": "john@example.com",
		"roles": ["admin"],
		"is_active": true,
		"created_at": "2025-10-09T12:00:00Z"
	}
	```
- `POST /users/{user}` : Mettre à jour le profil utilisateur
	**Payload :**
	```json
	{
		"username": "johnny",
		"fullname": "Johnny Doe",
		"email": "johnny@example.com"
	}
	```
	**Réponse :**
	```json
	{
		"success": true,
		"user": { ... }
	}
	```
- `DELETE /users/{user}` : Supprimer un utilisateur
	**Réponse :**
	```json
	{
		"success": true
	}
	```
- `GET /users/{user}/activity` : Récupérer l'activité (reviews, commentaires, réactions)
	**Réponse :**
	```json
	[
		{
			"text": "You posted a review: ...",
			"created_at": "2025-10-09T12:00:00Z",
			"type": "review"
		},
		{
			"text": "You liked a review: ...",
			"created_at": "2025-10-09T13:00:00Z",
			"type": "reaction"
		}
	]
	```

---

## Administration (rôle admin requis)

- `GET /admin/stats` : Statistiques globales
	**Réponse :**
	```json
	{
		"success": true,
		"data": {
			"users": 10,
			"reviews": 20,
			"comments": 30,
			"latest_reviews": [
				{ "id": 1, "content": "...", "created_at": "..." }
			]
		}
	}
	```
- `GET /admin/users` : Liste paginée des utilisateurs (avec rôles)
	**Réponse :**
	```json
	{
		"success": true,
		"data": {
			"current_page": 1,
			"data": [
				{
					"id": 1,
					"username": "john",
					"roles": ["admin"],
					"is_active": true
				}
			],
			"last_page": 2
		}
	}
	```
- `PATCH /admin/users/{user}/ban` : Bannir un utilisateur
	**Réponse :**
	```json
	{
		"success": true,
		"message": "User banned"
	}
	```
- `PATCH /admin/users/{user}/unban` : Débannir un utilisateur
	**Réponse :**
	```json
	{
		"success": true,
		"message": "User unbanned"
	}
	```
- `PATCH /admin/users/{user}/role` : Changer le rôle (admin/client)
	**Payload :**
	```json
	{
		"role": "admin"
	}
	```
	**Réponse :**
	```json
	{
		"success": true,
		"message": "Role updated to admin"
	}
	```
- `DELETE /admin/users/{user}` : Supprimer un utilisateur
	**Réponse :**
	```json
	{
		"success": true,
		"message": "User deleted"
	}
	```

---

## Reviews

- `GET /admin/reviews` : Liste paginée des reviews
	**Réponse :**
	```json
	{
		"success": true,
		"data": {
			"current_page": 1,
			"data": [
				{
					"id": 1,
					"content": "...",
					"user": { "id": 1, "username": "john" }
				}
			],
			"last_page": 2
		}
	}
	```
- `PATCH /admin/reviews/{review}/publish` : Publier un review
	**Réponse :**
	```json
	{
		"success": true,
		"message": "Review published"
	}
	```
- `PATCH /admin/reviews/{review}/unpublish` : Dépublier un review
	**Réponse :**
	```json
	{
		"success": true,
		"message": "Review unpublished"
	}
	```
- `DELETE /admin/reviews/{review}` : Supprimer un review
	**Réponse :**
	```json
	{
		"success": true,
		"message": "Review deleted"
	}
	```

---

## Commentaires

- `GET /admin/comments` : Liste paginée des commentaires
	**Réponse :**
	```json
	{
		"success": true,
		"data": {
			"current_page": 1,
			"data": [ { "id": 1, "content": "..." } ],
			"last_page": 2
		}
	}
	```
- `DELETE /admin/comments/{comment}` : Supprimer un commentaire
	**Réponse :**
	```json
	{
		"success": true,
		"message": "Comment deleted"
	}
	```

---

## Réactions

- `POST /reviews/{review}/reactions` : Ajouter une réaction (like/dislike) à un review
	**Payload :**
	```json
	{
		"reaction": "like"
	}
	```
	**Réponse :**
	```json
	{
		"success": true,
		"message": "Reaction added"
	}
	```
- `GET /reviews/{review}/reactions` : Récupérer les réactions d'un review
	**Réponse :**
	```json
	[
		{
			"id": 1,
			"user_id": 2,
			"reaction": "like",
			"created_at": "2025-10-09T14:00:00Z"
		}
	]
	```

---

## Sécurité

- Toutes les routes sensibles sont protégées par `auth:sanctum`
- Les routes admin nécessitent le rôle `admin`

---

Pour plus de détails sur les payloads, réponses ou la structure, voir le README principal ou la documentation du projet.
