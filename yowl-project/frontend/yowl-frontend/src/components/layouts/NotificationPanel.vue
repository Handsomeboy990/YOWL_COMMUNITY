<template>
  <div class="w-88 max-w-[calc(100vw-2rem)] bg-white rounded-2xl shadow-xl shadow-blue-night/10 border border-gray-100 overflow-hidden">
    <!-- En-tete -->
    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
      <h2 class="font-semibold text-blue-night text-sm">Notifications</h2>
      <button v-if="store.hasUnread" type="button"
        class="text-xs text-orange-text hover:underline cursor-pointer"
        @click="store.markAllAsRead()">
        Tout marquer comme lu
      </button>
    </div>

    <!-- Reglage du push -->
    <div v-if="push.isSupported" class="flex items-center justify-between gap-3 px-4 py-2.5 bg-gray-50 border-b border-gray-100">
      <span class="text-xs text-gray-500">
        {{ push.isSubscribed.value ? 'Notifications du navigateur activées' : 'Recevoir les alertes du navigateur' }}
      </span>
      <button type="button"
        class="shrink-0 text-xs font-medium px-2.5 py-1 rounded-full cursor-pointer transition-colors"
        :class="push.isSubscribed.value
          ? 'bg-orange-primary/10 text-orange-text hover:bg-orange-primary/20'
          : 'bg-blue-night text-white hover:bg-blue-night/90'"
        @click="$emit('toggle-push')">
        {{ push.isSubscribed.value ? 'Désactiver' : 'Activer' }}
      </button>
    </div>

    <!-- Liste -->
    <div class="max-h-96 overflow-y-auto">
      <!-- Chargement -->
      <div v-if="store.loading && !store.items.length" class="p-4 space-y-3">
        <div v-for="n in 3" :key="n" class="flex gap-3 animate-pulse">
          <div class="w-9 h-9 rounded-full bg-gray-200 shrink-0"></div>
          <div class="flex-1 space-y-2">
            <div class="h-3 bg-gray-200 rounded w-3/4"></div>
            <div class="h-3 bg-gray-100 rounded w-1/2"></div>
          </div>
        </div>
      </div>

      <!-- Erreur -->
      <div v-else-if="store.error" class="px-4 py-8 text-center">
        <Icon name="triangle-exclamation" :size="26" class="text-2xl text-gray-400" />
        <p class="mt-2 text-sm text-gray-500">{{ store.error }}</p>
        <button type="button" class="mt-3 text-xs text-orange-text hover:underline cursor-pointer"
          @click="store.fetchNotifications()">
          Réessayer
        </button>
      </div>

      <!-- Vide -->
      <div v-else-if="!store.items.length" class="px-4 py-10 text-center">
        <Icon name="bell-slash" :size="32" class="text-3xl text-gray-200" aria-hidden="true" />
        <p class="mt-3 text-sm font-medium text-blue-night">Aucune notification</p>
        <p class="mt-1 text-xs text-gray-500">
          Les réactions et réponses à tes reviews apparaîtront ici.
        </p>
      </div>

      <!-- Notifications -->
      <ul v-else class="divide-y divide-gray-50">
        <li v-for="notification in store.items" :key="notification.id">
          <div class="group relative flex gap-3 px-4 py-3 transition-colors"
            :class="notification.read_at ? 'hover:bg-gray-50' : 'bg-orange-primary/5 hover:bg-orange-primary/10'">
            <button type="button" class="flex gap-3 flex-1 text-left cursor-pointer"
              @click="open(notification)">
              <span class="relative shrink-0">
                <img v-if="notification.data?.actor?.picture" class="w-9 h-9 rounded-full object-cover"
                  :src="getStorageUrl(notification.data.actor.picture)" alt="">
                <span v-else class="w-9 h-9 rounded-full bg-blue-night grid place-items-center text-white text-xs font-bold">
                  {{ initials(notification.data?.actor?.username) }}
                </span>
                <span class="absolute -bottom-0.5 -right-0.5 w-4.5 h-4.5 rounded-full grid place-items-center text-[9px] text-white"
                  :class="iconBackground(notification.data?.type)">
                  <Icon :name="icon(notification.data?.type)" />
                </span>
              </span>

              <span class="flex-1 min-w-0">
                <span class="block text-sm text-blue-night leading-snug">
                  {{ notification.data?.body }}
                </span>
                <span class="block mt-0.5 text-[11px] text-gray-500">
                  {{ relativeDate(notification.created_at) }}
                </span>
              </span>

              <span v-if="!notification.read_at"
                class="shrink-0 self-center w-2 h-2 rounded-full bg-orange-primary"
                aria-label="Non lue"></span>
            </button>

            <button type="button"
              class="absolute top-2 right-2 w-6 h-6 rounded-full grid place-items-center text-gray-300 opacity-0 group-hover:opacity-100 hover:text-red-500 hover:bg-red-50 transition-all cursor-pointer"
              aria-label="Supprimer la notification"
              @click.stop="store.remove(notification.id)">
              <Icon name="xmark" :size="14" class="text-xs" />
            </button>
          </div>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import { useNotificationStore } from '@/stores/notification';
import { getStorageUrl } from '@/config';

import Icon from '@/components/ui/Icon.vue';
defineProps({
  push: { type: Object, required: true },
});

const emit = defineEmits(['close', 'toggle-push']);

const store = useNotificationStore();
const router = useRouter();

/**
 * Ouvre la cible de la notification et la marque lue au passage.
 */
async function open(notification) {
  await store.markAsRead(notification.id);
  emit('close');
  const url = notification.data?.url;
  if (url) router.push(url);
}

function initials(username) {
  if (!username) return '?';
  return username.slice(0, 2).toUpperCase();
}

function icon(type) {
  switch (type) {
    case 'comment':
      return 'comment';
    case 'reply':
      return 'reply';
    case 'review_like':
    case 'comment_like':
      return 'heart';
    default:
      return 'bell';
  }
}

function iconBackground(type) {
  switch (type) {
    case 'review_like':
    case 'comment_like':
      return 'bg-red-500';
    case 'reply':
      return 'bg-blue-500';
    default:
      return 'bg-orange-primary';
  }
}

/**
 * Date relative courte, en francais, sans dependance supplementaire.
 */
function relativeDate(value) {
  if (!value) return '';
  const then = new Date(value).getTime();
  const seconds = Math.round((Date.now() - then) / 1000);

  if (seconds < 60) return "a l'instant";
  const minutes = Math.round(seconds / 60);
  if (minutes < 60) return `il y a ${minutes} min`;
  const hours = Math.round(minutes / 60);
  if (hours < 24) return `il y a ${hours} h`;
  const days = Math.round(hours / 24);
  if (days < 7) return `il y a ${days} j`;
  return new Date(value).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
}
</script>
