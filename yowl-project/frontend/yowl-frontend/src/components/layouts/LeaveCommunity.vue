<template>
  <div class="mt-10 text-right pb-10">
    <BaseButton variant="danger" icon="fa-solid fa-door-open" :shine="false" @click="leave">
      Quitter la communauté YOWL
    </BaseButton>
  </div>
</template>

<script setup>
import router from '@/router';
import { useUserStore } from '@/stores/user';
import BaseButton from '@/components/ui/BaseButton.vue';
import { useNotify, apiErrorMessage } from '@/composables/useNotify';
import { useConfirm } from '@/composables/useConfirm';

const userStore = useUserStore();
const notify = useNotify();
const confirm = useConfirm();

const leave = async () => {
  const confirmed = await confirm({
    title: 'Confirmer le départ',
    message:
      'Veux-tu vraiment quitter la communauté YOWL ? Tes données personnelles seront effacées et cette action est irréversible.',
    confirmLabel: 'Oui, je veux partir',
    cancelLabel: 'Rester',
    tone: 'danger',
  });

  if (!confirmed) return;

  try {
    await userStore.leaveCommunity();
    router.push('/');
    notify.success('Compte supprimé', 'Tes données personnelles ont été effacées. À bientôt peut-être.');
  } catch (err) {
    notify.error(apiErrorMessage(err, "La suppression du compte a échoué. Réessaie."));
  }
};
</script>
