<template>
  <div class="w-full">
    <div class="w-full px-4 xl:px-6 py-6 pb-24">
      <!-- Bandeau -->
      <header class="mb-6 p-6 bg-blue-night text-white rounded-2xl shadow-lg shadow-blue-night/10">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <h1 class="text-2xl md:text-3xl font-poppins font-bold">Administration</h1>
            <p class="mt-1 text-sm text-white/75">Gestion de la communauté YOWL</p>
          </div>
          <div v-if="pendingReports" class="flex items-center gap-2 px-4 py-2 rounded-xl bg-orange-primary/20 text-orange-200">
            <Icon name="flag" />
            <span class="text-sm font-medium">
              {{ pendingReports }} signalement{{ pendingReports > 1 ? 's' : '' }} en attente
            </span>
          </div>
        </div>
      </header>

      <!-- Onglets.
           Ils passent a la ligne au lieu de defiler horizontalement : a neuf
           onglets, la barre demandait plus de largeur que l'ecran et les
           derniers, dont les reglages, restaient hors champ sans que rien ne
           l'indique. -->
      <nav class="flex flex-wrap gap-1 mb-6" aria-label="Sections d'administration">
        <button v-for="tab in tabs" :key="tab.key" type="button"
          class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium transition-colors cursor-pointer"
          :class="activeTab === tab.key
            ? 'bg-orange-primary text-white shadow-md shadow-orange-primary/30'
            : 'text-gray-500 hover:text-blue-night hover:bg-gray-100'"
          :aria-current="activeTab === tab.key ? 'page' : undefined" @click="selectTab(tab.key)">
          <Icon :name="tab.icon" />
          {{ tab.label }}
          <span v-if="tab.badge"
            class="min-w-5 h-5 px-1.5 rounded-full text-[11px] font-bold grid place-items-center"
            :class="activeTab === tab.key ? 'bg-white text-orange-text' : 'bg-orange-primary text-white'">
            {{ tab.badge }}
          </span>
        </button>
      </nav>

      <!-- ===== VUE D'ENSEMBLE ===== -->
      <section v-if="activeTab === 'overview'">
        <div v-if="loading.stats" class="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <div v-for="n in 4" :key="n" class="h-28 rounded-2xl bg-gray-100 animate-pulse"></div>
        </div>

        <div v-else-if="stats">
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <article v-for="card in statCards" :key="card.label"
              class="p-5 bg-white rounded-2xl border border-gray-200 shadow-sm">
              <div class="flex items-center justify-between">
                <span class="w-11 h-11 rounded-xl grid place-items-center" :class="card.tone">
                  <Icon :name="card.icon" :size="20" :class="card.teinte" />
                </span>
                <span class="text-3xl font-bold text-blue-night">{{ card.value }}</span>
              </div>
              <p class="mt-3 text-sm text-gray-500">{{ card.label }}</p>
            </article>
          </div>

          <div class="mt-6 bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <h2 class="px-5 py-4 border-b border-gray-100 font-semibold text-blue-night">Derniers avis</h2>
            <ul v-if="stats.latest_reviews?.length" class="divide-y divide-gray-50">
              <li v-for="review in stats.latest_reviews" :key="review.id"
                class="px-5 py-3 flex items-start gap-3 hover:bg-gray-50 transition-colors">
                <span class="shrink-0 mt-0.5 px-2 py-0.5 rounded-md bg-gray-100 text-xs text-gray-500">
                  #{{ review.id }}
                </span>
                <p class="flex-1 min-w-0 text-sm text-gray-700">{{ truncate(review.content, 110) }}</p>
                <span class="shrink-0 text-xs text-gray-500">{{ formatDate(review.created_at) }}</span>
              </li>
            </ul>
            <p v-else class="px-5 py-8 text-center text-sm text-gray-500">Aucun avis pour le moment.</p>
          </div>
        </div>

        <p v-else class="py-10 text-center text-sm text-gray-500">
          Statistiques indisponibles.
          <button type="button" class="text-orange-text hover:underline cursor-pointer" @click="fetchStats">
            Réessayer
          </button>
        </p>
      </section>

      <!-- ===== MODERATION ===== -->
      <section v-else-if="activeTab === 'reports'" class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-b border-gray-100">
          <h2 class="font-semibold text-blue-night">File de modération</h2>
          <div class="flex gap-1">
            <button v-for="filter in reportFilters" :key="filter.value" type="button"
              class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors cursor-pointer"
              :class="reportStatus === filter.value ? 'bg-blue-night text-white' : 'text-gray-500 hover:bg-gray-100'"
              @click="changeReportFilter(filter.value)">
              {{ filter.label }}
            </button>
          </div>
        </div>

        <TableSkeleton v-if="loading.reports" />

        <template v-else-if="reports?.data?.length">
          <ul class="divide-y divide-gray-100">
            <li v-for="report in reports.data" :key="report.id" class="p-5">
              <div class="flex flex-wrap items-center gap-2 mb-2">
                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-600">
                  {{ reasonLabel(report.reason) }}
                </span>
                <span class="px-2.5 py-1 rounded-full text-xs font-medium" :class="reportStatusTone(report.status)">
                  {{ reportStatusLabel(report.status) }}
                </span>
                <span class="text-xs text-gray-500">
                  {{ report.reportable_type?.includes('Comment') ? 'Commentaire' : 'Avis' }}
                  #{{ report.reportable_id }} — signalé par {{ report.user?.username || 'membre supprimé' }}
                  le {{ formatDate(report.created_at) }}
                </span>
              </div>

              <blockquote class="p-3 rounded-xl bg-gray-50 border border-gray-100 text-sm text-gray-700">
                {{ report.reportable?.content
                  ? truncate(report.reportable.content, 220)
                  : 'Contenu supprimé ou introuvable.' }}
              </blockquote>

              <p v-if="report.details" class="mt-2 text-sm text-gray-500">
                <span class="font-medium text-blue-night">Précisions :</span> {{ report.details }}
              </p>

              <div v-if="report.status === 'pending'" class="flex flex-wrap gap-2 mt-3">
                <button type="button"
                  class="px-3 py-1.5 rounded-lg text-xs font-medium bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors cursor-pointer"
                  @click="resolveReport(report, 'dismissed', false)">
                  Rejeter le signalement
                </button>
                <button type="button"
                  class="px-3 py-1.5 rounded-lg text-xs font-medium bg-red-500 text-white hover:bg-red-600 transition-colors cursor-pointer"
                  @click="resolveReport(report, 'actioned', true)">
                  Supprimer le contenu
                </button>
                <button type="button"
                  class="px-3 py-1.5 rounded-lg text-xs font-medium bg-blue-night text-white hover:bg-blue-night/90 transition-colors cursor-pointer"
                  @click="resolveReport(report, 'actioned', false)">
                  Marquer traité
                </button>
              </div>
              <p v-else class="mt-3 text-xs text-gray-500">
                Traité par {{ report.handler?.username || 'un administrateur' }}
                {{ report.handled_at ? 'le ' + formatDate(report.handled_at) : '' }}
              </p>
            </li>
          </ul>
          <Pagination v-if="reports.last_page > 1" :pagination="reports" @changePage="fetchReports" />
        </template>

        <EmptyState v-else icon="shield-halved" title="Rien à modérer"
          description="Aucun signalement ne correspond à ce filtre." />
      </section>

      <!-- ===== MEMBRES ===== -->
      <section v-else-if="activeTab === 'users'" class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-b border-gray-100">
          <h2 class="font-semibold text-blue-night">Membres</h2>
          <div class="flex flex-wrap items-center gap-2">
            <input v-model="userSearch" type="search" placeholder="Rechercher un membre..."
              class="w-full sm:w-64 px-3 py-2 text-sm bg-gray-100 focus:bg-white border border-transparent focus:border-orange-primary rounded-lg outline-none transition-colors"
              @keyup.enter="fetchUsers(1)">
            <BaseButton variant="primary" size="sm" icon="user-plus" @click="isCreateUserOpen = true">
              Créer un membre
            </BaseButton>
          </div>
        </div>

        <TableSkeleton v-if="loading.users" />

        <template v-else-if="users?.data?.length">
          <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                <tr>
                  <th class="px-5 py-3 font-medium">Membre</th>
                  <th class="px-5 py-3 font-medium">Email</th>
                  <th class="px-5 py-3 font-medium">Rôle</th>
                  <th class="px-5 py-3 font-medium">Statut</th>
                  <th class="px-5 py-3 font-medium text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="user in users.data" :key="user.id"
                  class="hover:bg-gray-50 transition-colors cursor-pointer"
                  tabindex="0" :aria-label="'Ouvrir la fiche de ' + user.username"
                  @click="openUserDetail(user.id)" @keyup.enter="openUserDetail(user.id)">
                  <td class="px-5 py-3">
                    <div class="flex items-center gap-3">
                      <img v-if="user.picture" :src="getStorageUrl(user.picture)" alt=""
                        class="w-9 h-9 rounded-full object-cover shrink-0" />
                      <span v-else
                        class="w-9 h-9 rounded-full bg-blue-night grid place-items-center text-white text-xs font-bold shrink-0">
                        {{ (user.username || '?').slice(0, 2).toUpperCase() }}
                      </span>
                      <span class="min-w-0">
                        <span class="block font-medium text-blue-night truncate">{{ user.username }}</span>
                        <span class="block text-xs text-gray-500">#{{ user.id }}</span>
                      </span>
                    </div>
                  </td>
                  <td class="px-5 py-3 text-gray-500">{{ user.email }}</td>
                  <td class="px-5 py-3">
                    <span class="px-2.5 py-1 rounded-full text-xs font-medium capitalize"
                      :class="isAdminUser(user) ? 'bg-purple-50 text-purple-600' : 'bg-gray-100 text-gray-500'">
                      {{ roleName(user) }}
                    </span>
                  </td>
                  <td class="px-5 py-3">
                    <span class="px-2.5 py-1 rounded-full text-xs font-medium"
                      :class="user.is_active ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'">
                      {{ user.is_active ? 'Actif' : 'Banni' }}
                    </span>
                  </td>
                  <td class="px-5 py-3" @click.stop>
                    <div class="flex justify-end gap-2">
                      <button type="button" :class="actionNeutral" @click="openUserDetail(user.id)">
                        Voir la fiche
                      </button>
                      <button v-if="user.is_active" type="button" :class="actionDanger" @click="banUser(user.id)">
                        Bannir
                      </button>
                      <button v-else type="button" :class="actionSuccess" @click="unbanUser(user.id)">
                        Réintégrer
                      </button>
                      <button v-if="user.is_active" type="button" :class="actionNeutral" @click="toggleRole(user)">
                        {{ isAdminUser(user) ? 'Passer client' : 'Passer admin' }}
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination v-if="users.last_page > 1" :pagination="users" @changePage="fetchUsers" />
        </template>

        <EmptyState v-else icon="users" title="Aucun membre"
          description="Aucun membre ne correspond à cette recherche." />
      </section>

      <!-- ===== REVIEWS ===== -->
      <section v-else-if="activeTab === 'reviews'" class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-b border-gray-100">
          <h2 class="font-semibold text-blue-night">Avis</h2>
          <input v-model="reviewSearch" type="search" placeholder="Rechercher un avis..."
            class="w-full sm:w-64 px-3 py-2 text-sm bg-gray-100 focus:bg-white border border-transparent focus:border-orange-primary rounded-lg outline-none transition-colors"
            @keyup.enter="fetchReviews(1)">
        </div>

        <TableSkeleton v-if="loading.reviews" />

        <template v-else-if="reviews?.data?.length">
          <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                <tr>
                  <th class="px-5 py-3 font-medium">Auteur</th>
                  <th class="px-5 py-3 font-medium">Contenu</th>
                  <th class="px-5 py-3 font-medium">État</th>
                  <th class="px-5 py-3 font-medium text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="review in reviews.data" :key="review.id" class="hover:bg-gray-50 transition-colors">
                  <td class="px-5 py-3">
                    <span class="font-medium text-blue-night">{{ review.user?.username || '-' }}</span>
                    <span class="block text-xs text-gray-500">#{{ review.id }}</span>
                  </td>
                  <td class="px-5 py-3 text-gray-600 max-w-md">{{ truncate(review.content, 90) }}</td>
                  <td class="px-5 py-3">
                    <span class="px-2.5 py-1 rounded-full text-xs font-medium"
                      :class="review.is_published ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'">
                      {{ review.is_published ? 'Publiée' : 'Dépubliée' }}
                    </span>
                  </td>
                  <td class="px-5 py-3">
                    <div class="flex justify-end gap-2">
                      <button v-if="review.is_published" type="button" :class="actionNeutral"
                        @click="unpublishReview(review.id)">
                        Dépublier
                      </button>
                      <button v-else type="button" :class="actionSuccess" @click="publishReview(review.id)">
                        Publier
                      </button>
                      <button type="button" :class="actionDanger" @click="deleteReview(review.id)">
                        Supprimer
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination v-if="reviews.last_page > 1" :pagination="reviews" @changePage="fetchReviews" />
        </template>

        <EmptyState v-else icon="newspaper" title="Aucun avis"
          description="Aucun avis ne correspond à cette recherche." />
      </section>

      <!-- ===== COMMENTAIRES ===== -->
      <section v-else-if="activeTab === 'comments'" class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-b border-gray-100">
          <h2 class="font-semibold text-blue-night">Commentaires</h2>
          <input v-model="commentSearch" type="search" placeholder="Rechercher un commentaire..."
            class="w-full sm:w-64 px-3 py-2 text-sm bg-gray-100 focus:bg-white border border-transparent focus:border-orange-primary rounded-lg outline-none transition-colors"
            @keyup.enter="fetchComments(1)">
        </div>

        <TableSkeleton v-if="loading.comments" />

        <template v-else-if="comments?.data?.length">
          <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                <tr>
                  <th class="px-5 py-3 font-medium">Auteur</th>
                  <th class="px-5 py-3 font-medium">Avis</th>
                  <th class="px-5 py-3 font-medium">Contenu</th>
                  <th class="px-5 py-3 font-medium text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="comment in comments.data" :key="comment.id" class="hover:bg-gray-50 transition-colors">
                  <td class="px-5 py-3">
                    <span class="font-medium text-blue-night">{{ comment.user?.username || '-' }}</span>
                    <span class="block text-xs text-gray-500">#{{ comment.id }}</span>
                  </td>
                  <td class="px-5 py-3 text-gray-500">#{{ comment.review_id }}</td>
                  <td class="px-5 py-3 text-gray-600 max-w-md">{{ truncate(comment.content, 90) }}</td>
                  <td class="px-5 py-3">
                    <div class="flex justify-end">
                      <button type="button" :class="actionDanger" @click="deleteComment(comment.id)">
                        Supprimer
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination v-if="comments.last_page > 1" :pagination="comments" @changePage="fetchComments" />
        </template>

        <EmptyState v-else icon="comments" title="Aucun commentaire"
          description="Aucun commentaire ne correspond à cette recherche." />
      </section>

      <!-- ===== SUGGESTIONS ===== -->
      <section v-else-if="activeTab === 'suggestions'" class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 space-y-3">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
              <h2 class="font-semibold text-blue-night">Suggestions des membres</h2>
              <p class="text-xs text-gray-500 mt-0.5">
                Idées et retours envoyés par le formulaire. Les signalements de contenu sont dans l'onglet Modération.
              </p>
            </div>
            <div class="flex gap-1">
              <button v-for="filter in suggestionFilters" :key="filter.value" type="button"
                class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors cursor-pointer"
                :class="suggestionStatus === filter.value ? 'bg-blue-night text-white' : 'text-gray-500 hover:bg-gray-100'"
                @click="changeSuggestionFilter(filter.value)">
                {{ filter.label }}
              </button>
            </div>
          </div>

          <!-- Filtre par sujet -->
          <div class="flex flex-wrap gap-1.5">
            <button v-for="option in subjectFilters" :key="option.value" type="button"
              class="px-2.5 py-1 rounded-full text-xs font-medium border transition-colors cursor-pointer"
              :class="suggestionSubject === option.value
                ? 'border-orange-primary bg-orange-50 text-orange-text'
                : 'border-gray-200 text-gray-500 hover:border-gray-300'"
              @click="changeSuggestionSubject(option.value)">
              {{ option.label }}
            </button>
          </div>
        </div>

        <TableSkeleton v-if="loading.suggestions" />

        <template v-else-if="suggestions?.data?.length">
          <ul class="divide-y divide-gray-100">
            <li v-for="suggestion in suggestions.data" :key="suggestion.id" class="p-5">
              <div class="flex flex-wrap items-center gap-2 mb-2">
                <span class="px-2.5 py-1 rounded-full text-xs font-medium" :class="suggestionStatusTone(suggestion.status)">
                  {{ suggestionStatusLabel(suggestion.status) }}
                </span>
                <span v-if="suggestion.subject"
                  class="px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                  <Icon :name="subjectIcon(suggestion.subject)" class="mr-1" />{{ subjectLabel(suggestion.subject) }}
                </span>
                <span class="text-xs text-gray-500">
                  {{ suggestion.user?.username || suggestion.name || 'Anonyme' }}
                  <template v-if="suggestion.email">— {{ suggestion.email }}</template>
                  — {{ formatDate(suggestion.created_at) }}
                </span>
              </div>

              <p class="text-sm text-gray-700 whitespace-pre-line">{{ suggestion.message }}</p>

              <div class="flex flex-wrap gap-2 mt-3">
                <button v-if="suggestion.status !== 'read'" type="button" :class="actionNeutral"
                  @click="updateSuggestion(suggestion, 'read')">
                  Marquer lue
                </button>
                <button v-if="suggestion.status !== 'archived'" type="button" :class="actionNeutral"
                  @click="updateSuggestion(suggestion, 'archived')">
                  Archiver
                </button>
              </div>
            </li>
          </ul>
          <Pagination v-if="suggestions.last_page > 1" :pagination="suggestions" @changePage="fetchSuggestions" />
        </template>

        <EmptyState v-else icon="lightbulb" title="Aucune suggestion"
          description="Les idées envoyées par les membres apparaîtront ici." />
      </section>

      <!-- ===== PAGES LEGALES ===== -->
      <AdminGrowth v-else-if="activeTab === 'growth'" />

      <AdminAnalytics v-else-if="activeTab === 'audience'" />

      <AdminCampaigns v-else-if="activeTab === 'campaigns'" />

      <AdminAppeals v-else-if="activeTab === 'appeals'" />

      <AdminLegalPages v-else-if="activeTab === 'legal'" />

      <!-- ===== REGLAGES ===== -->
      <AdminSettings v-else-if="activeTab === 'settings'" />

      <!-- ===== ROLES ET DROITS ===== -->
      <AdminRoles v-else-if="activeTab === 'roles'" />

      <!-- ===== JOURNAL ===== -->
      <AdminAuditLog v-else-if="activeTab === 'audit'" />
    </div>

    <CreateUserModal :is-open="isCreateUserOpen" @close="isCreateUserOpen = false"
      @created="onUserCreated" />

    <UserDetailModal :is-open="detailUserId !== null" :user-id="detailUserId"
      @close="detailUserId = null" @updated="fetchUsers(users?.current_page || 1)" />
  </div>
