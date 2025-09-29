<template>
  <AppLayout>
    <Head title="User Details" />
    
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          User Details
        </h2>
        <div class="flex space-x-2">
          <v-btn
            :to="route('users.edit', user.id)"
            variant="outlined"
            color="primary"
            prepend-icon="mdi-pencil"
          >
            Edit User
          </v-btn>
          <v-btn
            :to="route('users.index')"
            variant="outlined"
            color="grey"
            prepend-icon="mdi-arrow-left"
          >
            Back to Users
          </v-btn>
        </div>
      </div>
    </template>

    <v-container fluid class="py-8">
      <v-row>
        <!-- User Profile Card -->
        <v-col cols="12" md="4">
          <v-card elevation="2" style="border-radius: 12px;">
            <v-card-text class="text-center pa-6">
              <v-avatar size="120" color="primary" class="mb-4">
                <span class="text-h3 text-white font-weight-bold">
                  {{ getInitials(user.display_name || user.first_name + ' ' + user.last_name) }}
                </span>
              </v-avatar>
              
              <h2 class="text-h5 font-weight-bold mb-2">
                {{ user.display_name || user.first_name + ' ' + user.last_name }}
              </h2>
              
              <v-chip
                :color="getRoleColor(user.role)"
                variant="tonal"
                size="large"
                class="mb-3"
              >
                <v-icon left>{{ getRoleIcon(user.role) }}</v-icon>
                {{ user.role }}
              </v-chip>
              
              <v-chip
                :color="user.is_active ? 'success' : 'error'"
                variant="tonal"
                size="small"
                class="mb-4"
              >
                {{ user.is_active ? 'Active' : 'Inactive' }}
              </v-chip>
              
              <div class="text-body-2 text--secondary">
                <div class="mb-1">
                  <v-icon size="small" class="mr-1">mdi-email</v-icon>
                  {{ user.email }}
                </div>
                <div v-if="user.started_working_at">
                  <v-icon size="small" class="mr-1">mdi-calendar-clock</v-icon>
                  Started: {{ formatDate(user.started_working_at) }}
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <!-- User Details -->
        <v-col cols="12" md="8">
          <v-row>
            <!-- Basic Information -->
            <v-col cols="12">
              <v-card elevation="2" style="border-radius: 12px;" class="mb-4">
                <v-card-title class="bg-primary text-white">
                  <v-icon left class="mr-2">mdi-account-details</v-icon>
                  Basic Information
                </v-card-title>
                <v-card-text class="pa-6">
                  <v-row>
                    <v-col cols="12" md="6">
                      <div class="mb-4">
                        <span class="text-caption text--secondary">First Name</span>
                        <div class="text-body-1 font-weight-medium">{{ user.first_name || 'Not provided' }}</div>
                      </div>
                    </v-col>
                    <v-col cols="12" md="6">
                      <div class="mb-4">
                        <span class="text-caption text--secondary">Last Name</span>
                        <div class="text-body-1 font-weight-medium">{{ user.last_name || 'Not provided' }}</div>
                      </div>
                    </v-col>
                    <v-col cols="12" md="6">
                      <div class="mb-4">
                        <span class="text-caption text--secondary">Email</span>
                        <div class="text-body-1 font-weight-medium">{{ user.email }}</div>
                      </div>
                    </v-col>
                    <v-col cols="12" md="6">
                      <div class="mb-4">
                        <span class="text-caption text--secondary">Date of Birth</span>
                        <div class="text-body-1 font-weight-medium">{{ user.date_of_birth ? formatDate(user.date_of_birth) : 'Not provided' }}</div>
                      </div>
                    </v-col>
                  </v-row>
                </v-card-text>
              </v-card>
            </v-col>

            <!-- Personal Information -->
            <v-col cols="12">
              <v-card elevation="2" style="border-radius: 12px;" class="mb-4">
                <v-card-title class="bg-info text-white">
                  <v-icon left class="mr-2">mdi-card-account-details</v-icon>
                  Personal Information
                </v-card-title>
                <v-card-text class="pa-6">
                  <v-row>
                    <v-col cols="12" md="6">
                      <div class="mb-4">
                        <span class="text-caption text--secondary">Identity Card Number</span>
                        <div class="text-body-1 font-weight-medium">{{ user.identity_card_number || 'Not provided' }}</div>
                      </div>
                    </v-col>
                    <v-col cols="12" md="6">
                      <div class="mb-4">
                        <span class="text-caption text--secondary">National ID Number</span>
                        <div class="text-body-1 font-weight-medium">{{ user.national_identification_number || 'Not provided' }}</div>
                      </div>
                    </v-col>
                    <v-col cols="12" md="6">
                      <div class="mb-4">
                        <span class="text-caption text--secondary">Started Working At</span>
                        <div class="text-body-1 font-weight-medium">{{ user.started_working_at ? formatDate(user.started_working_at) : 'Not provided' }}</div>
                      </div>
                    </v-col>
                    <v-col cols="12" md="6">
                      <div class="mb-4">
                        <span class="text-caption text--secondary">Account Created</span>
                        <div class="text-body-1 font-weight-medium">{{ formatDateTime(user.created_at) }}</div>
                      </div>
                    </v-col>
                  </v-row>
                </v-card-text>
              </v-card>
            </v-col>

            <!-- Salary Information -->
            <v-col cols="12">
              <v-card elevation="2" style="border-radius: 12px;" class="mb-4">
                <v-card-title class="bg-success text-white">
                  <v-icon left class="mr-2">mdi-currency-usd</v-icon>
                  Salary Information
                </v-card-title>
                <v-card-text class="pa-6">
                  <v-row>
                    <v-col cols="12" md="6">
                      <div class="mb-4">
                        <span class="text-caption text--secondary">Monthly Salary</span>
                        <div class="text-h6 font-weight-bold text-success">
                          {{ user.monthly_salary ? `${Number(user.monthly_salary).toLocaleString()} DZD` : 'Not set' }}
                        </div>
                      </div>
                    </v-col>
                    <v-col cols="12" md="6">
                      <div class="mb-4">
                        <span class="text-caption text--secondary">Payment Day of Month</span>
                        <div class="text-body-1 font-weight-medium">
                          {{ user.payment_day_of_month ? `Day ${user.payment_day_of_month}` : 'Not set' }}
                        </div>
                      </div>
                    </v-col>
                  </v-row>
                </v-card-text>
              </v-card>
            </v-col>

            <!-- Manager Information (for agents) -->
            <v-col cols="12" v-if="user.role === 'agent' && user.manager">
              <v-card elevation="2" style="border-radius: 12px;" class="mb-4">
                <v-card-title class="bg-warning text-white">
                  <v-icon left class="mr-2">mdi-account-supervisor</v-icon>
                  Manager Information
                </v-card-title>
                <v-card-text class="pa-6">
                  <div class="d-flex align-center">
                    <v-avatar size="60" color="primary" class="mr-4">
                      <span class="text-h6 text-white font-weight-bold">
                        {{ getInitials(user.manager.display_name || user.manager.first_name + ' ' + user.manager.last_name) }}
                      </span>
                    </v-avatar>
                    <div>
                      <div class="text-h6 font-weight-bold">
                        {{ user.manager.display_name || user.manager.first_name + ' ' + user.manager.last_name }}
                      </div>
                      <div class="text-body-2 text--secondary">{{ user.manager.email }}</div>
                      <v-chip size="small" color="warning" variant="tonal">
                        {{ user.manager.role }}
                      </v-chip>
                    </div>
                  </div>
                </v-card-text>
              </v-card>
            </v-col>

            <!-- Subordinates (for supervisors) -->
            <v-col cols="12" v-if="user.role === 'supervisor' && user.subordinates && user.subordinates.length > 0">
              <v-card elevation="2" style="border-radius: 12px;">
                <v-card-title class="bg-secondary text-white">
                  <v-icon left class="mr-2">mdi-account-group</v-icon>
                  Subordinates ({{ user.subordinates.length }})
                </v-card-title>
                <v-card-text class="pa-6">
                  <v-row>
                    <v-col cols="12" md="6" v-for="subordinate in user.subordinates" :key="subordinate.id">
                      <v-card variant="outlined" class="mb-3">
                        <v-card-text class="pa-4">
                          <div class="d-flex align-center">
                            <v-avatar size="40" color="primary" class="mr-3">
                              <span class="text-white font-weight-bold">
                                {{ getInitials(subordinate.display_name || subordinate.first_name + ' ' + subordinate.last_name) }}
                              </span>
                            </v-avatar>
                            <div class="flex-grow-1">
                              <div class="font-weight-medium">
                                {{ subordinate.display_name || subordinate.first_name + ' ' + subordinate.last_name }}
                              </div>
                              <div class="text-caption text--secondary">{{ subordinate.email }}</div>
                              <div class="d-flex align-center mt-1">
                                <v-chip size="x-small" color="info" variant="tonal" class="mr-2">
                                  {{ subordinate.role }}
                                </v-chip>
                                <v-chip size="x-small" :color="subordinate.is_active ? 'success' : 'error'" variant="tonal">
                                  {{ subordinate.is_active ? 'Active' : 'Inactive' }}
                                </v-chip>
                              </div>
                            </div>
                            <v-btn
                              :to="route('users.show', subordinate.id)"
                              icon="mdi-eye"
                              size="small"
                              variant="text"
                              color="primary"
                            ></v-btn>
                          </div>
                        </v-card-text>
                      </v-card>
                    </v-col>
                  </v-row>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>
        </v-col>
      </v-row>
    </v-container>
  </AppLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  user: Object
})

const getInitials = (name) => {
  if (!name) return 'U'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

const getRoleColor = (role) => {
  const colors = {
    supervisor: 'warning',
    agent: 'info',
    admin: 'error'
  }
  return colors[role] || 'grey'
}

const getRoleIcon = (role) => {
  const icons = {
    supervisor: 'mdi-account-supervisor',
    agent: 'mdi-account',
    admin: 'mdi-shield-account'
  }
  return icons[role] || 'mdi-account'
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString()
}

const formatDateTime = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleString()
}
</script>