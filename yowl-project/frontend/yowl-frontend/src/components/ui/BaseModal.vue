<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div
        v-if="isOpen"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-blue-night/50 backdrop-blur-sm"
        @click.self="closable && $emit('close')"
      >
        <Transition name="modal-pop" appear>
          <div
            class="relative w-full bg-white rounded-2xl shadow-2xl shadow-blue-night/20 max-h-[90vh] flex flex-col"
            :class="sizeClasses"
            role="dialog"
            aria-modal="true"
          >
            <!-- Header -->
            <header
              v-if="title || closable"
              class="flex items-center justify-between px-6 pt-5 pb-4 border-b border-gray-100"
            >
              <h2 class="text-xl font-poppins font-bold text-blue-night">{{ title }}</h2>
              <button
                v-if="closable"
                type="button"
                class="grid place-items-center w-9 h-9 rounded-full text-gray-500 hover:text-blue-night hover:bg-gray-100 transition-colors cursor-pointer"
                aria-label="Fermer"
                @click="$emit('close')"
              >
                <i class="fa-solid fa-xmark text-lg"></i>
              </button>
            </header>

            <!-- Body -->
            <div class="px-6 py-5 overflow-y-auto">
              <slot />
            </div>

            <!-- Footer -->
            <footer v-if="$slots.footer" class="px-6 pb-5 pt-2">
              <slot name="footer" />
            </footer>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, watch } from 'vue';

const props = defineProps({
  isOpen: { type: Boolean, default: false },
  title: { type: String, default: '' },
  size: { type: String, default: 'md' }, // sm | md | lg
  closable: { type: Boolean, default: true },
});

defineEmits(['close']);

const sizeClasses = computed(
  () =>
    ({
      sm: 'max-w-md',
      md: 'max-w-lg',
      lg: 'max-w-2xl',
    })[props.size]
);

// Bloquer le scroll de la page quand une modale est ouverte
watch(
  () => props.isOpen,
  (open) => {
    document.body.classList.toggle('overflow-y-hidden', open);
  }
);
</script>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.25s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}
.modal-pop-enter-active {
  transition:
    transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1),
    opacity 0.25s ease;
}
.modal-pop-enter-from {
  transform: translateY(24px) scale(0.96);
  opacity: 0;
}
</style>
