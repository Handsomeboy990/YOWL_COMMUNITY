<template>
  <Header />
  <div class="max-w-5xl mx-auto py-20">
    <!-- Mini header -->
    <div class="flex items-center justify-between mb-8 p-4 bg-blue-night text-white rounded-lg shadow">
      <h1 class="text-3xl font-bold">Admin Dashboard</h1>
      <span class="text-lg">YOWL Community Management</span>
    </div>

    <section class="mb-8">
      <h2 class="text-xl font-semibold mb-2">Global statistics</h2>
      <div v-if="stats">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
          <div class="bg-blue-100 p-4 rounded-lg text-center">
            <div class="text-2xl font-bold">{{ stats.users }}</div>
            <div>Users</div>
          </div>
          <div class="bg-orange-100 p-4 rounded-lg text-center">
            <div class="text-2xl font-bold">{{ stats.reviews }}</div>
            <div>Reviews</div>
          </div>
          <div class="bg-green-100 p-4 rounded-lg text-center">
            <div class="text-2xl font-bold">{{ stats.comments }}</div>
            <div>Comments</div>
          </div>
          <div class="bg-purple-100 p-4 rounded-lg text-center">
            <div class="text-2xl font-bold">{{ stats.tags }}</div>
            <div>Tags</div>
          </div>
        </div>
        <h3 class="font-semibold mt-6 mb-2">Latest reviews</h3>
        <ul>
          <li v-for="review in stats.latest_reviews" :key="review.id" class="mb-2">
            <span class="font-bold">#{{ review.id }}</span> -
            {{ review.content.substring(0, 80) }}...
            <span class="text-xs text-gray-500">({{ review.created_at }})</span>
          </li>
        </ul>
      </div>
      <div v-else class="text-gray-500">Loading statistics...</div>
    </section>
    <section class="mb-8">
      <h2 class="text-xl font-semibold mb-2">Users</h2>
      <div v-if="users">
        <table class="min-w-full divide-y divide-gray-200 rounded-lg overflow-hidden shadow">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                Username
              </th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                Active
              </th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="user in users.data" :key="user.id">
              <td class="px-4 py-2">{{ user.id }}</td>
              <td class="px-4 py-2">{{ user.username }}</td>
              <td class="px-4 py-2">{{ user.email }}</td>
              <td class="px-4 py-2">{{ user.roles[0].name }}</td>
              <td class="px-4 py-2">
                <span :class="user.is_active ? 'text-green-600' : 'text-red-600'">
                  {{ user.is_active ? 'Yes' : 'No' }}
                </span>
              </td>
              <td class="px-4 py-2 flex gap-2">
                <button v-if="user.is_active" @click="banUser(user.id)"
                  class="bg-red-600 cursor-pointer text-white px-2 py-1 rounded">
                  Ban
                </button>
                <button v-else @click="unbanUser(user.id)"
                  class="bg-emerald-600 cursor-pointer text-white px-2 py-1 rounded">
                  Reintegrate
                </button>
                <button v-if="user.is_active" @click="toggleRole(user)" class="bg-indigo-500 cursor-pointer text-white px-2 py-1 rounded">
                  {{
                    user.roles && user.roles[0].name.includes('admin') ? 'Set Client' : 'Set Admin'
                  }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <Pagination v-if="users.last_page > 1" :pagination="users" @changePage="fetchUsers" />
      </div>
      <div v-else class="text-gray-500">Loading users...</div>
    </section>
    <section class="mb-8">
      <h2 class="text-xl font-semibold mb-2">Reviews</h2>
      <div v-if="reviews">
        <table class="min-w-full divide-y divide-gray-200 rounded-lg overflow-hidden shadow">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                Author
              </th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                Content
              </th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                Published
              </th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="review in reviews.data" :key="review.id">
              <td class="px-4 py-2">{{ review.id }}</td>
              <td class="px-4 py-2">{{ review.user?.username || '-' }}</td>
              <td class="px-4 py-2">{{ review.content.substring(0, 60) }}...</td>
              <td class="px-4 py-2">
                <span :class="review.is_published ? 'text-green-600' : 'text-red-600'">
                  {{ review.is_published ? 'Yes' : 'No' }}
                </span>
              </td>
              <td class="px-4 py-2 flex gap-2">
                <button v-if="review.is_published" @click="unpublishReview(review.id)"
                  class="bg-yellow-600 cursor-pointer text-white px-2 py-1 rounded">
                  Unpublish
                </button>
                <button v-else @click="publishReview(review.id)"
                  class="bg-emerald-600 cursor-pointer text-white px-2 py-1 rounded">
                  Publish
                </button>
                <button @click="deleteReview(review.id)" class="bg-red-600 cursor-pointer text-white px-2 py-1 rounded">
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <Pagination v-if="reviews.last_page > 1" :pagination="reviews" @changePage="fetchReviews" />
      </div>
      <div v-else class="text-gray-500">Loading reviews...</div>
    </section>
    <section class="mb-8">
      <h2 class="text-xl font-semibold mb-2">Comments</h2>
      <div v-if="comments">
        <table class="min-w-full divide-y divide-gray-200 rounded-lg overflow-hidden shadow">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                Author
              </th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                Review
              </th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                Content
              </th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="comment in comments.data" :key="comment.id">
              <td class="px-4 py-2">{{ comment.id }}</td>
              <td class="px-4 py-2">{{ comment.user?.username || '-' }}</td>
              <td class="px-4 py-2">#{{ comment.review_id }}</td>
              <td class="px-4 py-2">{{ comment.content.substring(0, 60) }}...</td>
              <td class="px-4 py-2 flex gap-2">
                <button @click="deleteComment(comment.id)"
                  class="bg-red-600 cursor-pointer text-white px-2 py-1 rounded">
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <Pagination v-if="comments.last_page > 1" :pagination="comments" @changePage="fetchComments" />
      </div>
      <div v-else class="text-gray-500">Loading comments...</div>
    </section>
  </div>
</template>

<script setup>
const toggleRole = async (user) => {
  const newRole = user.roles && user.roles[0].name.includes('admin') ? 'client' : 'admin';
  Swal.fire({
    title: "Confirm role changing",
    text: "Do you really want to change the role of this user ?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#FF6B35",
    cancelButtonColor: "#1E2A38",
    confirmButtonText: "Yes, change"
  }).then(async (result) => {
    if (result.isConfirmed) {
      await api.patch(`/admin/users/${user.id}/role`, { role: newRole });
      Swal.fire({
        title: "Updated!",
        text: "The role of this user has been updated.",
        icon: "success",
        confirmButtonColor: "#FF6B35"
      });
    }
    fetchUsers(users.value.current_page);
  })
};

import Header from '@/components/layouts/Header.vue';
import Pagination from '@/components/layouts/Pagination.vue';
import { ref, onMounted } from 'vue';
import api from '@/services/apiService';
import Swal from 'sweetalert2';

const stats = ref(null);
const users = ref(null);

const reviews = ref(null);

const comments = ref(null);

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

const deleteComment = async (id) => {

  Swal.fire({
    title: "Confirm deletion",
    text: "Do you really want to delete this comment ? This action is irreversible.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#FF6B35",
    cancelButtonColor: "#1E2A38",
    confirmButtonText: "Yes, delete"
  }).then(async (result) => {
    if (result.isConfirmed) {
      await api.delete(`/admin/comments/${id}`);
      Swal.fire({
        title: "Deleted!",
        text: "The comment has been deleted.",
        icon: "success",
        confirmButtonColor: "#FF6B35"
      });
    }
    fetchComments(comments.value.current_page);
  })
};

const publishReview = async (id) => {

  Swal.fire({
    title: "Confirm publishing",
    text: "Do you really want to publish this review ?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#FF6B35",
    cancelButtonColor: "#1E2A38",
    confirmButtonText: "Yes, publish"
  }).then(async (result) => {
    if (result.isConfirmed) {
      await api.patch(`/admin/reviews/${id}/publish`);
      Swal.fire({
        title: "Published!",
        text: "The review has been published.",
        icon: "success",
        confirmButtonColor: "#FF6B35"
      });
    }
    fetchReviews(reviews.value.current_page);
  });
};

const unpublishReview = async (id) => {

  Swal.fire({
    title: "Confirm unpublishing",
    text: "Do you really want to unpublish this review ?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#FF6B35",
    cancelButtonColor: "#1E2A38",
    confirmButtonText: "Yes, unplublish"
  }).then(async (result) => {
    if (result.isConfirmed) {
      await api.patch(`/admin/reviews/${id}/unpublish`);
      Swal.fire({
        title: "Deleted!",
        text: "The comment has been unpublished.",
        icon: "success",
        confirmButtonColor: "#FF6B35"
      });
    }
    fetchReviews(reviews.value.current_page);
  });
};

const deleteReview = async (id) => {

  Swal.fire({
    title: "Confirm deletion",
    text: "Do you really want to delete this review ? This action is irreversible",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#FF6B35",
    cancelButtonColor: "#1E2A38",
    confirmButtonText: "Yes, delete"
  }).then(async (result) => {
    if (result.isConfirmed) {
      await api.delete(`/admin/reviews/${id}`);
      Swal.fire({
        title: "Deleted!",
        text: "The review has been deleted.",
        icon: "success",
        confirmButtonColor: "#FF6B35"
      });
    }
    fetchReviews(reviews.value.current_page);
  });
};

const banUser = async (id) => {

  Swal.fire({
    title: "Confirm banning",
    text: "Do you really want to ban this user ?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#FF6B35",
    cancelButtonColor: "#1E2A38",
    confirmButtonText: "Yes, ban him"
  }).then(async (result) => {
    if (result.isConfirmed) {
      await api.patch(`/admin/users/${id}/ban`);
      Swal.fire({
        title: "Banned!",
        text: "The user has been banned.",
        icon: "success",
        confirmButtonColor: "#FF6B35"
      });
    }
    fetchUsers(users.value.current_page);
  });
};

const unbanUser = async (id) => {
  Swal.fire({
    title: "Confirm reintegration",
    text: "Do you really want to reintegrate this user into the community ?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#FF6B35",
    cancelButtonColor: "#1E2A38",
    confirmButtonText: "Yes, reintegrate him"
  }).then(async (result) => {
    if (result.isConfirmed) {
      await api.patch(`/admin/users/${id}/unban`);
      Swal.fire({
        title: "Reintegrated!",
        text: "The user has been reintegrated successfully.",
        icon: "success",
        confirmButtonColor: "#FF6B35"
      });
    }
    fetchUsers(users.value.current_page);
  });
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
