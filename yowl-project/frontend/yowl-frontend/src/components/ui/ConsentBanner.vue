<template>
  <Transition name="bandeau-consentement">
    <div v-if="visible"
      class="fixed inset-x-0 bottom-0 z-[60] px-3 pb-3 lg:px-5 lg:pb-5 pointer-events-none"
      :style="{ paddingBottom: 'calc(0.75rem + env(safe-area-inset-bottom))' }">
      <!-- Sur telephone la navigation basse occupe deja le bord : le bandeau
           se pose au-dessus plutot que par-dessus. -->
      <div ref="carte"
        class="pointer-events-auto mx-auto max-w-3xl mb-16 lg:mb-0 rounded-2xl border border-gray-200 bg-white shadow-xl shadow-blue-night/10 p-5">
        <div class="flex items-start gap-3">
          <span class="hidden sm:grid w-10 h-10 shrink-0 rounded-xl bg-orange-primary/10 text-orange-text place-items-center">
            <Icon name="chart-line" :size="18" aria-hidden="true" />
          </span>

          <div class="flex-1">
            <h2 class="font-poppins font-bold text-blue-night">Mesurer l'audience plus finement</h2>

            <p class="mt-1.5 text-sm text-gray-700 leading-relaxed">
              YOWL compte déjà les pages ouvertes, sans rien déposer sur votre
              appareil et sans conserver d'adresse IP. Pour savoir en plus
              combien de personnes distinctes viennent et lesquelles reviennent,
              il faut poser un identifiant tiré au hasard, qui ne sort jamais de
              ce site.
            </p>

            <p class="mt-2 text-sm text-gray-600 leading-relaxed">
              Rien ne change pour vous si vous refusez : aucune fonctionnalité
              n'en dépend. Vous pouvez revenir sur ce choix à tout moment depuis
              la
              <!-- Souligne en permanence, pas seulement au survol : la
                   couleur seule ne le distingue du texte voisin qu'a 1,45:1,
                   la ou trois sont exiges. Un lien qui ne se voit qu'a la
                   souris ne se voit pas du tout au doigt. -->
              <router-link to="/confidentialite"
                class="text-orange-text underline underline-offset-2 hover:text-orange-primary-dark">
                politique de confidentialité</router-link>.
            </p>

            <!-- Les deux boutons ont le meme poids visuel et la meme taille.
                 Un refus plus discret que l'acceptation n'est pas un choix
                 libre, et la CNIL le refuse explicitement. -->
            <div class="mt-4 flex flex-col sm:flex-row gap-2.5">
              <BaseButton variant="primary" size="sm" class="sm:w-44" @click="onAccepter">
                Accepter
              </BaseButton>
              <BaseButton variant="outline" size="sm" class="sm:w-44" @click="onRefuser">
                Refuser
              </BaseButton>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';

import BaseButton from '@/components/ui/BaseButton.vue';
import Icon from '@/components/ui/Icon.vue';
import { accepter, choix, refuser } from '@/composables/useConsent';

// Tant que la personne n'a pas repondu. Un choix deja fait, quel qu'il soit,
// ne se redemande pas avant l'expiration du cookie.
const visible = computed(() => choix.value !== 'oui' && choix.value !== 'non');

const carte = ref(null);

/**
 * Le bandeau flotte au-dessus de la page : sans compensation, il recouvre ce
 * qui se trouve en bas.
 *
 * Ce n'est pas une gene esthetique. Sur l'ecran de connexion, il masquait le
 * bouton « Se connecter » : le clic partait sur le bandeau, et le formulaire
 * ne repondait plus du tout. Le bas de page reçoit donc la hauteur du
 * bandeau, rendue des qu'il disparait.
 */
let observateur = null;

function compenser() {
  const hauteur = carte.value?.getBoundingClientRect().height ?? 0;
  document.body.style.paddingBottom = hauteur ? `${Math.ceil(hauteur) + 24}px` : '';
}

function liberer() {
  observateur?.disconnect();
  observateur = null;
  document.body.style.paddingBottom = '';
}

watch(
  [visible, carte],
  ([estVisible, element]) => {
    if (!estVisible || !element) {
      liberer();
      return;
    }

    // La hauteur depend du texte replie, donc de la largeur : elle change
    // avec la rotation de l'appareil et le zoom du navigateur.
    if (typeof ResizeObserver !== 'undefined') {
      observateur = new ResizeObserver(compenser);
      observateur.observe(element);
    }
    compenser();
  },
  { immediate: true, flush: 'post' }
);

onBeforeUnmount(liberer);

function onAccepter() {
  accepter();
  liberer();
}

function onRefuser() {
  refuser();
  liberer();
}
</script>

<style scoped>
.bandeau-consentement-enter-active {
  transition: opacity 260ms ease, transform 260ms cubic-bezier(.22, 1, .36, 1);
}

.bandeau-consentement-leave-active {
  transition: opacity 180ms ease, transform 180ms ease;
}

.bandeau-consentement-enter-from,
.bandeau-consentement-leave-to {
  opacity: 0;
  transform: translateY(12px);
}

@media (prefers-reduced-motion: reduce) {

  .bandeau-consentement-enter-active,
  .bandeau-consentement-leave-active {
    transition: opacity 120ms ease;
  }

  .bandeau-consentement-enter-from,
  .bandeau-consentement-leave-to {
    transform: none;
  }
}
</style>
