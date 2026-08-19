import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { useUserStore } from '@/stores/user';
import { useNotificationStore } from '@/stores/notification';
import { config } from '@/config';

/**
 * Connexion temps réel aux notifications.
 *
 * La pastille de la cloche se rafraîchissait par un appel toutes les soixante
 * secondes, par membre connecté. Une connexion persistante remplace ce
 * sondage : le compteur bouge quand quelque chose arrive, et l'API ne reçoit
 * plus une requête par minute et par onglet ouvert.
 *
 * Tout est facultatif : sans clé configurée, l'application fonctionne
 * exactement comme avant, avec le sondage en secours.
 */
let echo = null;

export function isRealtimeConfigured() {
  return Boolean(import.meta.env.VITE_REVERB_APP_KEY);
}

export function connectRealtime() {
  if (echo || !isRealtimeConfigured()) return null;

  const userStore = useUserStore();
  if (!userStore.isAuthenticated) return null;

  window.Pusher = Pusher;

  echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
    wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 443),
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: `${config.apiBaseUrl}/broadcasting/auth`,
    auth: {
      headers: { Authorization: `Bearer ${userStore.token}` },
    },
  });

  const notificationStore = useNotificationStore();

  echo.private(`App.Models.User.${userStore.user.id}`).notification(() => {
    // On recharge le compteur plutôt que d'incrémenter à l'aveugle : deux
    // onglets ouverts ne doivent pas compter deux fois.
    notificationStore.fetchUnreadCount();
    if (notificationStore.loaded) notificationStore.fetchNotifications();
  });

  return echo;
}

export function disconnectRealtime() {
  if (!echo) return;
  echo.disconnect();
  echo = null;
}