</template>

<script setup>
import Pagination from '@/components/layouts/Pagination.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import TableSkeleton from '@/components/ui/TableSkeleton.vue';
import { computed, defineAsyncComponent, onMounted, ref } from 'vue';
import { useNotify } from '@/composables/useNotify';
import { useConfirm } from '@/composables/useConfirm';
import api from '@/services/apiService';
import { getStorageUrl } from '@/config';
import BaseButton from '@/components/ui/BaseButton.vue';
import { useAppealStore } from '@/stores/appeal';

import Icon from '@/components/ui/Icon.vue';
// Panneaux chargés à l'ouverture de leur onglet. L'éditeur de texte et
// la bibliothèque de graphiques pèsent à eux deux la moitié de cette
// console, et un modérateur qui traite des signalements n'en ouvre aucun.
const AdminGrowth = defineAsyncComponent(() => import('@/components/admin/AdminGrowth.vue'));
const AdminAnalytics = defineAsyncComponent(() => import('@/components/admin/AdminAnalytics.vue'));
const AdminCampaigns = defineAsyncComponent(() => import('@/components/admin/AdminCampaigns.vue'));
const AdminLegalPages = defineAsyncComponent(() => import('@/components/admin/AdminLegalPages.vue'));
const AdminAppeals = defineAsyncComponent(() => import('@/components/admin/AdminAppeals.vue'));
const AdminSettings = defineAsyncComponent(() => import('@/components/admin/AdminSettings.vue'));
const AdminRoles = defineAsyncComponent(() => import('@/components/admin/AdminRoles.vue'));
const AdminAuditLog = defineAsyncComponent(() => import('@/components/admin/AdminAuditLog.vue'));
const UserDetailModal = defineAsyncComponent(() => import('@/components/admin/UserDetailModal.vue'));
const CreateUserModal = defineAsyncComponent(() => import('@/components/admin/CreateUserModal.vue'));

