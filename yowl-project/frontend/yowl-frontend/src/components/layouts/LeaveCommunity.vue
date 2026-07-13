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
import Swal from 'sweetalert2';
import BaseButton from '@/components/ui/BaseButton.vue';

const userStore = useUserStore();

const leave = async () => {
  Swal.fire({
    title: 'Confirmer le départ',
    text: "Veux-tu vraiment quitter la communauté YOWL ? Cette action est irréversible et tu vas nous manquer.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#FF6B35',
    cancelButtonColor: '#1E2A38',
    confirmButtonText: 'Oui, je veux partir',
    cancelButtonText: 'Rester',
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        await userStore.leaveCommunity();
        router.push('/');
        Swal.fire({
          title: 'Compte désactivé',
          text: 'Tu vas nous manquer. À bientôt peut-être !',
          icon: 'success',
          confirmButtonColor: '#FF6B35',
        });
      } catch {
        Swal.fire({
          title: 'Oups...',
          text: "La désactivation du compte a échoué. Réessaie.",
          icon: 'error',
          confirmButtonColor: '#FF6B35',
        });
      }
    }
  });
};
</script>
