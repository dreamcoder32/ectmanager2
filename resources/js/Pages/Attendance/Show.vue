<template>
  <AppLayout>
    <template #title>
      {{ user.full_name }} - Attendance Details
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
                @click="exportUserReport"
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

      <!-- Summary Statistics -->
      <v-row class="mb-6">
        <v-col cols="12" sm="6" md="3">
          <v-card style="border-radius: 12px;" elevation="1">
            <v-card-text class="text-center pa-4">
              <v-avatar color="success" size="56" class="mb-3">
                <v-icon color="white" size="28">mdi-check-circle</v-icon>
              </v-avatar>
              <div class="text-h5 font-weight-bold">{{ report.present_days }}</div>
              <div class="text-subtitle-2 text--secondary">Present Days</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" sm="6" md="3">
          <v-card style="border-radius: 12px;" elevation="1">
            <v-card-text class="text-center pa-4">
              <v-avatar color="error" size="56" class="mb-3">
                <v-icon color="white" size="28">mdi-close-circle</v-icon>
              </v-avatar>
              <div class="text-h5 font-weight-bold">{{ report.absent_days }}</div>
              <div class="text-subtitle-2 text--secondary">Absent Days</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" sm="6" md="3">
          <v-card style="border-radius: 12px;" elevation="1">
            <v-card-text class="text-center pa-4">
              <v-avatar color="info" size="56" class="mb-3">
                <v-icon color="white" size="28">mdi-clock-outline</v-icon>
              </v-avatar>
              <div class="text-h5 font-weight-bold">{{ report.total_work_hours }}h</div>
              <div class="text-subtitle-2 text--secondary">Total Work Hours</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" sm="6" md="3">
          <v-card style="border-radius: 12px;" elevation="1">
            <v-card-text class="text-center pa-4">
              <v-avatar color="warning" size="56" class="mb-3">
                <v-icon color="white" size="28">mdi-clock-alert</v-icon>
              </v-avatar>
              <div class="text-h5 font-weight-bold">{{ report.late_days }}</div>
              <div class="text-subtitle-2 text--secondary">Late Days</div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Daily Attendance Records -->
      <v-card style="border-radius: 12px;" elevation="1">
        <v-card-title class="d-flex align-center pa-6">
          <v-icon class="mr-2">mdi-calendar-month</v-icon>
          Daily Attendance Records
          <v-spacer></v-spacer>
          <v-chip color="primary" variant="outlined">
            {{ monthName }} {{ selectedYear }}
          </v-chip>
        </v-card-title>
        
        <v-data-table
          :headers="headers"
          :items="report.summaries"
          :loading="loading"
          class="elevation-0"
          item-value="id"
          :items-per-page="31"
        >
          <template v-slot:[`item.date`]="{ item }">
            <div class="font-weight-medium">{{ formatDate(item.date) }}</div>
            <div class="text-caption text--secondary">{{ getDayName(item.date) }}</div>
          </template>

          <template v-slot:[`item.first_check_in`]="{ item }">
            <div v-if="item.first_check_in">
              {{ formatTime(item.first_check_in) }}
              <v-chip
                v-if="item.late_minutes > 0"
                color="warning"
                variant="tonal"
                size="x-small"
                class="ml-2"
              >
                Late {{ item.late_minutes }}m
              </v-chip>
            </div>
            <span v-else class="text--secondary">-</span>
          </template>

          <template v-slot:[`item.last_check_out`]="{ item }">
            <div v-if="item.last_check_out">
              {{ formatTime(item.last_check_out) }}
              <v-chip
                v-if="item.early_departure_minutes > 0"
                color="warning"
                variant="tonal"
                size="x-small"
                class="ml-2"
              >
                Early {{ item.early_departure_minutes }}m
              </v-chip>
            </div>
            <span v-else class="text--secondary">-</span>
          </template>

          <template v-slot:[`item.total_work_hours`]="{ item }">
            <div class="font-weight-medium">{{ item.total_work_hours }}h</div>
            <div class="text-caption text--secondary" v-if="item.overtime_hours > 0">
              +{{ item.overtime_hours }}h OT
            </div>
          </template>

          <template v-slot:[`item.status`]="{ item }">
            <v-chip
              :color="getStatusColor(item.status)"
              variant="tonal"
              size="small"
            >
              {{ getStatusText(item.status) }}
            </v-chip>
          </template>

          <template v-slot:[`item.notes`]="{ item }">
            <div v-if="item.notes" class="text-caption">{{ item.notes }}</div>
            <v-btn
              v-else
              icon="mdi-note-plus"
              size="x-small"
              variant="text"
              color="grey"
              @click="addNote(item)"
            ></v-btn>
          </template>
        </v-data-table>
      </v-card>
    </v-container>

    <!-- Add Note Dialog -->
    <v-dialog v-model="noteDialog" max-width="500px">
      <v-card style="border-radius: 12px;">
        <v-card-title class="text-h5">Add Note</v-card-title>
        <v-card-text>
          <v-textarea
            v-model="noteText"
            label="Note"
            variant="outlined"
            rows="3"
          ></v-textarea>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="grey" variant="text" @click="noteDialog = false">Cancel</v-btn>
          <v-btn color="primary" variant="text" @click="saveNote">Save</v-btn>
        </v-card-actions>
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
  user: Object,
  report: Object,
  year: Number,
  month: Number
})

const loading = ref(false)
const noteDialog = ref(false)
const noteText = ref('')
const selectedSummary = ref(null)
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
  { title: 'Date', key: 'date', sortable: true },
  { title: 'Check In', key: 'first_check_in', sortable: true },
  { title: 'Check Out', key: 'last_check_out', sortable: true },
  { title: 'Work Hours', key: 'total_work_hours', sortable: true },
  { title: 'Status', key: 'status', sortable: true },
  { title: 'Notes', key: 'notes', sortable: false }
]

const monthName = computed(() => {
  return months.find(m => m.value === selectedMonth.value)?.title || ''
})

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', { 
    month: 'short', 
    day: 'numeric',
    year: 'numeric'
  })
}

const formatTime = (datetime) => {
  return new Date(datetime).toLocaleTimeString('en-US', { 
    hour: '2-digit', 
    minute: '2-digit'
  })
}

const getDayName = (date) => {
  return new Date(date).toLocaleDateString('en-US', { weekday: 'long' })
}

const getStatusColor = (status) => {
  const colors = {
    present: 'success',
    absent: 'error',
    half_day: 'warning',
    leave: 'info',
    holiday: 'grey'
  }
  return colors[status] || 'grey'
}

const getStatusText = (status) => {
  const texts = {
    present: 'Present',
    absent: 'Absent',
    half_day: 'Half Day',
    leave: 'Leave',
    holiday: 'Holiday'
  }
  return texts[status] || status
}

const loadData = () => {
  router.visit(`/attendance/users/${props.user.id}?year=${selectedYear.value}&month=${selectedMonth.value}`)
}

const exportUserReport = () => {
  window.location.href = `/attendance/export?user_id=${props.user.id}&year=${selectedYear.value}&month=${selectedMonth.value}`
}

const addNote = (summary) => {
  selectedSummary.value = summary
  noteText.value = summary.notes || ''
  noteDialog.value = true
}

const saveNote = async () => {
  if (selectedSummary.value) {
    try {
      await axios.put(`/attendance/summaries/${selectedSummary.value.id}/notes`, {
        notes: noteText.value
      })
      
      selectedSummary.value.notes = noteText.value
      noteDialog.value = false
    } catch (error) {
      console.error('Failed to save note:', error)
    }
  }
}
</script>