const activeTab = ref('overview');
const isCreateUserOpen = ref(false);
const detailUserId = ref(null);

const openUserDetail = (id) => { detailUserId.value = id; };

const onUserCreated = () => {
  fetchUsers(1);
  fetchStats();
};
const notify = useNotify();
const confirm = useConfirm();
// Le compteur de contestations en attente alimente la pastille de l'onglet,
// donc il est charge des l'ouverture de la console, pas de l'onglet.
const appealStore = useAppealStore();

const stats = ref(null);
const users = ref(null);
const reviews = ref(null);
const comments = ref(null);
const reports = ref(null);
const suggestions = ref(null);

const pendingReports = ref(0);
const newSuggestions = ref(0);

const userSearch = ref('');
const reviewSearch = ref('');
const commentSearch = ref('');
const reportStatus = ref('pending');
const suggestionStatus = ref('');
const suggestionSubject = ref('');

const loading = ref({
  stats: true,
  users: false,
  reviews: false,
  comments: false,
  reports: false,
  suggestions: false,
});

const loaded = { users: false, reviews: false, comments: false, reports: false, suggestions: false };

const tabs = computed(() => [
  { key: 'overview', label: "Vue d'ensemble", icon: 'chart-pie' },
  { key: 'growth', label: 'Croissance', icon: 'arrow-trend-up' },
  { key: 'audience', label: 'Audience', icon: 'chart-line' },
  { key: 'reports', label: 'Modération', icon: 'flag', badge: pendingReports.value },
  { key: 'appeals', label: 'Contestations', icon: 'scale-balanced', badge: appealStore.pendingCount },
  { key: 'users', label: 'Membres', icon: 'users' },
  { key: 'reviews', label: 'Avis', icon: 'newspaper' },
  { key: 'comments', label: 'Commentaires', icon: 'comments' },
  { key: 'suggestions', label: 'Suggestions', icon: 'lightbulb', badge: newSuggestions.value },
  { key: 'campaigns', label: 'Campagnes', icon: 'envelope' },
  { key: 'legal', label: 'Pages du site', icon: 'file-lines' },
  { key: 'settings', label: 'Réglages', icon: 'sliders' },
  { key: 'roles', label: 'Rôles et droits', icon: 'user-shield' },
  { key: 'audit', label: 'Journal', icon: 'clipboard-list' },
]);

