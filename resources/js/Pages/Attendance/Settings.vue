<template>
  <AppLayout>
    <template #title>
      Attendance Settings
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
      <v-row justify="center">
        <v-col cols="12" md="10" lg="8">
          <v-card style="border-radius: 12px;" elevation="1">
            <v-card-title class="pa-6">
              <v-icon class="mr-2">mdi-cog</v-icon>
              General Configuration
            </v-card-title>
            
            <v-card-text class="pa-6">
              <v-form @submit.prevent="submitForm">
                <v-row>
                  <!-- Work Hours Section -->
                  <v-col cols="12">
                    <div class="text-h6 mb-4">Standard Work Hours</div>
                  </v-col>
                  
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.work_start_time"
                      label="Standard Start Time *"
                      type="time"
                      variant="outlined"
                      step="1"
                    ></v-text-field>
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.work_end_time"
                      label="Standard End Time *"
                      type="time"
                      variant="outlined"
                      step="1"
                    ></v-text-field>
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model.number="form.standard_work_hours"
                      label="Standard Daily Hours *"
                      type="number"
                      variant="outlined"
                    ></v-text-field>
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model.number="form.grace_period_minutes"
                      label="Grace Period (Minutes) *"
                      type="number"
                      variant="outlined"
                    ></v-text-field>
                  </v-col>

                  <v-divider class="my-6 w-100"></v-divider>

                  <!-- Attendance Rules -->
                  <v-col cols="12">
                    <div class="text-h6 mb-4">Attendance Rules</div>
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model.number="form.half_day_hours"
                      label="Minimum Hours for Half Day *"
                      type="number"
                      variant="outlined"
                    ></v-text-field>
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model.number="form.sync_interval_minutes"
                      label="Device Sync Interval (Minutes) *"
                      type="number"
                      variant="outlined"
                    ></v-text-field>
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-switch
                      v-model="form.auto_checkout_enabled"
                      label="Enable Auto-Checkout"
                      color="primary"
                    ></v-switch>
                  </v-col>

                  <v-col cols="12" md="6" v-if="form.auto_checkout_enabled">
                    <v-text-field
                      v-model="form.auto_checkout_time"
                      label="Auto-Checkout Time"
                      type="time"
                      variant="outlined"
                      step="1"
                    ></v-text-field>
                  </v-col>

                  <v-divider class="my-6 w-100"></v-divider>

                  <!-- Working Days -->
                  <v-col cols="12">
                    <div class="text-h6 mb-4">Standard Working Days</div>
                    <v-row>
                      <v-col v-for="day in weekDays" :key="day.value" cols="6" sm="4" md="3">
                        <v-checkbox
                          v-model="form.working_days"
                          :label="day.title"
                          :value="day.value"
                          color="primary"
                          hide-details
                        ></v-checkbox>
                      </v-col>
                    </v-row>
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
                    Save Settings
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
  settings: Object
})

const submitting = ref(false)

const weekDays = [
  { title: 'Monday', value: 'monday' },
  { title: 'Tuesday', value: 'tuesday' },
  { title: 'Wednesday', value: 'wednesday' },
  { title: 'Thursday', value: 'thursday' },
  { title: 'Friday', value: 'friday' },
  { title: 'Saturday', value: 'saturday' },
  { title: 'Sunday', value: 'sunday' }
]

const form = ref({
  work_start_time: props.settings.work_start_time,
  work_end_time: props.settings.work_end_time,
  standard_work_hours: props.settings.standard_work_hours,
  grace_period_minutes: props.settings.grace_period_minutes,
  half_day_hours: props.settings.half_day_hours,
  auto_checkout_enabled: props.settings.auto_checkout_enabled,
  auto_checkout_time: props.settings.auto_checkout_time,
  sync_interval_minutes: props.settings.sync_interval_minutes,
  working_days: props.settings.working_days || []
})

const submitForm = () => {
  submitting.value = true
  
  router.put('/attendance/settings', form.value, {
    onFinish: () => {
      submitting.value = false
    }
  })
}
</script>
