<template>
  <AppLayout>
    <Head title="Create User" />
    
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Create New User
        </h2>
        <v-btn
          :to="route('users.index')"
          variant="outlined"
          color="grey"
          prepend-icon="mdi-arrow-left"
        >
          Back to Users
        </v-btn>
      </div>
    </template>

    <v-container fluid class="py-8">
      <v-row justify="center">
        <v-col cols="12" md="10" lg="8">
          <v-card elevation="2" style="border-radius: 12px;">
            <v-card-title class="text-h5 pa-6 bg-primary text-white">
              <v-icon left class="mr-2">mdi-account-plus</v-icon>
              Create New User
            </v-card-title>
            
            <v-card-text class="pa-6">
              <v-form @submit.prevent="submit">
                <v-row>
                  <!-- Basic Information -->
                  <v-col cols="12">
                    <h3 class="text-h6 mb-4 text-primary">Basic Information</h3>
                  </v-col>

                  <!-- First Name -->
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.first_name"
                      label="First Name"
                      variant="outlined"
                      required
                      :error-messages="errors.first_name"
                      prepend-inner-icon="mdi-account"
                      placeholder="Enter first name"
                    ></v-text-field>
                  </v-col>

                  <!-- Last Name -->
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.last_name"
                      label="Last Name"
                      variant="outlined"
                      required
                      :error-messages="errors.last_name"
                      prepend-inner-icon="mdi-account"
                      placeholder="Enter last name"
                    ></v-text-field>
                  </v-col>

                  <!-- Email -->
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.email"
                      label="Email"
                      type="email"
                      variant="outlined"
                      required
                      :error-messages="errors.email"
                      prepend-inner-icon="mdi-email"
                      placeholder="Enter email address"
                    ></v-text-field>
                  </v-col>

                  <!-- Password -->
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.password"
                      label="Password"
                      :type="showPassword ? 'text' : 'password'"
                      variant="outlined"
                      required
                      :error-messages="errors.password"
                      prepend-inner-icon="mdi-lock"
                      :append-inner-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
                      @click:append-inner="showPassword = !showPassword"
                      placeholder="Enter password"
                    ></v-text-field>
                  </v-col>

                  <!-- Role -->
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="form.role"
                      :items="roleOptions"
                      label="Role"
                      variant="outlined"
                      required
                      :error-messages="errors.role"
                      prepend-inner-icon="mdi-account-group"
                      placeholder="Select role"
                    ></v-select>
                  </v-col>

                  <!-- Manager assignment removed - supervisors can manage all agents -->

                  <!-- Personal Information -->
                  <v-col cols="12">
                    <v-divider class="my-4"></v-divider>
                    <h3 class="text-h6 mb-4 text-primary">Personal Information</h3>
                  </v-col>

                  <!-- Date of Birth -->
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.date_of_birth"
                      label="Date of Birth"
                      type="date"
                      variant="outlined"
                      :error-messages="errors.date_of_birth"
                      prepend-inner-icon="mdi-calendar"
                    ></v-text-field>
                  </v-col>

                  <!-- Identity Card Number -->
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.identity_card_number"
                      label="Identity Card Number"
                      variant="outlined"
                      :error-messages="errors.identity_card_number"
                      prepend-inner-icon="mdi-card-account-details"
                      placeholder="Enter identity card number"
                    ></v-text-field>
                  </v-col>

                  <!-- National Identification Number -->
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.national_identification_number"
                      label="National ID Number"
                      variant="outlined"
                      :error-messages="errors.national_identification_number"
                      prepend-inner-icon="mdi-card-account-details-outline"
                      placeholder="Enter national ID number"
                    ></v-text-field>
                  </v-col>

                  <!-- Started Working At -->
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.started_working_at"
                      label="Started Working At"
                      type="date"
                      variant="outlined"
                      :error-messages="errors.started_working_at"
                      prepend-inner-icon="mdi-calendar-clock"
                    ></v-text-field>
                  </v-col>

                  <!-- Salary Information -->
                  <v-col cols="12">
                    <v-divider class="my-4"></v-divider>
                    <h3 class="text-h6 mb-4 text-primary">Salary Information</h3>
                  </v-col>

                  <!-- Monthly Salary -->
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.monthly_salary"
                      label="Monthly Salary (DZD)"
                      type="number"
                      step="0.01"
                      min="0"
                      variant="outlined"
                      :error-messages="errors.monthly_salary"
                      prepend-inner-icon="mdi-currency-usd"
                      placeholder="0.00"
                      suffix="DZD"
                    ></v-text-field>
                  </v-col>

                  <!-- Payment Day of Month -->
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="form.payment_day_of_month"
                      :items="paymentDayOptions"
                      label="Payment Day of Month"
                      variant="outlined"
                      :error-messages="errors.payment_day_of_month"
                      prepend-inner-icon="mdi-calendar-month"
                      placeholder="Select payment day"
                    ></v-select>
                  </v-col>

                  <!-- Status -->
                  <v-col cols="12" md="6">
                    <v-switch
                      v-model="form.is_active"
                      label="Active User"
                      color="success"
                      :error-messages="errors.is_active"
                      inset
                    ></v-switch>
                  </v-col>

                  <!-- Can Collect StopDesk -->
                  <v-col cols="12" md="6">
                    <v-switch
                      v-model="form.can_collect_stopdesk"
                      label="Can Collect Without Money Case"
                      color="primary"
                      :error-messages="errors.can_collect_stopdesk"
                      inset
                    >
                      <template #label>
                        <div class="d-flex align-center">
                          <v-icon class="mr-2" size="small">mdi-cash-multiple</v-icon>
                          Can Collect Without Money Case
                        </div>
                      </template>
                    </v-switch>
                    <div class="text-caption text-grey-darken-1 mt-1">
                      Allow this user to collect payments at stop desks without selecting a money case
                    </div>
                  </v-col>
                </v-row>

                <!-- Action Buttons -->
                <v-row class="mt-6">
                  <v-col cols="12" class="d-flex justify-end gap-3">
                    <v-btn
                      :to="route('users.index')"
                      variant="outlined"
                      color="grey"
                      size="large"
                    >
                      Cancel
                    </v-btn>
                    <v-btn
                      type="submit"
                      color="primary"
                      size="large"
                      :loading="form.processing"
                      prepend-icon="mdi-content-save"
                    >
                      Create User
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  supervisors: Array,
  errors: Object
})

const showPassword = ref(false)

const form = useForm({
  first_name: '',
  last_name: '',
  email: '',
  password: '',
  role: '',
  manager_id: null,
  date_of_birth: '',
  identity_card_number: '',
  national_identification_number: '',
  started_working_at: '',
  monthly_salary: '',
  payment_day_of_month: null,
  is_active: true,
  can_collect_stopdesk: false
})

const roleOptions = [
  { title: 'Supervisor', value: 'supervisor' },
  { title: 'Agent', value: 'agent' }
]

const supervisorOptions = computed(() => {
  return props.supervisors?.map(supervisor => ({
    title: `${supervisor.first_name} ${supervisor.last_name} (${supervisor.email})`,
    value: supervisor.id
  })) || []
})

const paymentDayOptions = Array.from({ length: 31 }, (_, i) => ({
  title: `Day ${i + 1}`,
  value: i + 1
}))

const submit = () => {
  form.post(route('users.store'), {
    onSuccess: () => {
      // Handle success
    },
    onError: (errors) => {
      console.error('Validation errors:', errors)
    }
  })
}
</script>