const statCards = computed(() => [
  { label: 'Membres', value: stats.value?.users ?? 0, icon: 'users', tone: 'bg-blue-50', teinte: 'text-blue-600' },
  { label: 'Avis', value: stats.value?.reviews ?? 0, icon: 'newspaper', tone: 'bg-orange-50', teinte: 'text-orange-text' },
  { label: 'Commentaires', value: stats.value?.comments ?? 0, icon: 'comments', tone: 'bg-emerald-50', teinte: 'text-emerald-600' },
  { label: 'Signalements en attente', value: stats.value?.pending_reports ?? 0, icon: 'flag', tone: 'bg-red-50', teinte: 'text-red-600' },
]);

const reportFilters = [
  { value: 'pending', label: 'En attente' },
  { value: 'actioned', label: 'Traités' },
  { value: 'dismissed', label: 'Rejetés' },
  { value: '', label: 'Tous' },
];

const SUBJECTS = {
  feature: { label: 'Fonctionnalité', icon: 'wand-magic-sparkles' },
  improvement: { label: 'Amélioration', icon: 'arrow-trend-up' },
  bug: { label: 'Dysfonctionnement', icon: 'bug' },
  content: { label: 'Contenu', icon: 'pen-to-square' },
  other: { label: 'Autre', icon: 'comment-dots' },
};

