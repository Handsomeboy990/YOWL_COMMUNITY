<template>
  <p :class="classes"><template v-for="(part, index) in parts" :key="index"><router-link
        v-if="part.type === 'mention'" :to="`/membres/${part.handle}`"
        class="text-orange-text font-medium hover:underline"
        @click.stop>@{{ part.handle }}</router-link><a v-else-if="part.type === 'link'" :href="part.href"
        target="_blank" rel="noopener noreferrer" class="text-orange-text hover:underline"
        @click.stop>{{ part.text }}</a><template v-else>{{ part.text }}</template></template></p>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  text: { type: String, default: '' },
  classes: { type: String, default: '' },
});

/**
 * Découpe un texte en fragments, en reconnaissant les mentions et les liens.
 *
 * Rien n'est injecté comme HTML : chaque fragment est rendu par Vue, donc un
 * texte contenant des chevrons reste du texte. Écrire une mention créait une
 * notification sans que le nom soit cliquable nulle part.
 */
const MOTIF = /(@[a-zA-Z0-9._-]{3,255}|https?:\/\/[^\s<]+)/g;

const parts = computed(() => {
  const source = props.text ?? '';
  const morceaux = [];
  let dernier = 0;

  for (const trouve of source.matchAll(MOTIF)) {
    if (trouve.index > dernier) {
      morceaux.push({ type: 'text', text: source.slice(dernier, trouve.index) });
    }

    const valeur = trouve[0];
    if (valeur.startsWith('@')) {
      // Un point final appartient à la phrase, pas au pseudo.
      const handle = valeur.slice(1).replace(/\.$/, '');
      morceaux.push({ type: 'mention', handle });
      dernier = trouve.index + 1 + handle.length;
    } else {
      morceaux.push({ type: 'link', href: valeur, text: valeur });
      dernier = trouve.index + valeur.length;
    }
  }

  if (dernier < source.length) {
    morceaux.push({ type: 'text', text: source.slice(dernier) });
  }

  return morceaux;
});
</script>
