<template>
  <AppLayout>
    <Head title="Create Parcel" />
    
    <template #title>
      <span style="background: linear-gradient(135deg, #1976d2, #1565c0); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-weight: 600;">
        {{ $t('parcels.create_new') }}
      </span>
    </template>
    
    <template #content>
      <v-container fluid>
        <v-row>
          <v-col cols="12">
            <!-- Navigation -->
            <v-btn
              color="primary"
              text
              @click="navigateTo('/parcels')"
              class="mb-4"
            >
              <v-icon left>mdi-arrow-left</v-icon>
              {{ $t('common.back_to_parcels') }}
            </v-btn>

            <!-- Excel Upload Card -->
            <v-card class="mb-6">
              <v-card-title>
                <v-icon left>mdi-file-excel</v-icon>
                Upload Parcels from Excel
              </v-card-title>
              <v-card-text>
                <v-row>
                  <v-col cols="12" md="8">
                    <v-file-input
                      v-model="excelFile"
                      :label="'Select Excel file (.xlsx, .xls)'"
                      accept=".xlsx,.xls"
                      outlined
                      prepend-icon="mdi-file-excel"
                      :error-messages="excelErrors"
                      @change="clearExcelErrors"
                    ></v-file-input>
                  </v-col>
                  <v-col cols="12" md="4" class="d-flex align-center">
                    <v-btn
                      color="success"
                      :loading="uploadingExcel"
                      :disabled="!excelFile"
                      @click="uploadExcel"
                      block
                    >
                      <v-icon left>mdi-upload</v-icon>
                      Upload Excel
                    </v-btn>
                  </v-col>
                </v-row>
                
                <!-- Upload Progress -->
                <v-progress-linear
                  v-if="uploadingExcel"
                  indeterminate
                  color="success"
                  class="mt-2"
                ></v-progress-linear>

                <!-- Success/Error Messages -->
                <v-alert
                  v-if="uploadMessage"
                  :type="uploadMessageType"
                  dismissible
                  class="mt-4"
                  @input="uploadMessage = ''"
                >
                  {{ uploadMessage }}
                </v-alert>

                <!-- Sample Format Info -->
                <v-expansion-panels class="mt-4">
                  <v-expansion-panel>
                    <v-expansion-panel-header>
                      <v-icon left>mdi-information</v-icon>
                      Excel Format Requirements
                    </v-expansion-panel-header>
                    <v-expansion-panel-content>
                      <p><strong>Required columns (in this order):</strong></p>
                      <ol>
                        <li><strong>ID</strong> - Tracking number (e.g., ECIECJ2509152227389)</li>
                        <li><strong>Expéditeurs</strong> - Sender name</li>
                        <li><strong>Réf</strong> - Reference (optional)</li>
                        <li><strong>Client</strong> - Recipient name</li>
                        <li><strong>Tel 1</strong> - Primary phone</li>
                        <li><strong>Tel 2</strong> - Secondary phone (optional)</li>
                        <li><strong>Adresse</strong> - Address</li>
                        <li><strong>Commune</strong> - City</li>
                        <li><strong>Wilaya</strong> - State/Province</li>
                        <li><strong>Total</strong> - COD Amount</li>
                        <li><strong>Remarque</strong> - Notes (optional)</li>
                        <li><strong>Produits</strong> - Products description</li>
                      </ol>
                      <p class="mt-2"><em>Note: The first row should contain headers as shown above.</em></p>
                    </v-expansion-panel-content>
                  </v-expansion-panel>
                </v-expansion-panels>
              </v-card-text>
            </v-card>

            <!-- Divider -->
            <v-divider class="my-6"></v-divider>
            <v-row class="align-center mb-4">
              <v-col>
                <h3 class="text-h6">Or Create Single Parcel</h3>
              </v-col>
            </v-row>
            <v-card>
              <v-card-title>
                <v-icon left>mdi-package-variant-plus</v-icon>
                {{ $t('parcels.create_new') }}
              </v-card-title>
              <v-card-text>
                <v-form @submit.prevent="submit" ref="form_ref">
                  <v-row>
                    <!-- Tracking Number -->
                    <v-col cols="12">
                      <v-text-field
                        v-model="form.tracking_number"
                        :label="$t('parcels.tracking_number')"
                        :error-messages="errors.tracking_number"
                        outlined
                        required
                      ></v-text-field>
                    </v-col>

                    <!-- Sender Information -->
                    <v-col cols="12" md="6">
                      <v-text-field
                        v-model="form.sender_name"
                        :label="$t('parcels.sender_name')"
                        :error-messages="errors.sender_name"
                        outlined
                        required
                      ></v-text-field>
                    </v-col>

                    <v-col cols="12" md="6">
                      <v-text-field
                        v-model="form.sender_phone"
                        :label="$t('parcels.sender_phone')"
                        :error-messages="errors.sender_phone"
                        outlined
                        required
                      ></v-text-field>
                    </v-col>

                    <v-col cols="12">
                      <v-textarea
                        v-model="form.sender_address"
                        :label="$t('parcels.sender_address')"
                        :error-messages="errors.sender_address"
                        outlined
                        rows="3"
                        required
                      ></v-textarea>
                    </v-col>

                    <!-- Receiver Information -->
                    <v-col cols="12" md="6">
                      <v-text-field
                        v-model="form.receiver_name"
                        :label="$t('parcels.receiver_name')"
                        :error-messages="errors.receiver_name"
                        outlined
                        required
                      ></v-text-field>
                    </v-col>

                    <v-col cols="12" md="6">
                      <v-text-field
                        v-model="form.receiver_phone"
                        :label="$t('parcels.receiver_phone')"
                        :error-messages="errors.receiver_phone"
                        outlined
                        required
                      ></v-text-field>
                    </v-col>

                    <v-col cols="12">
                      <v-textarea
                        v-model="form.receiver_address"
                        :label="$t('parcels.receiver_address')"
                        :error-messages="errors.receiver_address"
                        outlined
                        rows="3"
                        required
                      ></v-textarea>
                    </v-col>

                    <!-- Location -->
                    <v-col cols="12" md="6">
                      <v-select
                        v-model="form.state_id"
                        :items="states"
                        item-title="name"
                        item-value="id"
                        :label="$t('parcels.state')"
                        :error-messages="errors.state_id"
                        outlined
                        @change="onStateChange"
                      ></v-select>
                    </v-col>

                    <v-col cols="12" md="6">
                      <v-select
                        v-model="form.city_id"
                        :items="cities"
                        item-title="name"
                        item-value="id"
                        :label="$t('parcels.city')"
                        :error-messages="errors.city_id"
                        :loading="loadingCities"
                        outlined
                        :disabled="!form.state_id"
                      ></v-select>
                    </v-col>

                    <!-- Parcel Details -->
                    <v-col cols="12" md="4">
                      <v-text-field
                        v-model="form.weight"
                        :label="$t('parcels.weight')"
                        :error-messages="errors.weight"
                        type="number"
                        step="0.01"
                        suffix="kg"
                        outlined
                      ></v-text-field>
                    </v-col>

                    <v-col cols="12" md="4">
                      <v-text-field
                        v-model="form.declared_value"
                        :label="$t('parcels.declared_value')"
                        :error-messages="errors.declared_value"
                        type="number"
                        step="0.01"
                        suffix="DZD"
                        outlined
                      ></v-text-field>
                    </v-col>

                    <v-col cols="12" md="4">
                      <v-text-field
                        v-model="form.cod_amount"
                        :label="$t('parcels.cod_amount')"
                        :error-messages="errors.cod_amount"
                        type="number"
                        step="0.01"
                        suffix="DZD"
                        outlined
                      ></v-text-field>
                    </v-col>

                    <!-- Description -->
                    <v-col cols="12">
                      <v-textarea
                        v-model="form.description"
                        :label="$t('parcels.description')"
                        :error-messages="errors.description"
                        outlined
                        rows="3"
                      ></v-textarea>
                    </v-col>
                  </v-row>
                </v-form>
              </v-card-text>
              <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn
                  color="grey"
                  text
                  @click="navigateTo('/parcels')"
                >
                  {{ $t('common.cancel') }}
                </v-btn>
                <v-btn
                  color="primary"
                  @click="submit"
                  :loading="processing"
                >
                  {{ $t('parcels.create') }}
                </v-btn>
              </v-card-actions>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </template>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import AppLayout from '@/Layouts/AppLayout.vue'