const subjectFilters = [
  { value: '', label: 'Tous les sujets' },
  ...Object.entries(SUBJECTS).map(([value, meta]) => ({ value, label: meta.label })),
];

const subjectLabel = (value) => SUBJECTS[value]?.label ?? value;
const subjectIcon = (value) => SUBJECTS[value]?.icon ?? 'comment-dots';

const suggestionFilters = [
  { value: '', label: 'Toutes' },
  { value: 'new', label: 'Nouvelles' },
  { value: 'read', label: 'Lues' },
  { value: 'archived', label: 'Archivées' },
];

// Styles d'action partages par les tableaux
const ACTION_BASE = 'px-3 py-1.5 rounded-lg text-xs font-medium transition-colors cursor-pointer whitespace-nowrap';
const actionDanger = `${ACTION_BASE} bg-red-50 text-red-600 hover:bg-red-100`;
const actionSuccess = `${ACTION_BASE} bg-emerald-50 text-emerald-600 hover:bg-emerald-100`;
const actionNeutral = `${ACTION_BASE} bg-gray-100 text-gray-600 hover:bg-gray-200`;

const REASON_LABELS = {
  spam: 'Spam',
  harassment: 'Harcèlement',
  hate: 'Haine',
  sexual: 'Contenu sexuel',
  violence: 'Violence',
  misinformation: 'Fausse information',
  other: 'Autre',
};

