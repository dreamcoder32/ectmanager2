<template>
  <AppLayout>
    <Head :title="$t('drivers.edit')" />

    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          {{ $t('drivers.edit') }}
        </h2>
        <v-btn
          @click="goIndex"
          variant="outlined"
          color="grey"
          prepend-icon="mdi-arrow-left"
        >
          {{ $t('drivers.title') }}
        </v-btn>
      </div>
    </template>

    <v-container fluid class="py-8">
      <v-row justify="center">
        <v-col cols="12" md="10" lg="8">
          <v-card elevation="2" style="border-radius: 12px;">
            <v-card-title class="text-h5 pa-6 bg-primary text-white">
              <v-icon left class="mr-2">mdi-motorbike</v-icon>
              {{ $t('drivers.edit') }}
            </v-card-title>

            <v-card-text class="pa-6">
              <v-form @submit.prevent="submit">
                <v-row>
                  <!-- Basic Information -->
                  <v-col cols="12">
                    <h3 class="text-h6 mb-4 text-primary">{{ $t('drivers.headers.name') }} & {{ $t('drivers.headers.state') }}</h3>
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.name"
                      :label="$t('drivers.headers.name')"
                      variant="outlined"
                      required
                      :error-messages="errors?.name"
                      prepend-inner-icon="mdi-account"
                      :placeholder="$t('drivers.headers.name')"
                    ></v-text-field>
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.phone"
                      :label="$t('drivers.headers.phone')"
                      variant="outlined"
                      :error-messages="errors?.phone"
                      prepend-inner-icon="mdi-phone"
                      :placeholder="$t('drivers.headers.phone')"
                    ></v-text-field>
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.license_number"
                      :label="$t('drivers.headers.license')"
                      variant="outlined"
                      :error-messages="errors?.license_number"
                      prepend-inner-icon="mdi-card-account-details"
                      :placeholder="$t('drivers.headers.license')"
                    ></v-text-field>
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.vehicle_info"
                      :label="$t('drivers.headers.vehicle')"
                      variant="outlined"
                      :error-messages="errors?.vehicle_info"
                      prepend-inner-icon="mdi-car"
                      :placeholder="$t('drivers.headers.vehicle')"
                    ></v-text-field>
                  </v-col>

                  <!-- Status -->
                  <v-col cols="12" md="6">
                    <v-switch
                      v-model="form.is_active"
                      :label="$t('drivers.status_active')"
                      color="success"
                      :error-messages="errors?.is_active"
                      inset
                    ></v-switch>
                  </v-col>

                  <!-- Commission Settings -->
                  <v-col cols="12">
                    <v-divider class="my-4"></v-divider>
                    <h3 class="text-h6 mb-4 text-primary">{{ $t('drivers.headers.commission') }}</h3>
                  </v-col>

                  <v-col cols="12" md="4">
                    <v-select
                      v-model="form.commission_type"
                      :items="commissionTypeOptions"
                      :label="$t('drivers.headers.commission')"
                      variant="outlined"
                      :error-messages="errors?.commission_type"
                      prepend-inner-icon="mdi-percent"
                      :disabled="!form.commission_is_active"
                    ></v-select>
                  </v-col>

                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="form.commission_rate"
                      :label="$t('drivers.headers.commission')"
                      type="number"
                      step="0.01"
                      min="0"
                      variant="outlined"
                      :error-messages="errors?.commission_rate"
                      prepend-inner-icon="mdi-currency-usd"
                      :disabled="!form.commission_is_active"
                      placeholder="0.00"
                    ></v-text-field>
                  </v-col>

                  <!-- State Assignment -->
                  <v-col cols="12">
                    <v-divider class="my-4"></v-divider>
                    <h3 class="text-h6 mb-4 text-primary">{{ $t('drivers.headers.state') }}</h3>
                  </v-col>

                  <v-col cols="12" md="6">
                    <StateSelector
                      v-model="form.state_id"
                      :label="$t('parcels.state')"
                      :placeholder="$t('parcels.state')"
                      :error="errors?.state_id"
                      :initial-states="states"
                      :auto-load="false"
                    />
                  </v-col>
                </v-row>

                <!-- Action Buttons -->
                <v-row class="mt-6">
                  <v-col cols="12" class="d-flex justify-end gap-3">
                    <v-btn
                      @click="goIndex"
                      variant="outlined"
                      color="grey"
                      size="large"
                    >
                      {{ $t('common.cancel') }}
                    </v-btn>
                    <v-btn
                      type="submit"
                      color="primary"
                      size="large"
                      :loading="form.processing"
                      prepend-icon="mdi-content-save"
                    >
                      {{ $t('drivers.edit') }}
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
import { onMounted } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AppLayout from '@/Layouts/AppLayout.vue'
import StateSelector from '@/Components/StateSelector.vue'

const props = defineProps({
  driver: Object,
  errors: Object,
  states: Array
})

const { t } = useI18n()

const commissionTypeOptions = [
  // { title: 'Percentage', value: 'percentage' },
  { title: 'Fixed per Parcel', value: 'fixed_per_parcel' }
]

const form = useForm({
  name: props.driver.name || '',
  phone: props.driver.phone || '',
  license_number: props.driver.license_number || '',
  vehicle_info: props.driver.vehicle_info || '',
  is_active: props.driver.is_active ?? true,
  commission_rate: props.driver.commission_rate ?? '',
  commission_type: props.driver.commission_type || 'fixed_per_parcel',
  commission_is_active: props.driver.commission_is_active ?? false,
  state_id: props.driver.state_id ?? ''
})

const goIndex = () => router.visit(route('drivers.index'))

onMounted(() => {
  // Ensure state is preselected if available
  if (props.driver.state_id) {
    form.state_id = props.driver.state_id
  }
})

const submit = () => {
  form.put(route('drivers.update', props.driver.id), {
    onSuccess: () => {
      // noop
    },
    onError: (err) => {
      console.error('Validation errors:', err)
    }
  })
}
</script>