# YOWL Backend API

This document summarizes the REST API exposed by the YOWL Community backend.

## Interactive documentation (Swagger)

The API is fully documented with OpenAPI 3.1, generated automatically from the codebase by [Scramble](https://scramble.dedoc.co/).

- Interactive UI: `GET /docs/api` (available in local and development environments)
- OpenAPI JSON: `GET /docs/api.json`
- Static export committed to the repository: [docs/openapi.json](docs/openapi.json)

To regenerate the static export after changing routes, validation rules or responses:

```bash
php artisan scramble:export --path=docs/openapi.json
```

## Authentication

The API uses Laravel Sanctum bearer tokens. Call `POST /api/login` with valid credentials and pass the returned token in the `Authorization: Bearer <token>` header for protected endpoints.

## Endpoint overview

All routes are prefixed with `/api`.

### Health

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/health` | Health check used by deployment platforms |

### Authentication and account

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/register` | guest | Register (username, fullname, email, password + confirmation, birthdate 13-35). Sends a 6-digit OTP by email |
| POST | `/email/otp/resend` | guest, throttled | Resend the verification code |
| POST | `/email/otp/verify` | guest, throttled | Verify the email with the 6-digit code |
| POST | `/login` | guest | Log in, returns `{ token, user }` |
| POST | `/logout` | token | Revoke the current token |
| POST | `/forgot-password` | guest | Send a password reset link |
| POST | `/reset-password` | guest | Reset the password with a valid token |
| GET | `/user` | token | Current authenticated user with roles |

### Reviews

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/reviews` | optional | Paginated list. Supports `search`, `tags`, `noAnswers`, `noViews`, `noLikes`, `sort` (`newest`, `older`, `highestLike`), `page`. Includes `user_reaction` when authenticated |
| GET | `/reviews/{id}` | optional | Single review, increments the view counter |
| POST | `/reviews` | token | Create a review (`content`, optional `link`, `medias[]` image files, `tags[]`) |
| POST | `/reviews/{id}` | token, owner | Update a review (multipart, `existingMedias[]` keeps previous files) |
| DELETE | `/reviews/{id}` | token, owner | Delete a review and its media files |
| POST | `/reviews/{id}/react` | token | Toggle a `like` or `dislike`, returns counters and `user_reaction` |

### Comments

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/comments` | public | Paginated list with author, review and children |
| GET | `/comments/{id}` | public | Single comment |
| POST | `/comments` | token | Create a comment (`review_id`, optional `parent_id`, `content`) |
| PATCH | `/comments/{id}` | token, owner | Update a comment |
| DELETE | `/comments/{id}` | token, owner | Delete a comment |
| POST | `/comments/{id}/react` | token | Toggle a `like` or `dislike`, returns counters and `user_reaction` |

### Tags and KPI

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/tags` | public | Tag list, supports `search` |
| GET | `/kpi` | public | Community metrics (users, reviews, comments, age ranges, daily average) |

### Users

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/users/{id}` | token | Public profile (full profile when requesting your own) |
| POST | `/users/{id}` | token, self | Update profile (multipart: username, email, picture, password) |
| DELETE | `/users/{id}` | token, self | Deactivate the account and revoke all tokens |
| GET | `/users/{id}/activity` | token, self | Recent activity (reviews, comments, reactions) |

### Push notifications

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/push/subscribe` | token | Register a Web Push subscription (endpoint + p256dh/auth keys) |
| POST | `/push/unsubscribe` | token | Remove a Web Push subscription |

Notifications are sent when someone comments on your review, replies to your comment or likes your review. Configure `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY` and `VAPID_SUBJECT` on the backend and `VITE_VAPID_PUBLIC_KEY` on the frontend.

### Administration (role `admin` required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/stats` | Global statistics (users, reviews, comments, tags, latest reviews) |
| GET | `/admin/users` | Paginated members list with roles, supports `search` |
| PATCH | `/admin/users/{id}/role` | Change role (`admin` or `client`) |
| PATCH | `/admin/users/{id}/ban` | Ban a member and revoke all their tokens |
| PATCH | `/admin/users/{id}/unban` | Reinstate a member |
| GET | `/admin/reviews` | Paginated reviews list, supports `search` |
| PATCH | `/admin/reviews/{id}/publish` | Publish a review |
| PATCH | `/admin/reviews/{id}/unpublish` | Unpublish a review (hidden from public feed) |
| DELETE | `/admin/reviews/{id}` | Delete a review and its media files |
| GET | `/admin/comments` | Paginated comments list, supports `search` |
| DELETE | `/admin/comments/{id}` | Delete a comment |

## Response conventions

- Successful responses are wrapped as `{ "success": true, "data": ..., "message": "..." }` (paginated payloads keep the Laravel paginator shape inside `data`).
- Validation errors return HTTP 422 with `{ "success": false, "message": "...", "error": { field: [messages] } }`.
- Authorization failures return HTTP 403, missing resources HTTP 404.
- Public payloads only expose safe user fields (`id`, `username`, `picture`).