const truncate = (text, length) =>
  text && text.length > length ? text.substring(0, length) + '...' : text;

const formatDate = (value) =>
  new Date(value).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' });

// Les rôles arrivent soit en chaînes (getRoleNames) soit en objets {name}
const roleName = (user) => {
  const role = user.roles?.[0];
  if (!role) return '-';
  return typeof role === 'string' ? role : role.name;
};

const isAdminUser = (user) => roleName(user) === 'admin';

const reasonLabel = (reason) => REASON_LABELS[reason] || reason;

const reportStatusLabel = (status) =>
  ({ pending: 'En attente', actioned: 'Traité', dismissed: 'Rejeté' })[status] || status;

const reportStatusTone = (status) =>
  ({
    pending: 'bg-amber-50 text-amber-600',
    actioned: 'bg-emerald-50 text-emerald-600',
    dismissed: 'bg-gray-100 text-gray-500',
  })[status] || 'bg-gray-100 text-gray-500';

const suggestionStatusLabel = (status) =>
  ({ new: 'Nouvelle', read: 'Lue', archived: 'Archivée' })[status] || status;

const suggestionStatusTone = (status) =>
  ({
    new: 'bg-orange-50 text-orange-text',
    read: 'bg-blue-50 text-blue-600',
    archived: 'bg-gray-100 text-gray-500',
  })[status] || 'bg-gray-100 text-gray-500';

