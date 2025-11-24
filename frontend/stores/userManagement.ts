import { defineStore } from 'pinia'

export type UserStatus = {
    value: string;
    label: string;
    color: string;
}

export type UserRole = {
    id: number;
    name: string;
    users_count?: number;
}

export type UserListItem = {
    ulid: string;
    name: string;
    email: string;
    avatar: string;
    status: string;
    roles: UserRole[];
    created_at: string;
}

export type PaginationMeta = {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

export const useUserManagementStore = defineStore('userManagement', () => {
    const users = ref<UserListItem[]>([]);
    const roles = ref<UserRole[]>([]);
    const statuses = ref<UserStatus[]>([]);
    const pagination = ref<PaginationMeta>({
        current_page: 1,
        last_page: 1,
        per_page: 5,
        total: 0
    });

    const loading = ref(false);
    const filters = ref({
        search: '',
        role: '',
        status: '',
        page: 1,
        per_page: 5
    });

    async function fetchUsers() {
        loading.value = true;
        try {
            const response = await $http<any>('/users', {
                method: 'GET',
                params: filters.value
            });

            users.value = response.data;
            pagination.value = {
                current_page: response.current_page,
                last_page: response.last_page,
                per_page: response.per_page,
                total: response.total
            };
        } catch (error) {
            console.error('Failed to fetch users:', error);
            throw error;
        } finally {
            loading.value = false;
        }
    }

    async function fetchStatuses() {
        try {
            const response = await $http<any>('/user-statuses', {
                method: 'GET'
            });
            statuses.value = response.statuses;
        } catch (error) {
            console.error('Failed to fetch statuses:', error);
            throw error;
        }
    }

    async function fetchRoles() {
        try {
            const response = await $http<any>('/roles', {
                method: 'GET'
            });
            roles.value = response.roles;
        } catch (error) {
            console.error('Failed to fetch roles:', error);
            throw error;
        }
    }

    async function createUser(userData: any) {
        try {
            const response = await $http<any>('/users', {
                method: 'POST',
                body: userData
            });

            await fetchUsers();
            return response;
        } catch (error: any) {
            throw error;
        }
    }

    async function updateUser(ulid: string, userData: any) {
        try {
            const response = await $http<any>(`/users/${ulid}`, {
                method: 'PUT',
                body: userData
            });

            await fetchUsers();
            return response;
        } catch (error: any) {
            throw error;
        }
    }

    async function deleteUser(ulid: string) {
        try {
            await $http<any>(`/users/${ulid}`, {
                method: 'DELETE'
            });

            await fetchUsers();
        } catch (error: any) {
            throw error;
        }
    }

    async function createRole(roleName: string) {
        try {
            const response = await $http<any>('/roles', {
                method: 'POST',
                body: { name: roleName }
            });

            await fetchRoles();
            return response;
        } catch (error: any) {
            throw error;
        }
    }

    async function updateUserStatus(ulid: string, status: string) {
        try {
            const response = await $http<any>(`/users/${ulid}/status`, {
                method: 'PATCH',
                body: { status }
            });

            await fetchUsers();
            return response;
        } catch (error: any) {
            throw error;
        }
    }

    async function deleteRole(id: number) {
        try {
            await $http<any>(`/roles/${id}`, {
                method: 'DELETE'
            });

            await fetchRoles();
        } catch (error: any) {
            throw error;
        }
    }

    function setFilters(newFilters: Partial<typeof filters.value>) {
        filters.value = { ...filters.value, ...newFilters };
        fetchUsers();
    }

    function resetFilters() {
        filters.value = {
            search: '',
            role: '',
            page: 1,
            per_page: 5
        };
        fetchUsers();
    }

    return {
        users,
        roles,
        statuses,
        pagination,
        loading,
        filters,
        fetchUsers,
        fetchStatuses,
        fetchRoles,
        createUser,
        updateUser,
        updateUserStatus,
        deleteUser,
        createRole,
        deleteRole,
        setFilters,
        resetFilters
    }
})