import { ref } from 'vue';
import api from '@/services/apiService';

// Convertit la clé VAPID base64url en Uint8Array pour PushManager
function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
  const rawData = window.atob(base64);
  return Uint8Array.from([...rawData].map((char) => char.charCodeAt(0)));
}

const isSubscribed = ref(false);
const isBusy = ref(false);

export function usePushNotifications() {
  const isSupported =
    typeof window !== 'undefined' &&
    'serviceWorker' in navigator &&
    'PushManager' in window &&
    'Notification' in window;

  const refreshState = async () => {
    if (!isSupported) return;
    try {
      const registration = await navigator.serviceWorker.ready;
      const subscription = await registration.pushManager.getSubscription();
      isSubscribed.value = Boolean(subscription) && Notification.permission === 'granted';
    } catch {
      isSubscribed.value = false;
    }
  };

  const subscribe = async () => {
    if (!isSupported || isBusy.value) return false;
    isBusy.value = true;
    try {
      const permission = await Notification.requestPermission();
      if (permission !== 'granted') return false;

      const registration = await navigator.serviceWorker.ready;
      let subscription = await registration.pushManager.getSubscription();
      if (!subscription) {
        subscription = await registration.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: urlBase64ToUint8Array(import.meta.env.VITE_VAPID_PUBLIC_KEY),
        });
      }

      await api.post('/push/subscribe', subscription.toJSON());
      isSubscribed.value = true;
      return true;
    } catch {
      return false;
    } finally {
      isBusy.value = false;
    }
  };

  const unsubscribe = async () => {
    if (!isSupported || isBusy.value) return false;
    isBusy.value = true;
    try {
      const registration = await navigator.serviceWorker.ready;
      const subscription = await registration.pushManager.getSubscription();
      if (subscription) {
        await api.post('/push/unsubscribe', { endpoint: subscription.endpoint });
        await subscription.unsubscribe();
      }
      isSubscribed.value = false;
      return true;
    } catch {
      return false;
    } finally {
      isBusy.value = false;
    }
  };

  return { isSupported, isSubscribed, isBusy, refreshState, subscribe, unsubscribe };
}
