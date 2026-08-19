import { onBeforeUnmount, onMounted } from 'vue';
import api from '@/services/apiService';
import { useUserStore } from '@/stores/user';

/** Cadence du signal. Le serveur n'en retient qu'un par minute de toute façon. */
const INTERVALLE_MS = 60000;

/**
 * Signale que l'onglet est ouvert et regardé.
 *
 * C'est la seule source du temps par session : les horodatages de publication
 * disent quand quelqu'un a écrit, jamais combien de temps il est resté. Le
 * signal s'arrête dès que l'onglet passe en arrière-plan, sinon un onglet
 * oublié compterait des heures de présence que personne n'a passées.
 */
export function usePresence() {
  const userStore = useUserStore();
  let timer = null;

  const envoyer = () => {
    if (!userStore.isAuthenticated || document.visibilityState !== 'visible') return;
    // L'echec est sans consequence : un point de mesure manquant vaut mieux
    // qu'une erreur affichee pour une donnee que le membre ne lit pas.
    api.post('/presence').catch(() => {});
  };

  const surVisibilite = () => {
    if (document.visibilityState === 'visible') {
      envoyer();
      if (!timer) timer = setInterval(envoyer, INTERVALLE_MS);
    } else if (timer) {
      clearInterval(timer);
      timer = null;
    }
  };

  onMounted(() => {
    document.addEventListener('visibilitychange', surVisibilite);
    surVisibilite();
  });

  onBeforeUnmount(() => {
    document.removeEventListener('visibilitychange', surVisibilite);
    if (timer) clearInterval(timer);
  });
}
