<template>
  <div v-if="isOpen" class="fixed inset-0 flex items-center justify-center z-50">
    <!-- Modal -->
    <div class="relative bg-gray-100 text-[#1E2A38] rounded-2xl shadow-lg w-full max-w-lg p-6 z-10">
      <!-- Close button -->
      <button class="cursor-pointer absolute top-3 right-3 text-gray-500 hover:text-gray-700" @click="$emit('close')">
        <i class="fa-solid fa-xmark text-xl"></i>
      </button>

      <!-- Title -->
      <h2 class="text-xl font-poppins font-bold text-blue-night mb-4">
        Edit Review
      </h2>

      <!-- Form -->
      <form @submit.prevent="updateForm" class="space-y-4">
        <!-- Content -->
        <div>
          <label class="block text-sm font-roboto mb-1">Content</label>
          <textarea v-model="form.content" class="w-full border rounded-md px-3 py-2 text-sm" rows="3"
            required></textarea>
        </div>

        <!-- Link -->
        <div>
          <label class="block text-sm font-roboto mb-1">Link (optional)</label>
          <input type="url" v-model="form.link" class="w-full border rounded-md px-3 py-2 text-sm" />
        </div>

        <!-- Media -->
        <div>
          <label class="block text-sm font-roboto mb-1">Media (optional)</label>
          <input type="file" @change="submitMediaUpdate" class="cursor-pointer w-full text-sm" />
          <p v-if="form.mediaName" class=" text-xs text-gray-500 mt-1">
            Current: {{ form.mediaName }}
          </p>
        </div>

        <!-- Tags -->
        <div>
          <label class="block text-sm font-roboto mb-1">Tags (comma separated)</label>
          <input type="text" v-model="form.tags" class="w-full border rounded-md px-3 py-2 text-sm" />
        </div>

        <!-- Buttons -->
        <div class="flex justify-end gap-3 mt-6">
          <button type="button" @click="$emit('close')"
            class="cursor-pointer bg-[#1E2A38] text-white px-4 py-2 rounded-md hover:opacity-80 transition-colors">
            Discard
          </button>
          <button type="submit"
            class="cursor-pointer bg-[#FF6B35] text-white px-4 py-2 rounded-md hover:opacity-90 transition-colors">
            Update my review
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import Swal from 'sweetalert2'

const props = defineProps({
  isOpen: Boolean,
  review: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'update'])

const form = ref({
  content: '',
  link: '',
  media: null,
  mediaName: '',
  tags: ''
})

// Watch to pre-fill when the review changes
watch(
  () => props.review,
  (newReview) => {
    if (newReview) {
      form.value.content = newReview.content || ''
      form.value.link = newReview.link || ''
      form.value.media = null
      form.value.mediaName = newReview.mediaName || ''
      form.value.tags = newReview.tags ? newReview.tags.join(', ') : ''
    }
  },
  { immediate: true }
)

const submitMediaUpdate = (event) => {
  const file = event.target.files[0]
  if (file) {
    form.value.media = file
    form.value.mediaName = file.name
  }
}

const updateForm = () => {
  emit('update', {
    content: form.value.content,
    link: form.value.link,
    media: form.value.media,
    tags: form.value.tags.split(',').map(t => t.trim())
  });

  Swal.fire({
    icon: 'success',
    title: 'Review Updated!',
    showConfirmButton: false,
    timer: 1500
  });

  emit('close');

}
</script>
