<template>
  <AppLayout>
    <template #title>
      Attendance Management
    </template>
    
    <template #actions>
      <v-btn
        color="primary"
        @click="syncAttendance"
        prepend-icon="mdi-sync"
        :loading="syncing"
        size="large"
        style="border-radius: 8px;"
        class="mr-2"
      >
        Sync Devices
      </v-btn>
      <v-btn
        color="success"
        @click="$inertia.visit('/attendance/devices')"
        prepend-icon="mdi-devices"
        size="large"
        style="border-radius: 8px;"
      >
        Manage Devices
      </v-btn>
    </template>

    <v-container fluid class="pa-6">
      <!-- Month Selector -->
      <v-card class="mb-6" style="border-radius: 12px;" elevation="1">
        <v-card-text>
          <v-row align="center">
            <v-col cols="12" md="4">
              <v-select
                v-model="selectedMonth"
                :items="months"
                label="Select Month"
                variant="outlined"
                density="compact"
                @update:model-value="loadData"
              ></v-select>
            </v-col>
            <v-col cols="12" md="4">
              <v-select
                v-model="selectedYear"
                :items="years"
                label="Select Year"
                variant="outlined"
                density="compact"
                @update:model-value="loadData"
              ></v-select>
            </v-col>
            <v-col cols="12" md="4">
              <v-btn
                @click="exportReport"
                variant="outlined"
                color="primary"
                block
                prepend-icon="mdi-download"
                style="height: 40px;"
              >
                Export Report
              </v-btn>
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>

      <!-- Statistics Cards -->
      <v-row class="mb-6">
        <v-col cols="12" sm="6" md="3">
          <v-card style="border-radius: 12px;" elevation="1">
            <v-card-text class="text-center pa-4">
              <v-avatar color="primary" size="56" class="mb-3">
                <v-icon color="white" size="28">mdi-account-group</v-icon>
              </v-avatar>
              <div class="text-h5 font-weight-bold">{{ totalEmployees }}</div>
              <div class="text-subtitle-2 text--secondary">Total Employees</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" sm="6" md="3">
          <v-card style="border-radius: 12px;" elevation="1">
            <v-card-text class="text-center pa-4">
              <v-avatar color="success" size="56" class="mb-3">
                <v-icon color="white" size="28">mdi-check-circle</v-icon>
              </v-avatar>
              <div class="text-h5 font-weight-bold">{{ averagePresent.toFixed(1) }}%</div>
              <div class="text-subtitle-2 text--secondary">Avg Attendance</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" sm="6" md="3">
          <v-card style="border-radius: 12px;" elevation="1">
            <v-card-text class="text-center pa-4">
              <v-avatar color="info" size="56" class="mb-3">
                <v-icon color="white" size="28">mdi-clock-outline</v-icon>
              </v-avatar>
              <div class="text-h5 font-weight-bold">{{ averageWorkHours.toFixed(1) }}h</div>
              <div class="text-subtitle-2 text--secondary">Avg Work Hours</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" sm="6" md="3">
          <v-card style="border-radius: 12px;" elevation="1">
            <v-card-text class="text-center pa-4">
              <v-avatar color="warning" size="56" class="mb-3">
                <v-icon color="white" size="28">mdi-clock-alert</v-icon>
              </v-avatar>
              <div class="text-h5 font-weight-bold">{{ totalLateDays }}</div>
              <div class="text-subtitle-2 text--secondary">Total Late Days</div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Attendance Table -->
      <v-card style="border-radius: 12px;" elevation="1">
        <v-card-title class="d-flex align-center pa-6">
          <v-icon class="mr-2">mdi-calendar-check</v-icon>
          Monthly Attendance Report
          <v-spacer></v-spacer>
          <v-chip color="primary" variant="outlined">
            {{ monthName }} {{ selectedYear }}
          </v-chip>
        </v-card-title>
        
        <v-data-table
          :headers="headers"
          :items="statistics"
          :loading="loading"
          class="elevation-0"
          item-value="user_id"
          :items-per-page="15"
        >
          <template v-slot:[`item.user`]="{ item }">
            <div class="d-flex align-center">
              <v-avatar color="primary" size="40" class="mr-3">
                <span class="text-white font-weight-bold">
                  {{ getInitials(item.user.full_name) }}
                </span>
              </v-avatar>
              <div>
                <div class="font-weight-medium">{{ item.user.full_name }}</div>
                <div class="text-caption text--secondary">{{ item.user.email }}</div>
              </div>
            </div>
          </template>

          <template v-slot:[`item.total_work_hours`]="{ item }">
            <div class="font-weight-medium">{{ item.total_work_hours }}h</div>
            <div class="text-caption text--secondary" v-if="item.total_overtime_hours > 0">
              +{{ item.total_overtime_hours }}h OT
            </div>
          </template>

          <template v-slot:[`item.present_days`]="{ item }">
            <v-chip
              color="success"
              variant="tonal"
              size="small"
            >
              {{ item.present_days }} days
            </v-chip>
          </template>

          <template v-slot:[`item.absent_days`]="{ item }">
            <v-chip
              :color="item.absent_days > 0 ? 'error' : 'grey'"
              variant="tonal"
              size="small"
            >
              {{ item.absent_days }} days
            </v-chip>
          </template>

          <template v-slot:[`item.late_days`]="{ item }">
            <v-chip
              :color="item.late_days > 0 ? 'warning' : 'grey'"
              variant="tonal"
              size="small"
            >
              {{ item.late_days }} days
            </v-chip>
          </template>

          <template v-slot:[`item.actions`]="{ item }">
            <v-btn
              icon="mdi-eye"
              size="small"
              variant="text"
              color="primary"
              @click="viewDetails(item.user_id)"
            ></v-btn>
          </template>
        </v-data-table>
      </v-card>
    </v-container>

    <!-- Sync Progress Dialog -->
    <v-dialog v-model="syncDialog" max-width="500px" persistent>
      <v-card style="border-radius: 12px;">
        <v-card-title class="text-h5">Syncing Attendance Data</v-card-title>
        <v-card-text>
          <v-progress-linear
            indeterminate
            color="primary"
            class="mb-4"
          ></v-progress-linear>
          <p>Please wait while we sync attendance data from all devices...</p>
        </v-card-text>
      </v-card>
    </v-dialog>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import axios from 'axios'

