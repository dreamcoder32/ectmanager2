<template>
  <AppLayout>
    <template #title>
      Add New Device
    </template>
    
    <template #actions>
      <v-btn
        color="grey"
        @click="$inertia.visit('/attendance/devices')"
        prepend-icon="mdi-arrow-left"
        variant="outlined"
        size="large"
        style="border-radius: 8px;"
      >
        Back to Devices
      </v-btn>
    </template>

    <v-container fluid class="pa-6">
      <v-row justify="center">
        <v-col cols="12" md="8" lg="6">
          <v-card style="border-radius: 12px;" elevation="1">
            <v-card-title class="pa-6">
              <v-icon class="mr-2">mdi-devices</v-icon>
              Device Information
            </v-card-title>
            
            <v-card-text class="pa-6">
              <v-form @submit.prevent="submitForm">
                <v-row>
                  <v-col cols="12">
                    <v-select
                      v-model="form.company_id"
                      :items="companies"
                      label="Company *"
                      item-title="name"
                      item-value="id"
                      variant="outlined"
                      :error-messages="errors.company_id"
                      placeholder="Select a company"
                    ></v-select>
                  </v-col>

                  <v-col cols="12">
                    <v-text-field
                      v-model="form.name"
                      label="Device Name *"
                      placeholder="e.g., Main Office K60 Pro"
                      variant="outlined"
                      :error-messages="errors.name"
                    ></v-text-field>
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.device_model"
                      label="Device Model *"
                      placeholder="e.g., K60 Pro"
                      variant="outlined"
                      :error-messages="errors.device_model"
                    ></v-text-field>
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.serial_number"
                      label="Serial Number *"
                      placeholder="Device serial number"
                      variant="outlined"
                      :error-messages="errors.serial_number"
                    ></v-text-field>
                  </v-col>

                  <v-col cols="12" md="8">
                    <v-text-field
                      v-model="form.ip_address"
                      label="IP Address *"
                      placeholder="e.g., 192.168.1.100"
                      variant="outlined"
                      :error-messages="errors.ip_address"
                    ></v-text-field>
                  </v-col>

                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model.number="form.port"
                      label="Port *"
                      placeholder="4370"
                      type="number"
                      variant="outlined"
                      :error-messages="errors.port"
                    ></v-text-field>
                  </v-col>

                  <v-col cols="12">
                    <v-text-field
                      v-model="form.location"
                      label="Location"
                      placeholder="e.g., Main Office - 1st Floor"
                      variant="outlined"
                      :error-messages="errors.location"
                    ></v-text-field>
                  </v-col>

                  <v-col cols="12">
                    <v-switch
                      v-model="form.is_active"
                      label="Active"
                      color="success"
                      hide-details
                    ></v-switch>
                  </v-col>
                </v-row>

                <v-divider class="my-6"></v-divider>

                <div class="d-flex gap-2">
                  <v-btn
                    type="submit"
                    color="primary"
                    size="large"
                    :loading="submitting"
                    prepend-icon="mdi-content-save"
                  >
                    Save Device
                  </v-btn>
                  <v-btn
                    color="grey"
                    size="large"
                    variant="outlined"
                    @click="$inertia.visit('/attendance/devices')"
                  >
                    Cancel
                  </v-btn>
                </div>
              </v-form>
            </v-card-text>
          </v-card>

          <!-- Help Card -->
          <v-card class="mt-6" style="border-radius: 12px;" elevation="1">
            <v-card-title class="pa-6">
              <v-icon class="mr-2" color="info">mdi-information</v-icon>
              Setup Instructions
            </v-card-title>
            <v-card-text class="pa-6">
              <v-list density="compact">
                <v-list-item>
                  <template v-slot:prepend>
                    <v-icon color="primary">mdi-numeric-1-circle</v-icon>
                  </template>
                  <v-list-item-title>Ensure the ZKTeco K60 Pro device is connected to your network</v-list-item-title>
                </v-list-item>
                <v-list-item>
                  <template v-slot:prepend>
                    <v-icon color="primary">mdi-numeric-2-circle</v-icon>
                  </template>
                  <v-list-item-title>Find the device IP address from the device settings menu</v-list-item-title>
                </v-list-item>
                <v-list-item>
                  <template v-slot:prepend>
                    <v-icon color="primary">mdi-numeric-3-circle</v-icon>
                  </template>
                  <v-list-item-title>Default port is usually 4370 (check device documentation)</v-list-item-title>
                </v-list-item>
                <v-list-item>
                  <template v-slot:prepend>
                    <v-icon color="primary">mdi-numeric-4-circle</v-icon>
                  </template>
                  <v-list-item-title>After saving, test the connection to verify setup</v-list-item-title>
                </v-list-item>
              </v-list>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  companies: Array,
  errors: {
    type: Object,
    default: () => ({})
  }
})

const submitting = ref(false)

const form = ref({
  company_id: null,
  name: '',
  device_model: 'K60 Pro',
  serial_number: '',
  ip_address: '',
  port: 4370,
  location: '',
  is_active: true
})

const submitForm = () => {
  submitting.value = true
  
  router.post('/attendance/devices', form.value, {
    onFinish: () => {
      submitting.value = false
    }
  })
}
</script>