/**
 * Charge une section a sa premiere ouverture, puis laisse la main aux actions.
 */
const selectTab = (key) => {
  activeTab.value = key;
  if (key === 'users' && !loaded.users) fetchUsers();
  if (key === 'reviews' && !loaded.reviews) fetchReviews();
  if (key === 'comments' && !loaded.comments) fetchComments();
  if (key === 'reports' && !loaded.reports) fetchReports();
  if (key === 'suggestions' && !loaded.suggestions) fetchSuggestions();
};

const fetchStats = async () => {
  loading.value.stats = true;
  try {
    const res = await api.get('/admin/stats');
    stats.value = res.data.data;
  } catch {
    stats.value = null;
  } finally {
    loading.value.stats = false;
  }
};

const fetchUsers = async (page = 1) => {
  loading.value.users = true;
  try {
    const res = await api.get('/admin/users', { params: { page, search: userSearch.value || undefined } });
    users.value = res.data.data;
    loaded.users = true;
  } catch {
    users.value = null;
  } finally {
    loading.value.users = false;
  }
};

const fetchReviews = async (page = 1) => {
  loading.value.reviews = true;
  try {
    const res = await api.get('/admin/reviews', { params: { page, search: reviewSearch.value || undefined } });
    reviews.value = res.data.data;
    loaded.reviews = true;
  } catch {
    reviews.value = null;
  } finally {
    loading.value.reviews = false;
  }
};

const fetchComments = async (page = 1) => {
  loading.value.comments = true;
  try {
    const res = await api.get('/admin/comments', { params: { page, search: commentSearch.value || undefined } });
    comments.value = res.data.data;
    loaded.comments = true;
  } catch {
    comments.value = null;
  } finally {
    loading.value.comments = false;
  }
};

const fetchReports = async (page = 1) => {
  loading.value.reports = true;
  try {
    const res = await api.get('/admin/reports', { params: { page, status: reportStatus.value || undefined } });
    reports.value = res.data.data;
    pendingReports.value = res.data.pending_count ?? 0;
    loaded.reports = true;
  } catch {
    reports.value = null;
  } finally {
    loading.value.reports = false;
  }
};

const fetchSuggestions = async (page = 1) => {
  loading.value.suggestions = true;
  try {
    const res = await api.get('/admin/suggestions', { params: {
      page,
      status: suggestionStatus.value || undefined,
      subject: suggestionSubject.value || undefined,
    } });
    suggestions.value = res.data.data;
    newSuggestions.value = res.data.new_count ?? 0;
    loaded.suggestions = true;
  } catch {
    suggestions.value = null;
  } finally {
    loading.value.suggestions = false;
  }
};

const changeReportFilter = (value) => {
  reportStatus.value = value;
  fetchReports(1);
};

const changeSuggestionSubject = (value) => {
  suggestionSubject.value = value;
  fetchSuggestions(1);
};

const changeSuggestionFilter = (value) => {
  suggestionStatus.value = value;
  fetchSuggestions(1);
};

