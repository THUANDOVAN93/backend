<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="$emit('close')">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6 max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold">{{ user ? 'Edit User' : 'Create User' }}</h2>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <form @submit.prevent="handleSubmit">
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1">Name</label>
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
            />
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Email</label>
            <input
              v-model="form.email"
              type="email"
              required
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
            />
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Status</label>
            <select
              v-model="form.status"
              required
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
            >
              <option v-for="status in statuses" :key="status.value" :value="status.value">
                {{ status.label }}
              </option>
            </select>
          </div>

          <div v-if="!user">
            <label class="block text-sm font-medium mb-1">Password</label>
            <input
              v-model="form.password"
              type="password"
              required
              minlength="8"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
            />
          </div>

          <div v-else>
            <label class="block text-sm font-medium mb-1">Password (leave blank to keep current)</label>
            <input
              v-model="form.password"
              type="password"
              minlength="8"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
            />
          </div>

          <div>
            <label class="block text-sm font-medium mb-2">Roles</label>
            <div class="space-y-2 max-h-48 overflow-y-auto border rounded-lg p-3">
              <label
                v-for="role in roles"
                :key="role.id"
                class="flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded"
              >
                <input
                  v-model="form.roles"
                  type="checkbox"
                  :value="role.name"
                  class="rounded text-blue-600 focus:ring-blue-500"
                />
                <span class="ml-2">{{ role.name }}</span>
              </label>
            </div>
          </div>
        </div>

        <div v-if="error" class="mt-4 p-3 bg-red-50 text-red-700 rounded-lg text-sm">
          {{ error }}
        </div>

        <div class="mt-6 flex gap-3">
          <button
            type="button"
            @click="$emit('close')"
            :disabled="submitting"
            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 transition"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="submitting"
            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition"
          >
            {{ submitting ? 'Saving...' : 'Save' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { UserListItem, UserRole, UserStatus } from '~/stores/userManagement'
import { useUserManagementStore } from '~/stores/userManagement'

const props = defineProps<{
  user: UserListItem | null
  roles: UserRole[]
  statuses: UserStatus[]
}>()

const emit = defineEmits<{
  close: []
  save: []
}>()

const userStore = useUserManagementStore()

const form = reactive({
  name: props.user?.name || '',
  email: props.user?.email || '',
  password: '',
  status: props.user?.status || 'active',
  roles: props.user?.roles.map(r => r.name) || []
})

const submitting = ref(false)
const error = ref('')

async function handleSubmit() {
  submitting.value = true
  error.value = ''

  try {
    const payload: any = {
      name: form.name,
      email: form.email,
      status: form.status,
      roles: form.roles
    }

    if (form.password) {
      payload.password = form.password
    }

    if (props.user) {
      await userStore.updateUser(props.user.ulid, payload)
    } else {
      await userStore.createUser(payload)
    }
    emit('save')
  } catch (e: any) {
    console.error('Error saving user:', e)
    error.value = e?.data?.message || e?.message || 'An error occurred while saving the user'
  } finally {
    submitting.value = false
  }
}
</script>