<template>
  <div class="flex min-h-screen bg-gray-100">
    <!-- Sidebar (mobile toggle) -->
    <SidebarAdmin v-if="sidebarOpen" @close="sidebarOpen = false" class="fixed z-50 md:static md:block" />

    <!-- Main content -->
    <div class="flex-1 flex flex-col">
      <!-- Header top bar for mobile -->
      <header class="md:hidden bg-[#1E2A38] text-white flex justify-between items-center p-4">
        <h1 class="text-lg font-semibold">Admin Dashboard</h1>
        <button @click="sidebarOpen = true">
          <i class="fa-solid fa-bars text-xl"></i>
        </button>
      </header>

      <main class="p-4 md:p-6 overflow-y-auto flex-1">
        <h1 class="hidden md:block text-2xl font-bold mb-6 text-gray-800">
          Admin Dashboard
        </h1>

        <!-- Dashboard Cards -->
        <div v-if="currentView === 'dashboard'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <DashboardCard title="Total Reviews" :value="125" icon="fa-star" color="bg-orange-500" />
          <DashboardCard title="Total Comments" :value="542" icon="fa-comments" color="bg-blue-500" />
          <DashboardCard title="Total Likes" :value="1200" icon="fa-thumbs-up" color="bg-green-500" />
          <DashboardCard title="Total Views" :value="3500" icon="fa-eye" color="bg-purple-500" />
        </div>

        <!-- Reviews -->
        <ReviewTable v-else-if="currentView === 'reviews'" />

        <!-- Comments -->
        <CommentTable v-else-if="currentView === 'comments'" />

        <!-- Users -->
        <UserTable v-else-if="currentView === 'users'" />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import SidebarAdmin from "@/components/admin/SidebarAdmin.vue";
import DashboardCard from "@/components/admin/DashboardCard.vue";
import ReviewTable from "@/components/admin/ReviewTable.vue";
import CommentTable from "@/components/admin/CommentTable.vue";
import UserTable from "@/components/admin/UserTable.vue";

const currentView = ref("dashboard");
const sidebarOpen = ref(false);
</script>
