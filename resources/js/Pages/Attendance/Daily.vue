<template>
  <AppLayout>
    <template #title>
      Daily Attendance: {{ formatDate(selectedDate) }}
    </template>
    
    <template #actions>
      <v-btn
        color="grey"
        @click="$inertia.visit('/attendance')"
        prepend-icon="mdi-arrow-left"
        variant="outlined"
        size="large"
        style="border-radius: 8px;"
      >
        Back to Dashboard
      </v-btn>
    </template>

    <v-container fluid class="pa-6">
      <!-- Date Selector -->
      <v-card class="mb-6" style="border-radius: 12px;" elevation="1">
        <v-card-text>
          <v-row align="center">
            <v-col cols="12" md="4">
              <v-text-field
                v-model="selectedDate"
                label="Select Date"
                type="date"
                variant="outlined"
                density="compact"
                hide-details
                @change="loadData"
              ></v-text-field>
            </v-col>
            <v-spacer></v-spacer>
            <v-col cols="12" md="4" class="text-right">
              <v-btn
                color="primary"
                prepend-icon="mdi-plus"
                variant="flat"
                @click="openManualPunch"
              >
                Manual Punch
              </v-btn>
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>

      <!-- Summaries Table -->
      <v-card style="border-radius: 12px;" elevation="1">
        <v-card-title class="pa-6 d-flex align-center">
          <v-icon class="mr-2">mdi-account-check</v-icon>
          Daily Summary
        </v-card-title>
        
        <v-data-table
          :headers="headers"
          :items="summaries"
          class="elevation-0"
          :items-per-page="50"
        >
          <template v-slot:[`item.user`]="{ item }">
            <div class="font-weight-medium">{{ item.user.full_name }}</div>
          </template>

          <template v-slot:[`item.first_check_in`]="{ item }">
            {{ item.first_check_in ? formatTime(item.first_check_in) : '-' }}
          </template>

          <template v-slot:[`item.last_check_out`]="{ item }">
            {{ item.last_check_out ? formatTime(item.last_check_out) : '-' }}
          </template>

          <template v-slot:[`item.total_work_minutes`]="{ item }">
            <span class="font-weight-medium">
              {{ (item.total_work_minutes / 60).toFixed(2) }}h
            </span>
          </template>

          <template v-slot:[`item.status`]="{ item }">
            <v-chip
              :color="getStatusColor(item.status)"
              variant="tonal"
              size="small"
            >
              {{ item.status }}
            </v-chip>
          </template>

          <template v-slot:[`item.actions`]="{ item }">
            <v-btn
              icon="mdi-eye"
              variant="text"
              color="primary"
              size="small"
              @click="$inertia.visit(`/attendance/users/${item.user_id}`)"
            ></v-btn>
          </template>
        </v-data-table>
      </v-card>
    </v-container>

    <!-- Manual Punch Dialog -->
    <v-dialog v-model="punchDialog" max-width="500px">
      <v-card style="border-radius: 12px;">
        <v-card-title class="pa-6">Manual Attendance Entry</v-card-title>
        <v-card-text class="pa-6 pt-0">
          <v-form @submit.prevent="submitManualPunch">
            <v-select
              v-model="punchForm.user_id"
              :items="users"
              item-title="full_name"
              item-value="id"
              label="Select Employee *"
              variant="outlined"
              class="mb-4"
            ></v-select>

            <v-text-field
              v-model="punchForm.punch_time"
              label="Time *"
              type="datetime-local"
              variant="outlined"
              class="mb-4"
            ></v-text-field>

            <v-select
              v-model="punchForm.punch_type"
              :items="punchTypes"
              label="Punch Type *"
              variant="outlined"
              class="mb-4"
            ></v-select>

            <v-textarea
              v-model="punchForm.notes"
              label="Notes"
              variant="outlined"
              rows="3"
            ></v-textarea>

            <div class="d-flex justify-end gap-2 mt-4">
              <v-btn color="grey" variant="text" @click="punchDialog = false">Cancel</v-btn>
              <v-btn color="primary" type="submit" :loading="punching">Record Punch</v-btn>
            </div>
          </v-form>
        </v-card-text>
      </v-card>
    </v-dialog>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import axios from 'axios'

const props = defineProps({
  date: String,
  summaries: Array,
  users: Array
})

const selectedDate = ref(props.date)
const punchDialog = ref(false)
const punching = ref(false)

const headers = [
  { title: 'Employee', key: 'user' },
  { title: 'Check In', key: 'first_check_in' },
  { title: 'Check Out', key: 'last_check_out' },
  { title: 'Duty Hours', key: 'total_work_minutes' },
  { title: 'Status', key: 'status' },
  { title: 'Actions', key: 'actions', sortable: false }
]

const punchTypes = [
  { title: 'Check In', value: 'check_in' },
  { title: 'Check Out', value: 'check_out' }
]

const punchForm = ref({
  user_id: null,
  punch_time: '',
  punch_type: 'check_in',
  notes: ''
})

const formatDate = (date) => {
  return new Date(date).toLocaleDateString(undefined, {
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
  })
}

const formatTime = (time) => {
  return new Date(time).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

const getStatusColor = (status) => {
  const colors = { present: 'success', absent: 'error', half_day: 'warning' }
  return colors[status] || 'grey'
}

const loadData = () => {
  router.visit(`/attendance/daily?date=${selectedDate.value}`)
}

const openManualPunch = () => {
  punchDialog.value = true
}

const submitManualPunch = async () => {
  punching.value = true
  try {
    await axios.post('/attendance/manual-punch', punchForm.value)
    router.reload()
    punchDialog.value = false
  } catch (err) {
    console.error(err)
  } finally {
    punching.value = false
  }
}
</script>
