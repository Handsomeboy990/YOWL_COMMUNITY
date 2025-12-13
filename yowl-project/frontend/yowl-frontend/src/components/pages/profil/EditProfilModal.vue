<template>
  <!-- modal -->
  <div v-if="isOpen" class="fixed inset-0 flex items-center justify-center z-50 bg-black/40">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-lg p-6 relative">

      <!-- close modal -->
      <button @click="$emit('close')" class="cursor-pointer absolute top-2 right-2 text-gray-500 hover:text-gray-800">
        <i class="fa-solid fa-xmark"></i>
      </button>

      <!-- title -->
      <h2 class="text-2xl font-poppins font-bold text-center mb-6">Edit Your Profile</h2>

      <!-- form -->
      <form @submit.prevent="EditModalSubmit" class="space-y-4">


        <div class="flex flex-col items-center gap-3">
          <!-- avatar -->
          <img :src="form.picturePreview" alt="Profile Avatar" class="w-24 h-24 rounded-full object-cover border" />
          <!-- input file -->
          <input type="file" @change="submitPhoto"
            class="cursor-pointer block w-full border border-gray-300 rounded-lg px-3 py-2" />
        </div>

        <!-- lastName -->
        <div>
          <label>Full Name</label>
          <input type="text" v-model="form.fullname"
            class="block w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-100 text-gray-500" disabled />
        </div>

        <!-- username -->
        <div>
          <label class="block text-sm font-medium mb-1">Username</label>
          <input v-model="form.username" type="text" class="block w-full border border-gray-300 rounded-lg px-3 py-2" />
        </div>

        <!-- email -->
        <div>
          <label class="block text-sm font-medium mb-1">Email</label>
          <input v-model="form.email" type="email" class="block w-full border border-gray-300 rounded-lg px-3 py-2" />
        </div>

        <!-- birthdate -->
        <div>
          <label class="block text-sm font-medium mb-1">Birthdate</label>
          <input v-model="form.birthdate" type="date"
            class="block w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-100 text-gray-500" readonly />
        </div>

        <!-- old Password -->
        <div>
          <label class="block text-sm font-medium mb-1">Old Password</label>
          <input v-model="form.oldPassword" type="password"
            class="block w-full border border-gray-300 rounded-lg px-3 py-2" />
        </div>

        <!-- new Password -->
        <div>
          <label class="block text-sm font-medium mb-1">New Password</label>
          <input v-model="form.newPassword" type="password"
            class="block w-full border border-gray-300 rounded-lg px-3 py-2" />
        </div>

        <!-- actions -->
        <div class="flex justify-between gap-3 pt-4">
          <!-- discard -->
          <button type="button" @click="$emit('close')"
            class="cursor-pointer flex-1 px-4 py-2 bg-blue-night text-white rounded-lg hover:opacity-90 transition">
            Discard
          </button>

          <!-- save Changes -->
          <button type="submit"
            class=" cursor-pointer flex-1 px-4 py-2 bg-orange-primary text-white rounded-lg hover:opacity-90 transition">
            Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import defaultAvatar from '@/assets/logo.png'
import { useUserStore } from '@/stores/user'
import Swal from 'sweetalert2';
const userStore = useUserStore();

defineProps({
  isOpen: Boolean
})

// form data
const form = ref({
  fullname: '',
  username: '',
  email: '',
  birthdate: '',
  oldPassword: '',
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

      // check the date format
      if (newUser.birthdate) {
        const date = new Date(newUser.birthdate)
        form.value.birthdate = date.toISOString().split('T')[0]
      }

      form.value.picturePreview = newUser.avatar_url ?? defaultAvatar
    }
  },
  { immediate: true }
)


// upload picture & preview
const submitPhoto = (event) => {
  const file = event.target.files[0]
  if (file) {
    form.value.picture = file
    form.value.picturePreview = URL.createObjectURL(file) // preview
  }
}

// formSubmit
const emit = defineEmits(['close'])
const EditModalSubmit = async () => {
  try {
    await userStore.updateUser(form.value)
    Swal.fire({
      icon: "success",
      title: "Profile updated!",
      timer: 1500,
      showConfirmButton: false
    })


    emit('close')
  } catch (error) {
    console.error("update failed", error.message)
    Swal.fire({
      icon: "error",
      title: "Update failed",
      timer: 4500,
      showConfirmButton: false
    })

  }
}
</script>