// Le tableau de bord n'agit qu'apres confirmation : les gestes y sont
// destructeurs ou visibles par toute la communaute.
const confirmAction = ({ title, text, confirmButtonText, tone = 'danger' }) =>
  confirm({ title, message: text, confirmLabel: confirmButtonText, tone });

const notifySuccess = (title) => notify.success(title);

const toggleRole = async (user) => {
  const newRole = isAdminUser(user) ? 'client' : 'admin';
  const confirmed = await confirmAction({
    title: 'Confirmer le changement de rôle',
    text: `Veux-tu vraiment passer ce membre en "${newRole}" ?`,
    confirmButtonText: 'Oui, changer',
  });
  if (confirmed) {
    await api.patch(`/admin/users/${user.id}/role`, { role: newRole });
    notifySuccess('Rôle mis à jour !');
    fetchUsers(users.value?.current_page || 1);
  }
};

const deleteComment = async (id) => {
  const confirmed = await confirmAction({
    title: 'Confirmer la suppression',
    text: 'Veux-tu vraiment supprimer ce commentaire ? Cette action est irréversible.',
    confirmButtonText: 'Oui, supprimer',
  });
  if (confirmed) {
    await api.delete(`/admin/comments/${id}`);
    notifySuccess('Supprimé !');
    fetchComments(comments.value?.current_page || 1);
  }
};

const publishReview = async (id) => {
  const confirmed = await confirmAction({
    title: 'Confirmer la publication',
    text: 'Veux-tu vraiment publier cet avis ?',
    confirmButtonText: 'Oui, publier',
  });
  if (confirmed) {
    await api.patch(`/admin/reviews/${id}/publish`);
    notifySuccess('Publiée !');
    fetchReviews(reviews.value?.current_page || 1);
  }
};

const unpublishReview = async (id) => {
  const confirmed = await confirmAction({
    title: 'Confirmer la dépublication',
    text: 'Veux-tu vraiment dépublier cet avis ?',
    confirmButtonText: 'Oui, dépublier',
  });
  if (confirmed) {
    await api.patch(`/admin/reviews/${id}/unpublish`);
    notifySuccess('Dépubliée !');
    fetchReviews(reviews.value?.current_page || 1);
  }
};

const deleteReview = async (id) => {
  const confirmed = await confirmAction({
    title: 'Confirmer la suppression',
    text: 'Veux-tu vraiment supprimer cet avis ? Cette action est irréversible.',
    confirmButtonText: 'Oui, supprimer',
  });
  if (confirmed) {
    await api.delete(`/admin/reviews/${id}`);
    notifySuccess('Supprimée !');
    fetchReviews(reviews.value?.current_page || 1);
  }
};

const banUser = async (id) => {
  const confirmed = await confirmAction({
    title: 'Confirmer le bannissement',
    text: 'Veux-tu vraiment bannir ce membre ?',
    confirmButtonText: 'Oui, bannir',
  });
  if (confirmed) {
    await api.patch(`/admin/users/${id}/ban`);
    notifySuccess('Banni !');
    fetchUsers(users.value?.current_page || 1);
  }
};

const unbanUser = async (id) => {
  const confirmed = await confirmAction({
    title: 'Confirmer la réintégration',
    text: 'Veux-tu vraiment réintégrer ce membre dans la communauté ?',
    confirmButtonText: 'Oui, réintégrer',
  });
  if (confirmed) {
    await api.patch(`/admin/users/${id}/unban`);
    notifySuccess('Réintégré !');
    fetchUsers(users.value?.current_page || 1);
  }
};

const resolveReport = async (report, status, deleteContent) => {
  const confirmed = await confirmAction({
    title: deleteContent ? 'Supprimer le contenu signalé' : 'Clore le signalement',
    text: deleteContent
      ? 'Le contenu sera supprimé définitivement et le signalement marqué traité.'
      : 'Le signalement sera clos sans toucher au contenu.',
    confirmButtonText: 'Confirmer',
  });
  if (!confirmed) return;

  await api.patch(`/admin/reports/${report.id}`, { status, delete_content: deleteContent });
  notifySuccess('Signalement traité');
  fetchReports(reports.value?.current_page || 1);
};

const updateSuggestion = async (suggestion, status) => {
  await api.patch(`/admin/suggestions/${suggestion.id}`, { status });
  fetchSuggestions(suggestions.value?.current_page || 1);
};

onMounted(() => {
  fetchStats();
  // Les compteurs alimentent les pastilles des onglets des l'arrivee
  fetchReports();
  fetchSuggestions();
  appealStore.loadQueue('pending');
});
</script>
