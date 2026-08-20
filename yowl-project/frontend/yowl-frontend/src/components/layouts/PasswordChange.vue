<template>
  <section class="mt-8 bg-white border border-gray-200 rounded-2xl p-5">
    <div class="flex items-start gap-4">
      <span class="w-10 h-10 shrink-0 rounded-xl bg-blue-night/5 grid place-items-center text-blue-night">
        <i class="fa-solid fa-key" aria-hidden="true"></i>
      </span>

      <div class="min-w-0 flex-1">
        <h2 class="font-poppins font-bold text-blue-night">{{ t('security.title') }}</h2>
        <p class="mt-1 text-sm text-gray-600">{{ t('security.intro') }}</p>

        <BaseButton v-if="!open" class="mt-4" variant="night" size="sm"
          icon="fa-solid fa-pen" @click="ouvrir">
          {{ t('security.change') }}
        </BaseButton>

        <form v-else class="mt-5 space-y-4 max-w-md" @submit.prevent="soumettre">
          <BaseInput v-model="form.current" :label="t('security.current')" type="password"
            icon="fa-solid fa-lock" autocomplete="current-password" required
            :error="erreurs.current_password" />

          <!-- Le nouveau mot de passe ne s'ouvre qu'une fois l'ancien saisi :
               l'ordre du formulaire dit l'ordre de la vérification. -->
          <template v-if="form.current">
            <BaseInput v-model="form.next" :label="t('security.next')" type="password"
              icon="fa-solid fa-key" autocomplete="new-password" required
              :error="erreurs.password" :hint="t('security.rule')" />

            <div>
              <div class="h-1.5 rounded-full bg-gray-200 overflow-hidden">
                <div class="h-full rounded-full transition-all duration-300"
                  :class="force.classe" :style="{ width: force.part + '%' }"></div>
              </div>
              <p class="mt-1.5 text-xs" :class="force.texte">{{ force.libelle }}</p>
            </div>

            <BaseInput v-model="form.confirm" :label="t('security.confirm')" type="password"
              autocomplete="new-password" icon="fa-solid fa-check-double" required
              :error="desaccord ? t('security.mismatch') : ''" />
          </template>

          <div class="flex flex-wrap gap-3 pt-1">
            <BaseButton variant="primary" size="sm" type="submit" :loading="envoi"
              :disabled="!complet">
              {{ t('security.save') }}
            </BaseButton>
            <BaseButton variant="ghost" size="sm" type="button" @click="fermer">
              {{ t('common.cancel') }}
            </BaseButton>
          </div>

          <p class="text-xs text-gray-500">{{ t('security.otherDevices') }}</p>
        </form>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import api from '@/services/apiService';
import { useNotify, apiErrorMessage } from '@/composables/useNotify';

const { t } = useI18n();
const notify = useNotify();

const open = ref(false);
const envoi = ref(false);
const form = ref({ current: '', next: '', confirm: '' });
const erreurs = ref({});

const desaccord = computed(
  () => form.value.confirm.length > 0 && form.value.next !== form.value.confirm
);

const complet = computed(
  () => form.value.current && form.value.next && form.value.confirm && !desaccord.value
);

/**
 * Une estimation lisible, pas un score de sécurité.
 *
 * Elle compte la longueur et la variété des caractères. Le serveur reste seul
 * juge : il applique les règles de Laravel, et cette barre ne fait qu'éviter
 * un aller-retour pour un mot de passe manifestement trop court.
 */
const force = computed(() => {
  const mot = form.value.next;
  if (!mot) return { part: 0, classe: 'bg-gray-300', texte: 'text-gray-500', libelle: t('security.strength0') };

  let points = 0;
  if (mot.length >= 8) points++;
  if (mot.length >= 12) points++;
  if (/[a-z]/.test(mot) && /[A-Z]/.test(mot)) points++;
  if (/\d/.test(mot)) points++;
  if (/[^\w\s]/.test(mot)) points++;

  return [
    { part: 20, classe: 'bg-red-500', texte: 'text-red-600', libelle: t('security.strength1') },
    { part: 40, classe: 'bg-orange-400', texte: 'text-orange-text', libelle: t('security.strength2') },
    { part: 60, classe: 'bg-amber-500', texte: 'text-amber-700', libelle: t('security.strength3') },
    { part: 80, classe: 'bg-emerald-500', texte: 'text-emerald-700', libelle: t('security.strength4') },
    { part: 100, classe: 'bg-emerald-600', texte: 'text-emerald-700', libelle: t('security.strength5') },
  ][Math.max(0, points - 1)];
});

function ouvrir() {
  open.value = true;
  form.value = { current: '', next: '', confirm: '' };
  erreurs.value = {};
}

function fermer() {
  open.value = false;
  form.value = { current: '', next: '', confirm: '' };
  erreurs.value = {};
}

async function soumettre() {
  envoi.value = true;
  erreurs.value = {};
  try {
    const { data } = await api.patch('/mot-de-passe', {
      current_password: form.value.current,
      password: form.value.next,
      password_confirmation: form.value.confirm,
    });
    notify.success(t('security.done'), data.message);
    fermer();
  } catch (err) {
    // Les erreurs de champ retournent au champ concerne plutot qu'a un toast.
    const champs = err?.response?.data?.errors;
    if (champs) {
      erreurs.value = {
        current_password: champs.current_password?.[0] ?? '',
        password: champs.password?.[0] ?? '',
      };
    } else {
      notify.error(apiErrorMessage(err, t('security.failed')));
    }
  } finally {
    envoi.value = false;
  }
}
</script>