const { t } = useI18n()

const props = defineProps({
  errors: Object,
})

const states = ref([])
const cities = ref([])
const loadingCities = ref(false)
const form_ref = ref(null)

const form = useForm({
  tracking_number: '',
  sender_name: '',
  sender_phone: '',
  sender_address: '',
  receiver_name: '',
  receiver_phone: '',
  receiver_address: '',
  state_id: '',
  city_id: '',
  weight: '',
  declared_value: '',
  cod_amount: '',
  description: '',
})

// Excel upload functionality
const excelFile = ref(null)
const uploadingExcel = ref(false)
const uploadMessage = ref('')
const uploadMessageType = ref('success')
const excelErrors = ref([])

const clearExcelErrors = () => {
  excelErrors.value = []
  uploadMessage.value = ''
}

const uploadExcel = async () => {
  if (!excelFile.value) return

  uploadingExcel.value = true
  uploadMessage.value = ''
  excelErrors.value = []

  const formData = new FormData()
  formData.append('excel_file', excelFile.value)

  try {
    console.log('Starting Excel upload...', excelFile.value.name)
    
    const response = await axios.post('/parcels/import-excel', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
      timeout: 300000, // 5 minutes timeout
    })

    console.log('Excel upload response:', response.data)
    
    // Check if the response indicates success
    if (response.data.success !== false) {
      uploadMessage.value = `Successfully imported ${response.data.imported_count} parcels from Excel file.`
      uploadMessageType.value = 'success'
      
      // Show errors if any, but still consider it a success
      if (response.data.has_errors && response.data.errors && response.data.errors.length > 0) {
        uploadMessage.value += ` (${response.data.errors.length} rows had validation errors)`
      }
    } else {
      uploadMessage.value = response.data.message || 'Import failed'
      uploadMessageType.value = 'error'
      
      if (response.data.errors && response.data.errors.length > 0) {
        excelErrors.value = response.data.errors
      }
    }
    
    excelFile.value = null

    // Only redirect on successful import without major errors
    if (response.data.success !== false) {
      setTimeout(() => {
        router.visit('/parcels')
      }, 3000) // Increased to 3 seconds to show the message
    }

  } catch (error) {
    console.error('Excel upload error:', error)
    console.error('Error response:', error.response)
    
    if (error.response?.data?.errors) {
      excelErrors.value = Object.values(error.response.data.errors).flat()
    } else if (error.response?.data?.message) {
      uploadMessage.value = error.response.data.message
      uploadMessageType.value = 'error'
    } else if (error.code === 'ECONNABORTED') {
      uploadMessage.value = 'Upload timeout. The file may be too large or the server is busy.'
      uploadMessageType.value = 'error'
    } else {
      uploadMessage.value = 'An error occurred while uploading the Excel file. Please try again.'
      uploadMessageType.value = 'error'
    }
  } finally {
    uploadingExcel.value = false
  }
}

const onStateChange = async () => {
  if (form.state_id) {
    loadingCities.value = true
    form.city_id = ''
    try {
      const response = await axios.get(`/api/states/${form.state_id}/cities`)
      cities.value = response.data || []
    } catch (error) {
      console.error('Error loading cities:', error)
      cities.value = [] // Ensure cities is always an array
    } finally {
      loadingCities.value = false
    }
  } else {
    cities.value = []
    form.city_id = ''
  }
}

const submit = () => {
  form.post('/parcels')
}

onMounted(async () => {
  try {
    const response = await axios.get('/api/states')
    states.value = response.data || []
  } catch (error) {
    console.error('Error loading states:', error)
    states.value = [] // Ensure states is always an array
  }
})
</script>