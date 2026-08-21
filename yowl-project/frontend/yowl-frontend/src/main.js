import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate'

import App from './App.vue'
import router from './router'
import { i18n } from './i18n'

const pinia = createPinia()
pinia.use(piniaPluginPersistedstate)
import { useUserStore } from './stores/user'

const app = createApp(App)

app.use(pinia)
app.use(router)
app.use(i18n)

app.mount('#app')

/**
 * Retire l'ecran de demarrage declare dans index.html.
 *
 * Le montage rend la premiere vue, mais le navigateur n'a pas encore peint :
 * effacer l'ecran dans la foulee laisse voir une image intermediaire. Deux
 * images successives suffisent a ce que la vue soit a l'ecran.
 *
 * router.isReady() attend en plus la resolution de la route initiale, sans
 * quoi une route paresseuse ferait apparaitre un fond vide entre la fin du
 * fondu et l'arrivee du composant.
 */
function retirerEcranDeDemarrage() {
  const ecran = document.getElementById('yowl-demarrage');
  if (!ecran) return;

  ecran.classList.add('parti');

  // Le fondu dure 420 ms dans la feuille. On attend la fin de la transition,
  // avec une echeance de secours : transitionend ne se declenche pas quand
  // l'utilisateur a demande moins d'animations, et l'ecran resterait dans le
  // document a intercepter les touchers.
  const enlever = () => ecran.remove();
  ecran.addEventListener('transitionend', enlever, { once: true });
  setTimeout(enlever, 700);
}

router.isReady().then(() => {
  requestAnimationFrame(() => requestAnimationFrame(retirerEcranDeDemarrage));
});

const userStore = useUserStore();

//get current user on mounted
userStore.initUser()
