import { computed, ref } from 'vue';
import { defineStore } from 'pinia';
import api from '@/services/apiService';

/**
 * Historique des notifications de l'utilisateur connecte.
 *
 * Le compteur non lu est rafraichi seul, sans recharger la liste, pour que la
 * pastille de la cloche reste juste sans cout reseau notable.
 */
export const useNotificationStore = defineStore('notifications', () => {
  const items = ref([]);
  const unreadCount = ref(0);
  const loading = ref(false);
  const error = ref(null);
  const loaded = ref(false);

  const hasUnread = computed(() => unreadCount.value > 0);

  async function fetchNotifications() {
    loading.value = true;
    error.value = null;
    try {
      const response = await api.get('/notifications');
      items.value = response.data.data.data || [];
      unreadCount.value = response.data.unread_count ?? 0;
      loaded.value = true;
    } catch (err) {
      error.value = err.response?.data?.message || 'Impossible de charger les notifications.';
    } finally {
      loading.value = false;
    }
  }

  async function fetchUnreadCount() {
    try {
      const response = await api.get('/notifications/unread-count');
      unreadCount.value = response.data.data.unread_count ?? 0;
    } catch {
      // Le compteur reste sur sa derniere valeur connue.
    }
  }

  async function markAsRead(id) {
    const target = items.value.find((item) => item.id === id);
    if (!target || target.read_at) return;

    try {
      const response = await api.patch(`/notifications/${id}/read`);
      target.read_at = new Date().toISOString();
      unreadCount.value = response.data.data.unread_count ?? Math.max(0, unreadCount.value - 1);
    } catch (err) {
      error.value = err.response?.data?.message || 'Impossible de marquer la notification comme lue.';
    }
  }

  async function markAllAsRead() {
    if (!unreadCount.value) return;

    try {
      await api.patch('/notifications/read-all');
      const now = new Date().toISOString();
      items.value.forEach((item) => {
        if (!item.read_at) item.read_at = now;
      });
      unreadCount.value = 0;
    } catch (err) {
      error.value = err.response?.data?.message || 'Impossible de tout marquer comme lu.';
    }
  }

  async function remove(id) {
    const target = items.value.find((item) => item.id === id);
    try {
      await api.delete(`/notifications/${id}`);
      items.value = items.value.filter((item) => item.id !== id);
      if (target && !target.read_at) {
        unreadCount.value = Math.max(0, unreadCount.value - 1);
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Impossible de supprimer la notification.';
    }
  }

  function reset() {
    items.value = [];
    unreadCount.value = 0;
    loaded.value = false;
    error.value = null;
  }

  return {
    items,
    unreadCount,
    hasUnread,
    loading,
    error,
    loaded,
    fetchNotifications,
    fetchUnreadCount,
    markAsRead,
    markAllAsRead,
    remove,
    reset,
  };
});
