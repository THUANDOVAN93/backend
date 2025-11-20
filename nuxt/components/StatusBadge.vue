<template>
  <div class="relative inline-block">
    <button
      @click="toggleDropdown"
      :class="[
        'px-3 py-1 text-xs font-semibold rounded-full cursor-pointer transition',
        getStatusClass(status)
      ]"
    >
      {{ getStatusLabel(status) }}
      <svg class="inline-block w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
    </button>

    <!-- Dropdown -->
    <div
      v-if="isOpen"
      class="absolute z-10 mt-1 w-40 bg-white rounded-lg shadow-lg border border-gray-200"
    >
      <button
        v-for="s in statuses"
        :key="s.value"
        @click="selectStatus(s.value)"
        :class="[
          'w-full text-left px-4 py-2 text-sm hover:bg-gray-50 first:rounded-t-lg last:rounded-b-lg',
          s.value === status ? 'bg-gray-50 font-semibold' : ''
        ]"
      >
        <span :class="['inline-block w-2 h-2 rounded-full mr-2', `bg-${s.color}-500`]"></span>
        {{ s.label }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { UserStatus } from '~/stores/userManagement'

const props = defineProps<{
  status: string
  statuses: UserStatus[]
}>()

const emit = defineEmits<{
  change: [status: string]
}>()

const isOpen = ref(false)

function toggleDropdown() {
  isOpen.value = !isOpen.value
}

function selectStatus(status: string) {
  emit('change', status)
  isOpen.value = false
}

function getStatusLabel(status: string): string {
  const found = props.statuses.find(s => s.value === status)
  return found?.label || status
}

function getStatusClass(status: string): string {
  const found = props.statuses.find(s => s.value === status)
  const color = found?.color || 'gray'
  
  const classes: Record<string, string> = {
    green: 'bg-green-100 text-green-800 hover:bg-green-200',
    yellow: 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200',
    red: 'bg-red-100 text-red-800 hover:bg-red-200',
    gray: 'bg-gray-100 text-gray-800 hover:bg-gray-200',
  }
  
  return classes[color] || classes.gray
}

// Close dropdown when clicking outside
onMounted(() => {
  document.addEventListener('click', (e) => {
    const target = e.target as HTMLElement
    if (!target.closest('.relative')) {
      isOpen.value = false
    }
  })
})
</script>