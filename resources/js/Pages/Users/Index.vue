<template>
  <AppLayout>
    <template #title>
      User Management
    </template>
    
    <template #actions>
      <v-btn
        color="primary"
        @click="$inertia.visit('/users/create')"
        prepend-icon="mdi-plus"
        size="large"
        style="border-radius: 8px;"
      >
        Create New User
      </v-btn>
    </template>

    <v-container fluid class="pa-6">
      <!-- Statistics Cards -->
      <v-row class="mb-6">
        <v-col cols="12" sm="6" md="3">
          <v-card style="border-radius: 12px;" elevation="1">
            <v-card-text class="text-center pa-4">
              <v-avatar color="primary" size="56" class="mb-3">
                <v-icon color="white" size="28">mdi-account-group</v-icon>
              </v-avatar>
              <div class="text-h5 font-weight-bold">{{ stats.total || 0 }}</div>
              <div class="text-subtitle-2 text--secondary">Total Users</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" sm="6" md="3">
          <v-card style="border-radius: 12px;" elevation="1">
            <v-card-text class="text-center pa-4">
              <v-avatar color="success" size="56" class="mb-3">
                <v-icon color="white" size="28">mdi-account-tie</v-icon>
              </v-avatar>
              <div class="text-h5 font-weight-bold">{{ stats.supervisors || 0 }}</div>
              <div class="text-subtitle-2 text--secondary">Supervisors</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" sm="6" md="3">
          <v-card style="border-radius: 12px;" elevation="1">
            <v-card-text class="text-center pa-4">
              <v-avatar color="info" size="56" class="mb-3">
                <v-icon color="white" size="28">mdi-account</v-icon>
              </v-avatar>
              <div class="text-h5 font-weight-bold">{{ stats.agents || 0 }}</div>
              <div class="text-subtitle-2 text--secondary">Agents</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" sm="6" md="3">
          <v-card style="border-radius: 12px;" elevation="1">
            <v-card-text class="text-center pa-4">
              <v-avatar color="warning" size="56" class="mb-3">
                <v-icon color="white" size="28">mdi-account-check</v-icon>
              </v-avatar>
              <div class="text-h5 font-weight-bold">{{ stats.active || 0 }}</div>
              <div class="text-subtitle-2 text--secondary">Active</div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Filters -->
      <v-card class="mb-6" style="border-radius: 12px;" elevation="1">
        <v-card-text>
          <v-row>
            <v-col cols="12" md="3">
              <v-select
                v-model="filters.role"
                :items="roleOptions"
                label="Filter by Role"
                clearable
                variant="outlined"
                density="compact"
                @update:model-value="applyFilters"
              ></v-select>
            </v-col>
            <v-col cols="12" md="3">
              <v-select
                v-model="filters.status"
                :items="statusOptions"
                label="Filter by Status"
                clearable
                variant="outlined"
                density="compact"
                @update:model-value="applyFilters"
              ></v-select>
            </v-col>
            <v-col cols="12" md="4">
              <v-text-field
                v-model="filters.search"
                label="Search users..."
                prepend-inner-icon="mdi-magnify"
                clearable
                variant="outlined"
                density="compact"
                @update:model-value="applyFilters"
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="2">
              <v-btn
                @click="clearFilters"
                variant="outlined"
                color="grey"
                block
                style="height: 40px;"
              >
                Clear Filters
              </v-btn>
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>

      <!-- Users Table -->
      <v-card style="border-radius: 12px;" elevation="1">
        <v-card-title class="d-flex align-center pa-6">
          <v-icon class="mr-2">mdi-account-group</v-icon>
          Users List
          <v-spacer></v-spacer>
          <v-chip color="primary" variant="outlined">
            {{ filteredUsers.length }} users
          </v-chip>
        </v-card-title>
        
        <v-data-table
          :headers="headers"
          :items="filteredUsers"
          :loading="loading"
          class="elevation-0"
          item-value="id"
          :items-per-page="15"
        >
          <template v-slot:[`item.avatar`]="{ item }">
            <v-avatar color="primary" size="40">
              <span class="text-white font-weight-bold">
                {{ getInitials(item.first_name || item.first_name + ' ' + item.last_name) }}
              </span>
            </v-avatar>
          </template>

          <template v-slot:[`item.name`]="{ item }">
            <div>
              <div class="font-weight-medium">{{ item.first_name }}</div>
              <div class="text-caption text--secondary" v-if="item.first_name || item.last_name">
                {{ item.first_name }} {{ item.last_name }}
              </div>
            </div>
          </template>

          <template v-slot:[`item.role`]="{ item }">
            <v-chip
              :color="getRoleColor(item.role)"
              variant="tonal"
              size="small"
            >
              {{ item.role }}
            </v-chip>
          </template>

          <template v-slot:[`item.companies`]="{ item }">
            <div v-if="item.companies && item.companies.length > 0">
              <v-chip
                v-for="company in item.companies.slice(0, 2)"
                :key="company.id"
                color="primary"
                variant="outlined"
                size="small"
                class="mr-1 mb-1"
              >
                {{ company.name }}
              </v-chip>
              <v-chip
                v-if="item.companies.length > 2"
                color="grey"
                variant="outlined"
                size="small"
                class="mr-1 mb-1"
              >
                +{{ item.companies.length - 2 }} more
              </v-chip>
            </div>
            <span v-else class="text--secondary">No companies</span>
          </template>

          <template v-slot:[`item.manager`]="{ item }">
            <div v-if="item.manager">
              <div class="font-weight-medium">{{ item.manager.first_name }}</div>
              <div class="text-caption text--secondary">
                {{ item.manager.first_name }} {{ item.manager.last_name }}
              </div>
            </div>
            <span v-else class="text--secondary">No Manager</span>
          </template>

          <template v-slot:[`item.monthly_salary`]="{ item }">
            <div v-if="item.monthly_salary && item.role != 'admin'">
              <span class="font-weight-medium">{{ Number(item.monthly_salary).toLocaleString() }}</span>
              <div class="text-caption text--secondary" v-if="item.payment_day_of_month">
                Paid on day {{ item.payment_day_of_month }}
              </div>
            </div>
            <span v-else class="text--secondary">Not set</span>
          </template>

          <template v-slot:[`item.is_active`]="{ item }">
            <v-chip
              :color="item.is_active ? 'success' : 'error'"
              variant="tonal"
              size="small"
            >
              {{ item.is_active ? 'Active' : 'Inactive' }}
            </v-chip>
          </template>

          <template v-slot:[`item.actions`]="{ item }">
            <div class="d-flex gap-1">
              <v-btn
                icon="mdi-eye"
                size="small"
                variant="text"
                color="primary"
                @click="viewUser(item.id)"
              ></v-btn>
              <v-btn
                icon="mdi-pencil"
                size="small"
                variant="text"
                color="warning"
                @click="editUser(item.id)"
              ></v-btn>
              <v-btn
                icon="mdi-delete"
                size="small"
                variant="text"
                color="error"
                @click="confirmDelete(item)"
              ></v-btn>
            </div>
          </template>
        </v-data-table>
      </v-card>
    </v-container>

    <!-- Delete Confirmation Dialog -->
    <v-dialog v-model="deleteDialog" max-width="500px">
      <v-card style="border-radius: 12px;">
        <v-card-title class="text-h5">Confirm Delete</v-card-title>
        <v-card-text>
          Are you sure you want to delete user "{{ userToDelete?.first_name }}"?
          <div v-if="userToDelete?.subordinates?.length > 0" class="mt-2 text-warning">
            <v-icon color="warning" class="mr-1">mdi-alert</v-icon>
            This user manages {{ userToDelete.subordinates.length }} subordinate(s). Please reassign them first.
          </div>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="grey" variant="text" @click="deleteDialog = false">Cancel</v-btn>
          <v-btn 
            color="error" 
            variant="text" 
            @click="deleteUser"
            :disabled="userToDelete?.subordinates?.length > 0"
          >
            Delete
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  users: Array
})

