<template>
  <div class="container mx-auto px-4 py-8">
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-3xl font-bold">User Management</h1>
      <button
        @click="openCreateModal"
        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition"
      >
        Add User
      </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium mb-2">Search</label>
          <input
            v-model="searchQuery"
            @input="debouncedSearch"
            type="text"
            placeholder="Search by name or email..."
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          />
        </div>
        <div>
          <label class="block text-sm font-medium mb-2">Filter by Role</label>
          <select
            v-model="selectedRole"
            @change="handleFilterChange"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="">All Roles</option>
            <option v-for="role in roles" :key="role.id" :value="role.name">
              {{ role.name }} ({{ role.users_count }})
            </option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-2">Filter by Status</label>
          <select
            v-model="selectedStatus"
            @change="handleFilterChange"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="">All Statuses</option>
            <option v-for="status in statuses" :key="status.value" :value="status.value">
              {{ status.label }}
            </option>
          </select>
        </div>
        <div class="flex items-end">
          <button
            @click="resetFilters"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
          >
            Reset Filters
          </button>
        </div>
      </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <div v-if="loading" class="p-8 text-center">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-600 border-t-transparent"></div>
        <p class="mt-2 text-gray-600">Loading users...</p>
      </div>

      <table v-else class="w-full">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Roles</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="user in users" :key="user.ulid" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center">
                <img
                  :src="user.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}`"
                  :alt="user.name"
                  class="h-10 w-10 rounded-full"
                />
                <div class="ml-4">
                  <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                </div>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm text-gray-900">{{ user.email }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <StatusBadge :status="user.status" :statuses="statuses" @change="(status) => updateStatus(user.ulid, status)" />
            </td>
            <td class="px-6 py-4">
              <div class="flex flex-wrap gap-1">
                <span
                  v-for="role in user.roles"
                  :key="role.id"
                  class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800"
                >
                  {{ role.name }}
                </span>
                <span v-if="user.roles.length === 0" class="text-sm text-gray-400">No roles</span>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ formatDate(user.created_at) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <button
                @click="openEditModal(user)"
                class="text-blue-600 hover:text-blue-900 mr-4"
              >
                Edit
              </button>
              <button
                @click="confirmDelete(user)"
                class="text-red-600 hover:text-red-900"
              >
                Delete
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Empty State -->
      <div v-if="!loading && users.length === 0" class="p-8 text-center text-gray-500">
        No users found
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.last_page > 1" class="mt-6 flex items-center justify-between">
      <div class="text-sm text-gray-700">
        Showing {{ ((pagination.current_page - 1) * pagination.per_page) + 1 }} to
        {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} of
        {{ pagination.total }} results
      </div>
      <div class="flex gap-2">
        <button
          @click="changePage(pagination.current_page - 1)"
          :disabled="pagination.current_page === 1"
          class="px-4 py-2 border rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
        >
          Previous
        </button>
        <button
          v-for="page in visiblePages"
          :key="page"
          @click="changePage(page)"
          :class="[
            'px-4 py-2 border rounded-lg',
            page === pagination.current_page
              ? 'bg-blue-600 text-white'
              : 'hover:bg-gray-50'
          ]"
        >
          {{ page }}
        </button>
        <button
          @click="changePage(pagination.current_page + 1)"
          :disabled="pagination.current_page === pagination.last_page"
          class="px-4 py-2 border rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
        >
          Next
        </button>
      </div>
    </div>

    <!-- User Modal -->
    <UserModal
      v-if="showModal"
      :user="selectedUser"
      :roles="roles"
      :statuses="statuses"
      @close="closeModal"
      @save="handleSave"
    />

    <!-- Delete Confirmation -->
    <ConfirmDialog
      v-if="showDeleteConfirm"
      title="Delete User"
      :message="`Are you sure you want to delete ${userToDelete?.name}? This action cannot be undone.`"
      @confirm="handleDelete"
      @cancel="showDeleteConfirm.value = false"
    />
  </div>
</template>

<script setup lang="ts">
import { storeToRefs } from 'pinia'
import { useUserManagementStore } from '~/stores/userManagement'
import type { UserListItem } from '~/stores/userManagement'
import { useDebounceFn } from '@vueuse/core'

definePageMeta({
  middleware: 'auth',
  layout: 'admin'
})

const userStore = useUserManagementStore()
const { users, roles, statuses, pagination, loading } = storeToRefs(userStore)

const showModal = ref(false)
const showDeleteConfirm = ref(false)
const selectedUser = ref<UserListItem | null>(null)
const userToDelete = ref<UserListItem | null>(null)
const searchQuery = ref('')
const selectedRole = ref('')
const selectedStatus = ref('')

// Fetch initial data
onMounted(async () => {
  try {
    await Promise.all([
      userStore.fetchUsers(),
      userStore.fetchRoles(),
      userStore.fetchStatuses()
    ])
  } catch (error) {
    console.error('Failed to load initial data:', error)
  }
})

// Debounced search
const debouncedSearch = useDebounceFn(() => {
  userStore.setFilters({ search: searchQuery.value, page: 1 })
}, 500)

function handleFilterChange() {
  userStore.setFilters({ 
    role: selectedRole.value, 
    status: selectedStatus.value,
    page: 1 
  })
}

function resetFilters() {
  searchQuery.value = ''
  selectedRole.value = ''
  selectedStatus.value = ''
  userStore.resetFilters()
}

async function updateStatus(ulid: string, status: string) {
  try {
    await userStore.updateUserStatus(ulid, status)
  } catch (error) {
    console.error('Failed to update status:', error)
  }
}

function openCreateModal() {
  selectedUser.value = null
  showModal.value = true
}

function openEditModal(user: UserListItem) {
  selectedUser.value = user
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  selectedUser.value = null
}

async function handleSave() {
  closeModal()
}

function confirmDelete(user: UserListItem) {
  userToDelete.value = user
  showDeleteConfirm.value = true
}

async function handleDelete() {
  if (userToDelete.value) {
    try {
      await userStore.deleteUser(userToDelete.value.ulid)
      showDeleteConfirm.value = false
      userToDelete.value = null
    } catch (error) {
      console.error('Failed to delete user:', error)
    }
  }
}

function changePage(page: number) {
  userStore.setFilters({ page })
}

const visiblePages = computed(() => {
  const current = pagination.value.current_page
  const last = pagination.value.last_page
  const delta = 2
  const pages = []

  for (let i = Math.max(1, current - delta); i <= Math.min(last, current + delta); i++) {
    pages.push(i)
  }

  return pages
})

function formatDate(date: string) {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}
</script>