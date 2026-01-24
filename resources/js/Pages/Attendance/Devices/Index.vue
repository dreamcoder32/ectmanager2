<template>
  <AppLayout>
    <template #title>
      Attendance Devices
    </template>
    
    <template #actions>
      <v-btn
        color="primary"
        @click="$inertia.visit('/attendance/devices/create')"
        prepend-icon="mdi-plus"
        size="large"
        style="border-radius: 8px;"
      >
        Add New Device
      </v-btn>
    </template>

    <v-container fluid class="pa-6">
      <!-- Statistics Cards -->
      <v-row class="mb-6">
        <v-col cols="12" sm="6" md="4">
          <v-card style="border-radius: 12px;" elevation="1">
            <v-card-text class="text-center pa-4">
              <v-avatar color="primary" size="56" class="mb-3">
                <v-icon color="white" size="28">mdi-devices</v-icon>
              </v-avatar>
              <div class="text-h5 font-weight-bold">{{ devices.length }}</div>
              <div class="text-subtitle-2 text--secondary">Total Devices</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" sm="6" md="4">
          <v-card style="border-radius: 12px;" elevation="1">
            <v-card-text class="text-center pa-4">
              <v-avatar color="success" size="56" class="mb-3">
                <v-icon color="white" size="28">mdi-check-circle</v-icon>
              </v-avatar>
              <div class="text-h5 font-weight-bold">{{ activeDevices }}</div>
              <div class="text-subtitle-2 text--secondary">Active Devices</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" sm="6" md="4">
          <v-card style="border-radius: 12px;" elevation="1">
            <v-card-text class="text-center pa-4">
              <v-avatar color="info" size="56" class="mb-3">
                <v-icon color="white" size="28">mdi-database</v-icon>
              </v-avatar>
              <div class="text-h5 font-weight-bold">{{ totalRecords }}</div>
              <div class="text-subtitle-2 text--secondary">Total Records</div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Devices Table -->
      <v-card style="border-radius: 12px;" elevation="1">
        <v-card-title class="d-flex align-center pa-6">
          <v-icon class="mr-2">mdi-devices</v-icon>
          Registered Devices
        </v-card-title>
        
        <v-data-table
          :headers="headers"
          :items="devices"
          :loading="loading"
          class="elevation-0"
          item-value="id"
          :items-per-page="10"
        >
          <template v-slot:[`item.name`]="{ item }">
            <div>
              <div class="font-weight-medium">{{ item.name }}</div>
              <div class="text-caption text--secondary">{{ item.device_model }}</div>
            </div>
          </template>

          <template v-slot:[`item.company`]="{ item }">
            <div v-if="item.company">
              <div class="font-weight-medium">{{ item.company.name || item.company.commercial_name }}</div>
              <div class="text-caption text--secondary">{{ item.company.email }}</div>
            </div>
            <v-chip v-else color="grey" size="small" variant="tonal">No Company</v-chip>
          </template>

          <template v-slot:[`item.connection`]="{ item }">
            <div>
              <div class="font-weight-medium">{{ item.ip_address }}:{{ item.port }}</div>
              <div class="text-caption text--secondary" v-if="item.location">
                {{ item.location }}
              </div>
            </div>
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

          <template v-slot:[`item.last_sync_at`]="{ item }">
            <div v-if="item.last_sync_at">
              <div class="font-weight-medium">{{ formatDateTime(item.last_sync_at) }}</div>
              <div v-if="item.sync_error" class="text-caption text-error">
                Error: {{ item.sync_error }}
              </div>
            </div>
            <span v-else class="text--secondary">Never synced</span>
          </template>

          <template v-slot:[`item.attendance_records_count`]="{ item }">
            <v-chip
              color="info"
              variant="tonal"
              size="small"
            >
              {{ item.attendance_records_count || 0 }} records
            </v-chip>
          </template>

          <template v-slot:[`item.actions`]="{ item }">
            <div class="d-flex gap-1">
              <v-btn
                icon="mdi-connection"
                size="small"
                variant="text"
                color="info"
                @click="testConnection(item)"
                :loading="testingDevice === item.id"
              ></v-btn>
              <v-btn
                icon="mdi-eye"
                size="small"
                variant="text"
                color="primary"
                @click="viewDevice(item.id)"
              ></v-btn>
              <v-btn
                icon="mdi-pencil"
                size="small"
                variant="text"
                color="warning"
                @click="editDevice(item.id)"
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
          Are you sure you want to delete device "{{ deviceToDelete?.name }}"?
          <div class="mt-2 text-warning">
            <v-icon color="warning" class="mr-1">mdi-alert</v-icon>
            This will also delete all attendance records from this device.
          </div>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="grey" variant="text" @click="deleteDialog = false">Cancel</v-btn>
          <v-btn color="error" variant="text" @click="deleteDevice">Delete</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Connection Test Dialog -->
    <v-dialog v-model="connectionDialog" max-width="500px">
      <v-card style="border-radius: 12px;">
        <v-card-title class="text-h5">Connection Test Result</v-card-title>
        <v-card-text>
          <v-alert
            :type="connectionResult.success ? 'success' : 'error'"
            variant="tonal"
          >
            {{ connectionResult.message }}
          </v-alert>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="primary" variant="text" @click="connectionDialog = false">Close</v-btn>
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
  devices: Array
})

const loading = ref(false)
const deleteDialog = ref(false)
const deviceToDelete = ref(null)
const connectionDialog = ref(false)
const connectionResult = ref({ success: false, message: '' })
const testingDevice = ref(null)

const headers = [
  { title: 'Device', key: 'name', sortable: true },
  { title: 'Company', key: 'company', sortable: true },
  { title: 'Connection', key: 'connection', sortable: false },
  { title: 'Serial Number', key: 'serial_number', sortable: true },
  { title: 'Status', key: 'is_active', sortable: true },
  { title: 'Last Sync', key: 'last_sync_at', sortable: true },
  { title: 'Records', key: 'attendance_records_count', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false, width: '180px' }
]

const activeDevices = computed(() => {
  return props.devices.filter(d => d.is_active).length
})

const totalRecords = computed(() => {
  return props.devices.reduce((sum, d) => sum + (d.attendance_records_count || 0), 0)
})

const formatDateTime = (datetime) => {
  return new Date(datetime).toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const viewDevice = (id) => {
  router.visit(`/attendance/devices/${id}`)
}

const editDevice = (id) => {
  router.visit(`/attendance/devices/${id}/edit`)
}

const confirmDelete = (device) => {
  deviceToDelete.value = device
  deleteDialog.value = true
}

const deleteDevice = () => {
  if (deviceToDelete.value) {
    router.delete(`/attendance/devices/${deviceToDelete.value.id}`, {
      onSuccess: () => {
        deleteDialog.value = false
        deviceToDelete.value = null
      }
    })
  }
}

const testConnection = async (device) => {
  testingDevice.value = device.id
  
  try {
    const response = await axios.post(`/attendance/devices/${device.id}/test-connection`)
    connectionResult.value = response.data
    connectionDialog.value = true
  } catch (error) {
    connectionResult.value = {
      success: false,
      message: error.response?.data?.message || 'Connection test failed'
    }
    connectionDialog.value = true
  } finally {
    testingDevice.value = null
  }
}
</script>