const loading = ref(false)
const deleteDialog = ref(false)
const userToDelete = ref(null)

const filters = ref({
  role: null,
  status: null,
  search: ''
})

const headers = [
  { title: '', key: 'avatar', sortable: false, width: '60px' },
  { title: 'Name', key: 'name', sortable: true },
  { title: 'Email', key: 'email', sortable: true },
  { title: 'Role', key: 'role', sortable: true },
  { title: 'Companies', key: 'companies', sortable: false },
  // { title: 'Manager', key: 'manager', sortable: false },
  { title: 'Salary', key: 'monthly_salary', sortable: true },
  { title: 'Status', key: 'is_active', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false, width: '120px' }
]

const roleOptions = [
  { title: 'Admin', value: 'admin' },
  { title: 'Supervisor', value: 'supervisor' },
  { title: 'Agent', value: 'agent' }
]

const statusOptions = [
  { title: 'Active', value: true },
  { title: 'Inactive', value: false }
]

const stats = computed(() => {
  const total = props.users.length
  const supervisors = props.users.filter(u => u.role === 'supervisor').length
  const agents = props.users.filter(u => u.role === 'agent').length
  const active = props.users.filter(u => u.is_active).length
  
  return { total, supervisors, agents, active }
})

const filteredUsers = computed(() => {
  let filtered = [...props.users]
  
  if (filters.value.role) {
    filtered = filtered.filter(user => user.role === filters.value.role)
  }
  
  if (filters.value.status !== null) {
    filtered = filtered.filter(user => user.is_active === filters.value.status)
  }
  
  if (filters.value.search) {
    const search = filters.value.search.toLowerCase()
    filtered = filtered.filter(user => 
      user.first_name?.toLowerCase().includes(search) ||
      user.email?.toLowerCase().includes(search) ||
      user.first_name?.toLowerCase().includes(search) ||
      user.last_name?.toLowerCase().includes(search)
    )
  }
  
  return filtered
})

const getInitials = (name) => {
  if (!name) return '?'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

const getRoleColor = (role) => {
  const colors = {
    admin: 'purple',
    supervisor: 'success',
    agent: 'info'
  }
  return colors[role] || 'grey'
}

const applyFilters = () => {
  // Filters are reactive, so this just triggers reactivity
}

const clearFilters = () => {
  filters.value = {
    role: null,
    status: null,
    search: ''
  }
}

const viewUser = (id) => {
  router.visit(`/users/${id}`)
}

const editUser = (id) => {
  router.visit(`/users/${id}/edit`)
}

const confirmDelete = (user) => {
  userToDelete.value = user
  deleteDialog.value = true
}

const deleteUser = () => {
  if (userToDelete.value) {
    router.delete(`/users/${userToDelete.value.id}`, {
      onSuccess: () => {
        deleteDialog.value = false
        userToDelete.value = null
      }
    })
  }
}
</script>