const props = defineProps({
  statistics: Array,
  year: Number,
  month: Number
})

const loading = ref(false)
const syncing = ref(false)
const syncDialog = ref(false)
const selectedMonth = ref(props.month)
const selectedYear = ref(props.year)

const months = [
  { title: 'January', value: 1 },
  { title: 'February', value: 2 },
  { title: 'March', value: 3 },
  { title: 'April', value: 4 },
  { title: 'May', value: 5 },
  { title: 'June', value: 6 },
  { title: 'July', value: 7 },
  { title: 'August', value: 8 },
  { title: 'September', value: 9 },
  { title: 'October', value: 10 },
  { title: 'November', value: 11 },
  { title: 'December', value: 12 }
]

const years = Array.from({ length: 5 }, (_, i) => {
  const year = new Date().getFullYear() - 2 + i
  return { title: year.toString(), value: year }
})

const headers = [
  { title: 'Employee', key: 'user', sortable: true },
  { title: 'Work Hours', key: 'total_work_hours', sortable: true },
  { title: 'Present Days', key: 'present_days', sortable: true },
  { title: 'Absent Days', key: 'absent_days', sortable: true },
  { title: 'Half Days', key: 'half_days', sortable: true },
  { title: 'Late Days', key: 'late_days', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false, width: '100px' }
]

const monthName = computed(() => {
  return months.find(m => m.value === selectedMonth.value)?.title || ''
})

const totalEmployees = computed(() => {
  return props.statistics.length
})

const averagePresent = computed(() => {
  if (props.statistics.length === 0) return 0
  const totalPresent = props.statistics.reduce((sum, stat) => sum + stat.present_days, 0)
  const daysInMonth = new Date(selectedYear.value, selectedMonth.value, 0).getDate()
  return (totalPresent / (props.statistics.length * daysInMonth)) * 100
})

const averageWorkHours = computed(() => {
  if (props.statistics.length === 0) return 0
  const totalHours = props.statistics.reduce((sum, stat) => sum + stat.total_work_hours, 0)
  return totalHours / props.statistics.length
})

const totalLateDays = computed(() => {
  return props.statistics.reduce((sum, stat) => sum + stat.late_days, 0)
})

const getInitials = (name) => {
  if (!name) return '?'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

const loadData = () => {
  router.visit(`/attendance?year=${selectedYear.value}&month=${selectedMonth.value}`)
}

const viewDetails = (userId) => {
  router.visit(`/attendance/users/${userId}?year=${selectedYear.value}&month=${selectedMonth.value}`)
}

const syncAttendance = async () => {
  syncing.value = true
  syncDialog.value = true
  
  try {
    const response = await axios.post('/attendance/sync')
    
    if (response.data.success) {
      // Reload the page to show updated data
      router.reload()
    }
  } catch (error) {
    console.error('Sync failed:', error)
  } finally {
    syncing.value = false
    syncDialog.value = false
  }
}

const exportReport = () => {
  window.location.href = `/attendance/export?year=${selectedYear.value}&month=${selectedMonth.value}`
}
</script>
