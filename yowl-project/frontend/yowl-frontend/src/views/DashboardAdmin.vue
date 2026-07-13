<template>
  <Header />
  <div class="max-w-6xl mx-auto py-20 px-4">
    <!-- Bandeau -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-8 p-5 bg-blue-night text-white rounded-xl shadow">
      <h1 class="text-2xl md:text-3xl font-poppins font-bold">Tableau de bord admin</h1>
      <span class="text-sm md:text-base text-white/70">Gestion de la communauté YOWL</span>
    </div>

    <!-- Statistiques globales -->
    <section class="mb-10">
      <h2 class="text-xl font-semibold mb-3 text-blue-night">Statistiques globales</h2>
      <div v-if="stats">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
          <div class="bg-blue-100 p-4 rounded-xl text-center">
            <div class="text-2xl font-bold">{{ stats.users }}</div>
            <div class="text-sm text-gray-600">Membres</div>
          </div>
          <div class="bg-orange-100 p-4 rounded-xl text-center">
            <div class="text-2xl font-bold">{{ stats.reviews }}</div>
            <div class="text-sm text-gray-600">Reviews</div>
          </div>
          <div class="bg-green-100 p-4 rounded-xl text-center">
            <div class="text-2xl font-bold">{{ stats.comments }}</div>
            <div class="text-sm text-gray-600">Commentaires</div>
          </div>
          <div class="bg-purple-100 p-4 rounded-xl text-center">
            <div class="text-2xl font-bold">{{ stats.tags }}</div>
            <div class="text-sm text-gray-600">Tags</div>
          </div>
        </div>
        <h3 class="font-semibold mt-6 mb-2 text-blue-night">Dernières reviews</h3>
        <ul>
          <li v-for="review in stats.latest_reviews" :key="review.id" class="mb-2 text-sm">
            <span class="font-bold">#{{ review.id }}</span> -
            {{ truncate(review.content, 80) }}
            <span class="text-xs text-gray-500">({{ formatDate(review.created_at) }})</span>
          </li>
        </ul>
      </div>
      <div v-else class="text-gray-500">Chargement des statistiques...</div>
    </section>

    <!-- Utilisateurs -->
    <section class="mb-10">
      <h2 class="text-xl font-semibold mb-3 text-blue-night">Membres</h2>
      <div v-if="users">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 rounded-xl overflow-hidden shadow">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Pseudo</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Rôle</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actif</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="user in users.data" :key="user.id">
                <td class="px-4 py-2">{{ user.id }}</td>
                <td class="px-4 py-2">{{ user.username }}</td>
                <td class="px-4 py-2">{{ user.email }}</td>
                <td class="px-4 py-2 capitalize">{{ roleName(user) }}</td>
                <td class="px-4 py-2">
                  <span :class="user.is_active ? 'text-green-600' : 'text-red-600'">
                    {{ user.is_active ? 'Oui' : 'Non' }}
                  </span>
                </td>
                <td class="px-4 py-2 flex gap-2">
                  <button v-if="user.is_active" class="bg-red-600 cursor-pointer text-white px-2 py-1 rounded"
                    @click="banUser(user.id)">
                    Bannir
                  </button>
                  <button v-else class="bg-emerald-600 cursor-pointer text-white px-2 py-1 rounded"
                    @click="unbanUser(user.id)">
                    Réintégrer
                  </button>
                  <button v-if="user.is_active" class="bg-indigo-500 cursor-pointer text-white px-2 py-1 rounded"
                    @click="toggleRole(user)">
                    {{ isAdminUser(user) ? 'Passer client' : 'Passer admin' }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <Pagination v-if="users.last_page > 1" :pagination="users" @changePage="fetchUsers" />
      </div>
      <div v-else class="text-gray-500">Chargement des membres...</div>
    </section>

    <!-- Reviews -->
    <section class="mb-10">
      <h2 class="text-xl font-semibold mb-3 text-blue-night">Reviews</h2>
      <div v-if="reviews">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 rounded-xl overflow-hidden shadow">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Auteur</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Contenu</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Publiée</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="review in reviews.data" :key="review.id">
                <td class="px-4 py-2">{{ review.id }}</td>
                <td class="px-4 py-2">{{ review.user?.username || '-' }}</td>
                <td class="px-4 py-2">{{ truncate(review.content, 60) }}</td>
                <td class="px-4 py-2">
                  <span :class="review.is_published ? 'text-green-600' : 'text-red-600'">
                    {{ review.is_published ? 'Oui' : 'Non' }}
                  </span>
                </td>
                <td class="px-4 py-2 flex gap-2">
                  <button v-if="review.is_published" class="bg-yellow-600 cursor-pointer text-white px-2 py-1 rounded"
                    @click="unpublishReview(review.id)">
                    Dépublier
                  </button>
                  <button v-else class="bg-emerald-600 cursor-pointer text-white px-2 py-1 rounded"
                    @click="publishReview(review.id)">
                    Publier
                  </button>
                  <button class="bg-red-600 cursor-pointer text-white px-2 py-1 rounded" @click="deleteReview(review.id)">
                    Supprimer
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <Pagination v-if="reviews.last_page > 1" :pagination="reviews" @changePage="fetchReviews" />
      </div>
      <div v-else class="text-gray-500">Chargement des reviews...</div>
    </section>

    <!-- Commentaires -->
    <section class="mb-10">
      <h2 class="text-xl font-semibold mb-3 text-blue-night">Commentaires</h2>
      <div v-if="comments">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 rounded-xl overflow-hidden shadow">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Auteur</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Review</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Contenu</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="comment in comments.data" :key="comment.id">
                <td class="px-4 py-2">{{ comment.id }}</td>
                <td class="px-4 py-2">{{ comment.user?.username || '-' }}</td>
                <td class="px-4 py-2">#{{ comment.review_id }}</td>
                <td class="px-4 py-2">{{ truncate(comment.content, 60) }}</td>
                <td class="px-4 py-2 flex gap-2">
                  <button class="bg-red-600 cursor-pointer text-white px-2 py-1 rounded" @click="deleteComment(comment.id)">
                    Supprimer
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <Pagination v-if="comments.last_page > 1" :pagination="comments" @changePage="fetchComments" />
      </div>
      <div v-else class="text-gray-500">Chargement des commentaires...</div>
    </section>
  </div>
</template>

<script setup>
import Header from '@/components/layouts/Header.vue';
import Pagination from '@/components/layouts/Pagination.vue';
import { ref, onMounted } from 'vue';
import api from '@/services/apiService';
import Swal from 'sweetalert2';

const stats = ref(null);
const users = ref(null);
const reviews = ref(null);
const comments = ref(null);

const truncate = (text, length) =>
  text && text.length > length ? text.substring(0, length) + '...' : text;

const formatDate = (value) => new Date(value).toLocaleDateString('fr-FR', {
  day: 'numeric',
  month: 'short',
  year: 'numeric',
});

// Les rôles arrivent soit en chaînes (getRoleNames) soit en objets {name}
const roleName = (user) => {
  const role = user.roles?.[0];
  if (!role) return '-';
  return typeof role === 'string' ? role : role.name;
};

const isAdminUser = (user) => roleName(user) === 'admin';

const fetchUsers = async (page = 1) => {
  try {
    const res = await api.get(`/admin/users?page=${page}`);
    users.value = res.data.data;
  } catch {
    users.value = null;
  }
};

const fetchReviews = async (page = 1) => {
  try {
    const res = await api.get(`/admin/reviews?page=${page}`);
    reviews.value = res.data.data;
  } catch {
    reviews.value = null;
  }
};

const fetchComments = async (page = 1) => {
  try {
    const res = await api.get(`/admin/comments?page=${page}`);
    comments.value = res.data.data;
  } catch {
    comments.value = null;
  }
};

const confirmAction = (options) =>
  Swal.fire({
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#FF6B35',
    cancelButtonColor: '#1E2A38',
    cancelButtonText: 'Annuler',
    ...options,
  });

const toggleRole = async (user) => {
  const newRole = isAdminUser(user) ? 'client' : 'admin';
  const result = await confirmAction({
    title: 'Confirmer le changement de rôle',
    text: `Veux-tu vraiment passer ce membre en "${newRole}" ?`,
    confirmButtonText: 'Oui, changer',
  });
  if (result.isConfirmed) {
    await api.patch(`/admin/users/${user.id}/role`, { role: newRole });
    Swal.fire({ title: 'Rôle mis à jour !', icon: 'success', timer: 1500, showConfirmButton: false });
  }
  fetchUsers(users.value?.current_page || 1);
};

const deleteComment = async (id) => {
  const result = await confirmAction({
    title: 'Confirmer la suppression',
    text: 'Veux-tu vraiment supprimer ce commentaire ? Cette action est irréversible.',
    confirmButtonText: 'Oui, supprimer',
  });
  if (result.isConfirmed) {
    await api.delete(`/admin/comments/${id}`);
    Swal.fire({ title: 'Supprimé !', icon: 'success', timer: 1500, showConfirmButton: false });
  }
  fetchComments(comments.value?.current_page || 1);
};

const publishReview = async (id) => {
  const result = await confirmAction({
    title: 'Confirmer la publication',
    text: 'Veux-tu vraiment publier cette review ?',
    confirmButtonText: 'Oui, publier',
  });
  if (result.isConfirmed) {
    await api.patch(`/admin/reviews/${id}/publish`);
    Swal.fire({ title: 'Publiée !', icon: 'success', timer: 1500, showConfirmButton: false });
  }
  fetchReviews(reviews.value?.current_page || 1);
};

const unpublishReview = async (id) => {
  const result = await confirmAction({
    title: 'Confirmer la dépublication',
    text: 'Veux-tu vraiment dépublier cette review ?',
    confirmButtonText: 'Oui, dépublier',
  });
  if (result.isConfirmed) {
    await api.patch(`/admin/reviews/${id}/unpublish`);
    Swal.fire({ title: 'Dépubliée !', icon: 'success', timer: 1500, showConfirmButton: false });
  }
  fetchReviews(reviews.value?.current_page || 1);
};

const deleteReview = async (id) => {
  const result = await confirmAction({
    title: 'Confirmer la suppression',
    text: 'Veux-tu vraiment supprimer cette review ? Cette action est irréversible.',
    confirmButtonText: 'Oui, supprimer',
  });
  if (result.isConfirmed) {
    await api.delete(`/admin/reviews/${id}`);
    Swal.fire({ title: 'Supprimée !', icon: 'success', timer: 1500, showConfirmButton: false });
  }
  fetchReviews(reviews.value?.current_page || 1);
};

const banUser = async (id) => {
  const result = await confirmAction({
    title: 'Confirmer le bannissement',
    text: 'Veux-tu vraiment bannir ce membre ?',
    confirmButtonText: 'Oui, bannir',
  });
  if (result.isConfirmed) {
    await api.patch(`/admin/users/${id}/ban`);
    Swal.fire({ title: 'Banni !', icon: 'success', timer: 1500, showConfirmButton: false });
  }
  fetchUsers(users.value?.current_page || 1);
};

const unbanUser = async (id) => {
  const result = await confirmAction({
    title: 'Confirmer la réintégration',
    text: 'Veux-tu vraiment réintégrer ce membre dans la communauté ?',
    confirmButtonText: 'Oui, réintégrer',
  });
  if (result.isConfirmed) {
    await api.patch(`/admin/users/${id}/unban`);
    Swal.fire({ title: 'Réintégré !', icon: 'success', timer: 1500, showConfirmButton: false });
  }
  fetchUsers(users.value?.current_page || 1);
};

onMounted(async () => {
  try {
    const res = await api.get('/admin/stats');
    stats.value = res.data.data;
  } catch {
    stats.value = null;
  }
  fetchUsers();
  fetchReviews();
  fetchComments();
});
</script>
