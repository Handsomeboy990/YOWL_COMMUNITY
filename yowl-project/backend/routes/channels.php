<?php

use Illuminate\Support\Facades\Broadcast;

/**
 * Canal privé des notifications d'un membre.
 *
 * L'autorisation est vérifiée à chaque abonnement : sans elle, n'importe qui
 * connaissant un identifiant écouterait les notifications de n'importe qui.
 */
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * Canal public d'un avis : les commentaires arrivent en direct sous la page
 * que les gens sont en train de lire. Public parce que l'avis l'est, et
 * qu'un avis dépublié n'est plus diffusé.
 */
Broadcast::channel('reviews.{reviewId}', function ($user, $reviewId) {
    return true;
});
