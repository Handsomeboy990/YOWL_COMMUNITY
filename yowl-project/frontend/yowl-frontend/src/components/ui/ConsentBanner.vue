<template>
  <Transition name="bandeau-consentement">
    <div v-if="visible"
      class="fixed inset-x-0 bottom-0 z-[60] px-3 pb-3 lg:px-5 lg:pb-5 pointer-events-none"
      :style="{ paddingBottom: 'calc(0.75rem + env(safe-area-inset-bottom))' }">
      <div ref="carte"
        class="pointer-events-auto mx-auto max-w-3xl mb-16 lg:mb-0 rounded-2xl border border-gray-200 bg-white shadow-xl shadow-blue-night/10 p-4 sm:p-5">

        <!-- En-tete, avec le bouton de repliage a droite. Replie par defaut :
             le texte complet fait six lignes sur un telephone et repoussait
             les reponses hors de vue. -->
        <div class="flex items-start gap-3">
          <span class="hidden sm:grid w-10 h-10 shrink-0 rounded-xl bg-orange-primary/10 text-orange-text place-items-center">
            <Icon name="chart-line" :size="18" aria-hidden="true" />
          </span>

          <div class="flex-1 min-w-0">
            <!-- Plus petit sur telephone : a la taille par defaut le titre
                 prenait deux lignes et occupait a lui seul un tiers du
                 bandeau. -->
            <h2 class="font-poppins font-bold text-base sm:text-lg text-blue-night leading-snug">
              Mesurer l'audience plus finement
            </h2>
            <p class="mt-1 text-sm text-gray-700 leading-relaxed">
              Un identifiant tiré au hasard, qui ne sort jamais de ce site.
            </p>
          </div>

          <button type="button"
            class="shrink-0 w-11 h-11 -mt-1 -mr-1 grid place-items-center rounded-xl text-gray-500 hover:text-blue-night hover:bg-gray-100 transition-colors cursor-pointer"
            :aria-expanded="deplie" aria-controls="consentement-details"
            :aria-label="deplie ? 'Replier les explications' : 'Déplier les explications'"
            @click="deplie = !deplie">
            <Icon :name="deplie ? 'chevron-up' : 'chevron-down'" :size="18" aria-hidden="true" />
          </button>
        </div>

        <!-- Le detail, deplie a la demande. Les reponses, elles, restent
             toujours visibles : les cacher derriere un depliage rendrait le
             refus plus couteux que l'acceptation. -->
        <div v-show="deplie" id="consentement-details" class="mt-3 sm:pl-13 space-y-2 text-sm text-gray-600 leading-relaxed">
          <p>
            YOWL compte déjà les pages ouvertes, sans rien déposer sur votre
            appareil et sans conserver d'adresse IP. Accepter permet en plus de
            savoir combien de personnes distinctes viennent et lesquelles
            reviennent.
          </p>
          <p>
            Rien ne change pour vous si vous refusez : aucune fonctionnalité
            n'en dépend. Vous pouvez revenir sur ce choix à tout moment depuis la
            <router-link to="/confidentialite"
              class="text-orange-text underline underline-offset-2 hover:text-orange-primary-dark">
              politique de confidentialité</router-link>.
          </p>
        </div>

        <!-- Meme taille, meme position, et deux aplats plutot qu'un aplat face
             a un contour : un bouton borde sur fond blanc se lit comme
             l'option secondaire, meme a dimensions egales. Un refus plus
             discret que l'acceptation n'est pas un choix libre, et la CNIL le
             refuse explicitement. -->
        <div class="mt-4 flex gap-2.5 sm:pl-13">
          <BaseButton variant="primary" size="sm" class="flex-1 sm:flex-none sm:w-44" @click="onAccepter">
            Accepter
          </BaseButton>
          <BaseButton variant="night" size="sm" class="flex-1 sm:flex-none sm:w-44" @click="onRefuser">
            Refuser
          </BaseButton>
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

// Replie au premier affichage : le texte complet occupait la moitie d'un
// ecran de telephone, et les reponses tombaient sous le pli.
const deplie = ref(false);

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
  [visible, carte, deplie],
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
