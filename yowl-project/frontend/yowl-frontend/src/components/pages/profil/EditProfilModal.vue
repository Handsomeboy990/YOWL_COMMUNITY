<template>
  <BaseModal :isOpen="isOpen" title="Modifier mon profil" @close="$emit('close')">
    <form class="space-y-4" @submit.prevent="EditModalSubmit">
      <!-- Avatar -->
      <div class="flex flex-col items-center gap-3">
        <img :src="form.picturePreview" alt="Avatar" class="w-24 h-24 rounded-full object-cover border-2 border-orange-primary/30" />
        <label
          class="cursor-pointer inline-flex items-center gap-2 text-sm font-medium text-orange-primary hover:text-orange-primary-dark transition-colors"
        >
          <i class="fa-solid fa-camera"></i>
          Changer ma photo
          <input type="file" accept="image/*" class="hidden" @change="submitPhoto" />
        </label>
      </div>

      <BaseInput v-model="form.fullname" label="Nom complet" icon="fa-regular fa-user" disabled />
      <BaseInput v-model="form.username" label="Pseudo" icon="fa-solid fa-at" />
      <BaseInput v-model="form.email" label="Adresse email" type="email" icon="fa-regular fa-envelope" />
      <BaseInput v-model="form.birthdate" label="Date de naissance" type="date" icon="fa-regular fa-calendar" readonly
        hint="La date de naissance ne peut pas être modifiée" />
      <BaseInput v-model="form.newPassword" label="Nouveau mot de passe" type="password"
        placeholder="Laisser vide pour ne pas changer" icon="fa-solid fa-lock" autocomplete="new-password" />

      <!-- Actions -->
      <div class="flex justify-end gap-3 pt-2">
        <BaseButton variant="ghost" :shine="false" @click="$emit('close')">Annuler</BaseButton>
        <BaseButton type="submit" variant="primary" :loading="saving">Enregistrer</BaseButton>
      </div>
    </form>
  </BaseModal>
</template>

<script setup>
import { ref, watch } from 'vue'
import defaultAvatar from '@/assets/logo.png'
import { useUserStore } from '@/stores/user'
import { getStorageUrl } from '@/config'
import Swal from 'sweetalert2';
import BaseModal from '@/components/ui/BaseModal.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseButton from '@/components/ui/BaseButton.vue';

const userStore = useUserStore();
const saving = ref(false);

defineProps({
  isOpen: Boolean
})

const form = ref({
  fullname: '',
  username: '',
  email: '',
  birthdate: '',
  newPassword: '',
  picture: null,
  picturePreview: defaultAvatar
})

watch(
  () => userStore.user,
  (newUser) => {
    if (newUser) {
      form.value.username = newUser.username
      form.value.email = newUser.email
      form.value.fullname = newUser.fullname

      if (newUser.birthdate) {
        const date = new Date(newUser.birthdate)
        form.value.birthdate = date.toISOString().split('T')[0]
      }

      form.value.picturePreview = newUser.picture ? getStorageUrl(newUser.picture) : defaultAvatar
    }
  },
  { immediate: true }
)

// Aperçu de la nouvelle photo
const submitPhoto = (event) => {
  const file = event.target.files[0]
  if (file) {
    form.value.picture = file
    form.value.picturePreview = URL.createObjectURL(file)
  }
}

const emit = defineEmits(['close'])

const EditModalSubmit = async () => {
  saving.value = true;
  try {
    await userStore.updateUser(form.value)
    Swal.fire({
      icon: "success",
      title: "Profil mis à jour !",
      timer: 1500,
      showConfirmButton: false
    })
    emit('close')
  } catch (err) {
    Swal.fire({
      icon: "error",
      title: "La mise à jour a échoué",
      text: err.message || '',
      confirmButtonColor: '#FF6B35',
    })
  } finally {
    saving.value = false;
  }
}
</script>
