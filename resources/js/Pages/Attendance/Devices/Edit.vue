<template>
  <AppLayout>
    <template #title>
      Edit Device: {{ device.name }}
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
              <v-icon class="mr-2">mdi-pencil</v-icon>
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
                      variant="outlined"
                      :error-messages="errors.name"
                    ></v-text-field>
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.device_model"
                      label="Device Model *"
                      variant="outlined"
                      :error-messages="errors.device_model"
                    ></v-text-field>
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.serial_number"
                      label="Serial Number *"
                      variant="outlined"
                      :error-messages="errors.serial_number"
                    ></v-text-field>
                  </v-col>

                  <v-col cols="12" md="8">
                    <v-text-field
                      v-model="form.ip_address"
                      label="IP Address *"
                      variant="outlined"
                      :error-messages="errors.ip_address"
                    ></v-text-field>
                  </v-col>

                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model.number="form.port"
                      label="Port *"
                      type="number"
                      variant="outlined"
                      :error-messages="errors.port"
                    ></v-text-field>
                  </v-col>

                  <v-col cols="12">
                    <v-text-field
                      v-model="form.location"
                      label="Location"
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
                    Update Device
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
  device: Object,
  companies: Array,
  errors: Object
})

const submitting = ref(false)

const form = ref({
  company_id: props.device.company_id,
  name: props.device.name,
  device_model: props.device.device_model,
  serial_number: props.device.serial_number,
  ip_address: props.device.ip_address,
  port: props.device.port,
  location: props.device.location,
  is_active: props.device.is_active
})

const submitForm = () => {
  submitting.value = true
  
  router.put(`/attendance/devices/${props.device.id}`, form.value, {
    onFinish: () => {
      submitting.value = false
    }
  })
}
</script>
