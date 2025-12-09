<template>
  <AppLayout>
    <Head title="Stop Desk Payment" />
    
    <template #title>
      <div class="d-flex justify-space-between align-center">
        <span class="text-h4 font-weight-bold">
          {{ $t('stopdesk_payment.title') }}
        </span>
      </div>
    </template>
    
    <template #content>
      <v-container fluid>
        <v-row>
          <!-- Main Payment Interface -->
          <v-col cols="12" md="8">
            <!-- Barcode Scanner Input -->
            <v-card 
              class="mb-4" 
              elevation="2"
              style="border-radius: 16px; overflow: hidden;"
            >
              <v-card-title class="pa-4 text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <v-icon left>mdi-barcode-scan</v-icon>
                {{ $t('stopdesk_payment.barcode_scanner') }}
              </v-card-title>
              <v-card-text class="pa-4">
                <v-text-field
                  ref="barcodeInput"
                  v-model="barcodeInput"
                  :label="$t('stopdesk_payment.scan_or_type')"
                  prepend-inner-icon="mdi-barcode"
                  variant="outlined"
                  size="large"
                  @keyup.enter="searchParcel"
                  :loading="searching"
                  autofocus
                  clearable
                  class="text-h6"
                  style="font-size: 18px;"
                >
                  <template v-slot:append>
                    <v-btn 
                      @click="searchParcel"
                      :loading="searching"
                      size="large"
                      style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;"
                    >
                      {{ $t('stopdesk_payment.search') }}
                    </v-btn>
                  </template>
                </v-text-field>
              </v-card-text>
            </v-card>

            <!-- Manual Parcel Entry Form -->
            <v-card 
              v-if="showManualEntry"
              class="mb-4" 
              elevation="2"
              style="border-radius: 16px; overflow: hidden;"
            >
              <v-card-title class="pa-4 text-white" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <v-icon left>mdi-package-variant-plus</v-icon>
                {{ $t('stopdesk_payment.manual_parcel_entry') }}
                <v-spacer></v-spacer>
                <v-btn 
                  icon 
                  @click="cancelManualEntry"
                  color="white"
                  variant="text"
                  size="small"
                >
                  <v-icon>mdi-close</v-icon>
                </v-btn>
              </v-card-title>
              <v-card-text class="pa-4">
                <v-row>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="manualParcel.tracking_number"
                      :label="$t('stopdesk_payment.tracking_number')"
                      prepend-inner-icon="mdi-barcode"
                      variant="outlined"
                      required
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="manualParcel.cod_amount"
                      :label="$t('stopdesk_payment.cod_amount')"
                      prepend-inner-icon="mdi-currency-usd"
                      variant="outlined"
                      type="number"
                      required
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="manualParcel.recipient_name"
                      :label="$t('stopdesk_payment.recipient_name')"
                      prepend-inner-icon="mdi-account"
                      variant="outlined"
                      required
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="manualParcel.recipient_phone"
                      :label="$t('stopdesk_payment.recipient_phone')"
                      prepend-inner-icon="mdi-phone"
                      variant="outlined"
                      required
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12">
                    <v-text-field
                      v-model="manualParcel.recipient_address"
                      :label="$t('stopdesk_payment.recipient_address')"
                      prepend-inner-icon="mdi-map-marker"
                      variant="outlined"
                      required
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="manualParcel.company"
                      :label="$t('stopdesk_payment.company')"
                      prepend-inner-icon="mdi-domain"
                      variant="outlined"
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="manualParcel.state"
                      :label="$t('stopdesk_payment.state')"
                      prepend-inner-icon="mdi-map"
                      variant="outlined"
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="manualParcel.city"
                      :label="$t('stopdesk_payment.city')"
                      prepend-inner-icon="mdi-city"
                      variant="outlined"
                    ></v-text-field>
                  </v-col>
                </v-row>
                <v-row class="mt-2">
                  <v-col cols="12" class="text-right">
                    <v-btn 
                      color="grey" 
                      @click="cancelManualEntry"
                      class="mr-2"
                      variant="outlined"
                    >
                      {{ $t('stopdesk_payment.cancel') }}
                    </v-btn>
                    <v-btn 
                      @click="addManualParcel"
                      :disabled="!isManualParcelValid"
                      style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white;"
                    >
                      <v-icon left>mdi-plus</v-icon>
                      {{ $t('stopdesk_payment.add_to_queue') }}
                    </v-btn>
                  </v-col>
                </v-row>
              </v-card-text>
            </v-card>

            <!-- Active Parcels List -->
            <v-card 
              elevation="2"
              style="border-radius: 16px; overflow: hidden;"
              class="mb-2"
            >
              <v-card-title class="pa-4 text-white" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                <v-icon left>mdi-package-variant</v-icon>
                {{ $t('stopdesk_payment.pending_payments') }} ({{ activeParcels.length }})
              </v-card-title>
              <v-card-text class="pa-0">
                <div v-if="activeParcels.length === 0" class="text-center pa-8">
                  <v-icon size="64" color="grey-lighten-1">mdi-package-variant-closed</v-icon>
                  <p class="text-h6 text-grey mt-4">{{ $t('stopdesk_payment.no_parcels_queue') }}</p>
                  <p class="text-body-2 text-grey">{{ $t('stopdesk_payment.scan_barcode_add') }}</p>
                </div>
                
                <v-list v-else>
                  <v-list-item
                    v-for="(parcel, index) in activeParcels"
                    :key="parcel.id"
                    class="pa-4 border-b"
                  >
                    <template v-slot:prepend>
                      <v-avatar class="mr-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <v-icon color="white">mdi-package</v-icon>
                      </v-avatar>
                    </template>
                    
                    <template v-slot:default>
                      <v-row>
                        <v-col cols="12" md="6">
                          <div class="mb-2">
                            <v-icon color="primary" size="large" class="pb-2">
                              mdi-barcode
                            </v-icon>
                            <strong class="text-h6">{{ parcel.tracking_number }}</strong>
                          </div>
                          <p class="text-body-1 text-black mb-1">
                            <v-icon size="small" class="mr-1">mdi-account</v-icon>
                            {{ parcel.recipient_name }}
                          </p>
                          <p class="text-body-1 text-black">
                            <v-icon size="small" class="mr-1">mdi-phone</v-icon>
                            {{ parcel.recipient_phone }}
                          </p>
                        </v-col>
                        
                        <v-col cols="12" md="6">
                          <v-row align="center">
                            <v-col cols="6" class="">
                              <v-chip size="medium" color="info" class=" p-2 text-h5" label>
                                {{ parseInt(parcel.cod_amount) }} DA
                              </v-chip>
                              <v-text-field
                                v-model.number="parcel.amountGiven"
                                :label="$t('stopdesk_payment.amount_given')"
                                type="number"
                                variant="solo"
                                outlined
                                dense   
                                suffix="DA"
                                @input="calculateChange(parcel)"
                              ></v-text-field>
                            </v-col>
                            <v-col cols="6">
                              <div class="change-display">
                                <label class="text-caption text-grey-darken-1 mb-1 d-block">{{ $t('stopdesk_payment.change') }}</label>
                                <div 
                                  class="text-h6 font-weight-bold pa-3 rounded border"
                                  :class="parcel.changeAmount < 9000000 ? 'text-success bg-success-lighten-5 border-success' : 'text-error bg-error-lighten-5 border-error'"
                                >
                                  {{ parcel.changeAmount }} {{ $t('common.currency') }}
                                </div>
                              </div>
                            </v-col>
                          </v-row>

                          <!-- Parcel Type Toggle -->
                          <v-row class="mt-2">
                            <v-col cols="12">
                              <div class="d-flex align-center">
                                <v-icon class="mr-2" color="primary">mdi-package-variant</v-icon>
                                <span class="text-body-1 mr-4">Type:</span>
                                <v-btn-toggle
                                  v-model="parcel.parcel_type"
                                  color="primary"
                                  variant="outlined"
                                  mandatory
                                  density="compact"
                                >
                                  <v-btn value="stopdesk" size="small">
                                    <v-icon left size="small">mdi-store</v-icon>
                                    Stopdesk
                                  </v-btn>
                                  <v-btn value="home_delivery" size="small">
                                    <v-icon left size="small">mdi-home</v-icon>
                                    À domicile
                                  </v-btn>
                                </v-btn-toggle>
                              </div>
                            </v-col>
                          </v-row>

                          <div class="d-flex justify-end mt-2">
                            <v-btn
                              color="error"
                              variant="outlined"
                              size="small"
                              @click="removeParcel(index)"
                              class="mr-2"
                            >
                              <v-icon left size="small">mdi-close</v-icon>
                              {{ $t('stopdesk_payment.remove') }}
                            </v-btn>
                            <v-btn
                              @click="confirmPayment(parcel, index)"
                              :disabled="(parcel.amountGiven === null || parcel.amountGiven === undefined) || parcel.amountGiven < parcel.cod_amount || processingPayment"
                              :loading="processingPayment"
                              size="small"
                              style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white;"
                            >
                              <v-icon left size="small">mdi-check</v-icon>
                              {{ $t('stopdesk_payment.confirm_payment') }}
                            </v-btn>
                          </div>
                        </v-col>
                      </v-row>
                    </template>
                  </v-list-item>
                </v-list>
              </v-card-text>
            </v-card>
          </v-col>

          <!-- Recent Collections Sidebar -->
          <v-col cols="12" md="4">
            <!-- Recent Collections Card -->
            <v-card 
              elevation="2"
              class="recent-collections-card"
              style="border-radius: 16px; position: sticky; top: 20px; overflow: hidden;"
            >
              <v-card-title class="pa-4 text-white recent-collections-header" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                <v-icon left>mdi-check-circle</v-icon>
                {{ $t('stopdesk_payment.recent_collections') }} <b> ({{localRecentCollections.length}}) </b>
              </v-card-title>
              
              <!-- Scrollable Collections List -->
              <div class="recent-collections-scrollable">
                <div v-if="localRecentCollections.length === 0" class="text-center pa-4">
                  <v-icon size="48" color="grey-lighten-1">mdi-history</v-icon>
                  <p class="text-body-2 text-grey mt-2">{{ $t('stopdesk_payment.no_recent_collections') }}</p>
                </div>
                
                <v-list v-else density="compact">
                  <v-list-item
                    v-for="(collection, index) in localRecentCollections"
                    :key="`collection-${collection.id}-${index}`"
                    class="pa-3 border-b"
                  >
                    <template v-slot:prepend>
                      <v-avatar size="32" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                        <v-icon size="16" color="white">mdi-check</v-icon>
                      </v-avatar>
                    </template>
                    
                    <template v-slot:default>
                      <v-list-item-title class="text-body-2 font-weight-medium">
                        {{ collection.tracking_number }}
                      </v-list-item-title>
                      <v-list-item-subtitle class="text-caption ">
                        <span class="font-weight-bold ">
                          {{ parseInt(collection.cod_amount ) }} {{ $t('common.currency') }}
                        </span>
                        - {{ formatTimeAgo(collection.collected_at) }}
                      </v-list-item-subtitle>
                    </template>
                  </v-list-item>
                </v-list>
              </div>
              
              <!-- Fixed Total Sum at the bottom -->
              <div class="recent-collections-total">
                <v-divider></v-divider>
                <div class="pa-4 bg-grey-lighten-5">
                  <div class="d-flex justify-space-between align-center">
                    <span class="text-body-2 font-weight-medium text-grey-darken-2">
                      {{ $t('stopdesk_payment.total_collections') }}:
                    </span>
                    <span class="text-h6 font-weight-bold text-success">
                      {{ totalRecentCollections }} {{ $t('common.currency') }}
                    </span>
                  </div>
                </div>
              </div>
            </v-card>
          </v-col>
        </v-row>
        
        <!-- Money Case Selection - Fixed at Bottom Right -->
        <div 
          ref="caseSelectionContainer"
          :class="['case-selection-container', { 'shake-animation': isShaking }]"
          style="
            position: fixed; 
            bottom: 20px; 
            right: 20px;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            width: 280px;
          "
        >
          <div class="d-flex align-center mb-3">
            <v-avatar size="32" class="mr-2" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
              <v-icon color="white" size="18">mdi-cash-register</v-icon>
            </v-avatar>
            <span class="text-body-1 font-weight-bold">{{ $t('stopdesk_payment.money_case') }}:</span>
          </div>
          
          <v-select
            v-model="selectedCaseId"
            :items="activeCases"
            item-title="name"
            item-value="id"
            :placeholder="$t('stopdesk_payment.select_money_case')"
            variant="outlined"
            clearable
            prepend-inner-icon="mdi-briefcase"
            @update:model-value="activateCase"
            density="compact"
            class="mb-3"
            :disabled="canCollectWithoutCase && !selectedCaseId"
          >
            <template v-slot:item="{ props, item }">
              <v-list-item v-bind="props">
                <template v-slot:title>
                  <div class="d-flex justify-space-between align-center">
                    <span>{{ item.raw.name }}</span>
                    <v-chip size="x-small" color="primary" variant="outlined">
                      {{ item.raw.balance }} {{ item.raw.currency }}
                    </v-chip>
                  </div>
                </template>
                <template v-slot:subtitle>
                  {{ item.raw.description }}
                </template>
              </v-list-item>
            </template>
            <template v-slot:selection="{ item }">
              <div class="d-flex align-center">
                <span class="mr-2">{{ item.raw.name }}</span>
                <v-chip size="x-small" color="primary" variant="outlined">
                  {{ item.raw.balance }} {{ item.raw.currency }}
                </v-chip>
              </div>
            </template>
          </v-select>
          
          <v-chip 
            :color="selectedCaseId ? 'success' : (canCollectWithoutCase ? 'info' : 'warning')" 
            variant="elevated"
            size="small"
            class="w-100 justify-center"
          >
            <v-icon left size="small">
              {{ selectedCaseId ? 'mdi-check-circle' : (canCollectWithoutCase ? 'mdi-account-check' : 'mdi-alert-circle') }}
            </v-icon>
            {{ selectedCaseId ? $t('stopdesk_payment.case_active') : (canCollectWithoutCase ? $t('stopdesk_payment.can_collect_without_case') : $t('stopdesk_payment.no_case_selected')) }}
          </v-chip>
        </div>
        
      </v-container>

      <!-- Snackbar Notifications -->
      <v-snackbar
        v-model="snackbar.show"
        :color="snackbar.color"
        :timeout="snackbar.timeout"
        location="bottom center"
        style="margin-bottom: 20px;"
      >
        <div class="d-flex align-center">
          <v-icon class="mr-2" :color="snackbar.iconColor || 'white'">
            {{ snackbar.icon }}
          </v-icon>
          <span class="text-body-1">{{ snackbar.message }}</span>
        </div>
        <template v-slot:actions>
          <v-btn
            color="white"
            variant="text"
            @click="snackbar.show = false"
            icon
            size="small"
          >
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </template>
      </v-snackbar>
    </template>
  </AppLayout>
</template>

<script>
import { router, Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useI18n } from 'vue-i18n'

export default {
  name: 'StopDeskPayment',
  components: {
    AppLayout,
    Head
  },
  setup() {
    const { t } = useI18n()
    return { t }
  },
  props: {
    recentCollections: {
      type: Array,
      default: () => []
    },
    activeCases: {
      type: Array,
      default: () => []
    },
    userLastActiveCaseId: {
      type: Number,
      default: null
    },
    auth: {
      type: Object,
      default: () => ({})
    }
  },
  data() {
    return {
      barcodeInput: '',
      searching: false,
      activeParcels: [],
      showManualEntry: false,
      selectedCaseId: null, // Global case selection
      isShaking: false, // For shake animation
      localRecentCollections: this.recentCollections,
      processingPayment: false, // Loading state for payment processing
      manualParcel: {
        tracking_number: '',
        recipient_name: '',
        recipient_phone: '',
        recipient_address: '',
        cod_amount: null,
        company: '',
        state: '',
        city: ''
      },
      snackbar: {
        show: false,
        message: '',
        color: 'success',
        icon: 'mdi-check-circle',
        iconColor: 'white',
        timeout: 3000
      },
      successAudio: null
    }
  },
  mounted() {
    // Set the user's last active case if available
    if (this.userLastActiveCaseId && this.activeCases.some(c => c.id === this.userLastActiveCaseId)) {
      this.selectedCaseId = this.userLastActiveCaseId
    }
    
    // Initialize success sound
    this.successAudio = new Audio('/paid.wav')
    this.successAudio.volume = 0.9 // Set volume to 70%
    
    // Focus on barcode input when component mounts
    this.$nextTick(() => {
      if (this.$refs.barcodeInput) {
        this.$refs.barcodeInput.focus()
      }
    })
  },
  methods: {
    async searchParcel() {
      if (!this.barcodeInput.trim()) return;
      this.searching = true;
      
      try {
        const response = await axios.post('/parcels/search-by-tracking', {
          tracking_number: this.barcodeInput,
        });
        this.handleSearchResponse(response.data.searchResult);
      } catch (error) {
        console.error('Search error:', error);
        this.handleSearchError(error.response?.data?.errors || error.response?.data?.message || 'An unexpected error occurred.');
      } finally {
        this.resetSearchState();
        this.searching = false;
      }
    },

    handleSearchResponse(data) {
      console.log('Processing search response:', data)
      
      if (data.success) {
        console.log('SUCCESS: Parcel found')
        
        if (data.parcel) {
          // Check if parcel already exists in queue
          const existingIndex = this.activeParcels.findIndex(p => 
            p.tracking_number === data.parcel.tracking_number || p.id === data.parcel.id
          )
          
          if (existingIndex === -1) {
            // Add new parcel to queue
            const parcel = {
              ...data.parcel,
              amountGiven: null,
              changeAmount: 0,
              parcel_type: 'stopdesk' // Default to stopdesk
            }
            this.activeParcels.push(parcel)
            console.log('Parcel added to queue:', parcel.tracking_number)
          } else {
            console.log('Parcel already in queue')
          }
          
          this.showManualEntry = false
        }
      } else {
        console.log('FAIL: Parcel not found or invalid')
        
        // Handle different failure scenarios
        if (data.allow_manual_entry === true) {
          console.log('Showing manual entry form')
          this.showManualEntry = true
          this.manualParcel.tracking_number = this.barcodeInput
        } else if (data.allow_manual_entry === false) {
          console.log('Manual entry not allowed')
          this.showManualEntry = false
          this.resetManualParcel()
        } else {
          console.log('Unknown response structure')
          this.showManualEntry = false
        }
      }
    },

    handleSearchError(errors) {
      console.log('Handling search error:', errors)
      
      if (typeof errors === 'object' && errors.tracking_number) {
        // Validation error
        console.log('Validation error')
        this.showManualEntry = false
        this.showError('Invalid tracking number format')
      } else if (typeof errors === 'string') {
        // Generic error message
        console.log('Generic error')
        this.showManualEntry = false
        this.showError(errors)
      } else {
        // Network or server error - allow manual entry as fallback
        console.log('Network/server error - allowing manual entry')
        this.showManualEntry = true
        this.manualParcel.tracking_number = this.barcodeInput
        this.showWarning('Parcel not found in system. You can add it manually.')
      }
    },

    showError(message) {
      this.snackbar = {
        show: true,
        message: message,
        color: 'error',
        icon: 'mdi-alert-circle',
        iconColor: 'white',
        timeout: 4000
      }
    },

    showWarning(message) {
      this.snackbar = {
        show: true,
        message: message,
        color: 'warning',
        icon: 'mdi-alert',
        iconColor: 'white',
        timeout: 3500
      }
    },

    showSuccess(message) {
      this.snackbar = {
        show: true,
        message: message,
        color: 'success',
        icon: 'mdi-check-circle',
        iconColor: 'white',
        timeout: 3000
      }
      this.playSuccessSound()
    },

    playSuccessSound() {
      if (this.successAudio) {
        // Reset the audio to the beginning in case it's already playing
        this.successAudio.currentTime = 0
        this.successAudio.play().catch(error => {
          console.warn('Could not play success sound:', error)
        })
      }
    },

    resetSearchState() {
      this.barcodeInput = ''
      this.searching = false
      
      // Refocus on input for next scan
      this.$nextTick(() => {
        if (this.$refs.barcodeInput) {
          this.$refs.barcodeInput.focus()
        }
      })
    },
    
    calculateChange(parcel) {
      if (parcel.amountGiven && parcel.cod_amount) {
        parcel.changeAmount = parcel.amountGiven - parcel.cod_amount
      } else {
        parcel.changeAmount = 0
      }
    },
    
    async confirmPayment(parcel, index) {
      // Allow 0 amount if COD is 0
      if (parcel.amountGiven === null || parcel.amountGiven === undefined || parcel.amountGiven < parcel.cod_amount) {
        this.showError('Amount given must be greater than or equal to COD amount');
        return;
      }
      if (!this.canCollectWithoutCase && !this.selectedCaseId) {
        this.triggerShakeAnimation();
        this.showError('Please select a money case before confirming payment');
        return;
      }
      if (parcel.isManual) {
        this.confirmManualParcelPayment(parcel, index);
        return;
      }
      
      this.processingPayment = true;
      
      try {
        const response = await axios.post('/parcels/confirm-payment', {
          parcel_id: parcel.id,
          amount_given: parcel.amountGiven,
          case_id: this.selectedCaseId,
          parcel_type: parcel.parcel_type || 'stopdesk',
        });
        
        if (response.data.success) {
          this.activeParcels.splice(index, 1);
          if (response.data.recentCollections) {
            this.localRecentCollections = response.data.recentCollections;
          }
          this.showSuccess(`Payment confirmed for ${parcel.tracking_number}`);
        } else {
          this.showError(response.data.message || 'Payment confirmation failed');
        }
      } catch (error) {
        console.error('Payment confirmation error:', error);
        const errorMessage = error.response?.data?.message || 
                           error.response?.data?.errors?.parcel_id?.[0] ||
                           'Payment confirmation failed';
        this.showError(errorMessage);
      } finally {
        this.processingPayment = false;
      }
    },
    
    async confirmManualParcelPayment(parcel, index) {
      if (!this.canCollectWithoutCase && !this.selectedCaseId) {
        this.triggerShakeAnimation();
        this.showError('Please select a money case before confirming payment');
        return;
      }
      
      this.processingPayment = true;
      
      try {
        const response = await axios.post('/parcels/create-manual-and-collect', {
          tracking_number: parcel.tracking_number,
          recipient_name: parcel.recipient_name,
          recipient_phone: parcel.recipient_phone,
          recipient_address: parcel.recipient_address,
          cod_amount: parcel.cod_amount,
          amount_given: parcel.amountGiven,
          company: parcel.company,
          state: parcel.state,
          city: parcel.city,
          case_id: this.selectedCaseId,
          parcel_type: parcel.parcel_type || 'stopdesk',
        });
        
        if (response.data.success) {
          this.activeParcels.splice(index, 1);
          if (response.data.collection) {
            this.localRecentCollections.unshift(response.data.collection);
            if (this.localRecentCollections.length > 20) {
              this.localRecentCollections = this.localRecentCollections.slice(0, 20);
            }
          }
          this.showSuccess(`Manual parcel created and payment confirmed for ${parcel.tracking_number}`);
        } else {
          this.showError(response.data.message || 'Manual parcel creation failed');
        }
      } catch (error) {
        console.error('Manual parcel creation error:', error);
        const errorMessage = error.response?.data?.message || 
                           error.response?.data?.errors?.tracking_number?.[0] ||
                           'Manual parcel creation failed';
        this.showError(errorMessage);
      } finally {
        this.processingPayment = false;
      }
    },
    
    removeParcel(index) {
      const parcel = this.activeParcels[index]
      this.activeParcels.splice(index, 1)
      
      // Refocus on barcode input
      this.$nextTick(() => {
        if (this.$refs.barcodeInput) {
          this.$refs.barcodeInput.focus()
        }
      })
    },
    
    formatTime(date) {
      return new Date(date).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit'
      })
    },
    
    formatTimeAgo(date) {
      const now = new Date()
      const collectedAt = new Date(date)
      const diffInSeconds = Math.floor((now - collectedAt) / 1000)
      
      if (diffInSeconds < 60) {
        return this.$t('time.just_now')
      }
      
      const diffInMinutes = Math.floor(diffInSeconds / 60)
      if (diffInMinutes < 60) {
        return this.$t('time.minutes_ago', diffInMinutes)
      }
      
      const diffInHours = Math.floor(diffInMinutes / 60)
      if (diffInHours < 24) {
        return this.$t('time.hours_ago', diffInHours)
      }
      
      const diffInDays = Math.floor(diffInHours / 24)
      if (diffInDays < 7) {
        return this.$t('time.days_ago', diffInDays)
      }
      
      const diffInWeeks = Math.floor(diffInDays / 7)
      if (diffInWeeks < 4) {
        return this.$t('time.weeks_ago', diffInWeeks)
      }
      
      const diffInMonths = Math.floor(diffInDays / 30)
      if (diffInMonths < 12) {
        return this.$t('time.months_ago', diffInMonths)
      }
      
      const diffInYears = Math.floor(diffInDays / 365)
      return this.$t('time.years_ago', diffInYears)
    },
    
    triggerShakeAnimation() {
      this.isShaking = true
      setTimeout(() => {
        this.isShaking = false
      }, 600) // Duration matches the CSS animation duration
    },
    
    cancelManualEntry() {
      this.showManualEntry = false
      this.resetManualParcel()
      this.barcodeInput = ''
      
      // Refocus on barcode input
      this.$nextTick(() => {
        if (this.$refs.barcodeInput) {
          this.$refs.barcodeInput.focus()
        }
      })
    },
    
    resetManualParcel() {
      this.manualParcel = {
        tracking_number: '',
        recipient_name: '',
        recipient_phone: '',
        recipient_address: '',
        cod_amount: null,
        company: '',
        state: '',
        city: ''
      }
    },
    
    activateCase(caseId) {
      if (!caseId) return
      
      router.post('/money-cases/activate', {
        case_id: caseId
      }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: (page) => {
          console.log('Case activation success:', page.props.flash)
          if (page.props.flash?.success) {
            this.showSuccess('Money case activated successfully');
          }
        },
        onError: (errors) => {
          console.error('Case activation error:', errors)
          if (errors.case_activation) {
            this.showError('Failed to activate money case');
          }
          this.selectedCaseId = null
        }
      })
    },
    
    addManualParcel() {
      if (!this.isManualParcelValid) {
        this.showError('Please fill in all required fields');
        return;
      }
      
      // Validate COD amount
      if (this.manualParcel.cod_amount <= 0) {
        this.showError('COD amount must be greater than 0');
        return;
      }
      
      // Create a temporary parcel object for the queue
      const manualParcel = {
        id: 'manual_' + Date.now(), // Temporary ID
        tracking_number: this.manualParcel.tracking_number.trim(),
        recipient_name: this.manualParcel.recipient_name.trim(),
        recipient_phone: this.manualParcel.recipient_phone.trim(),
        recipient_address: this.manualParcel.recipient_address.trim(),
        cod_amount: parseFloat(this.manualParcel.cod_amount),
        company: this.manualParcel.company?.trim() || null,
        state: this.manualParcel.state?.trim() || null,
        city: this.manualParcel.city?.trim() || null,
        amountGiven: null,
        changeAmount: 0,
        parcel_type: 'stopdesk', // Default to stopdesk for manual parcels
        isManual: true // Flag to identify manual parcels
      }
      
      this.activeParcels.push(manualParcel)
      this.showManualEntry = false
      this.resetManualParcel()
      this.barcodeInput = ''
      this.showSuccess('Manual parcel added to queue');
      
      // Refocus on barcode input
      this.$nextTick(() => {
        if (this.$refs.barcodeInput) {
          this.$refs.barcodeInput.focus()
        }
      })
    }
  },
  
  computed: {
    isManualParcelValid() {
      return this.manualParcel.tracking_number &&
             this.manualParcel.recipient_name &&
             this.manualParcel.recipient_phone &&
             this.manualParcel.recipient_address &&
             this.manualParcel.cod_amount &&
             this.manualParcel.cod_amount > 0
    },
    
    canCollectWithoutCase() {
      return this.auth?.user?.can_collect_stopdesk ?? false
    },
    
    totalRecentCollections() {
      return this.localRecentCollections.reduce((total, collection) => {
        return total + (parseFloat(collection.cod_amount) || 0)
      }, 0)
    }
  }
}
</script>

<style scoped>
.border-b {
  border-bottom: 1px solid #e0e0e0;
}

.v-list-item:last-child .border-b {
  border-bottom: none;
}

/* Card hover effects */
.v-card {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.v-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 40px rgba(0,0,0,0.12) !important;
}

/* Custom scrollbar for sidebar */
::-webkit-scrollbar {
  width: 6px;
}

::-webkit-scrollbar-track {
  background: rgba(0,0,0,0.05);
  border-radius: 3px;
}

::-webkit-scrollbar-thumb {
  background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(135deg, #38ef7d 0%, #11998e 100%);
}

/* Recent Collections Card specific styles */
.recent-collections-card {
  max-height: 500px;
  display: flex;
  flex-direction: column;
}

.recent-collections-header {
  flex-shrink: 0;
}

.recent-collections-scrollable {
  flex-grow: 1;
  overflow-y: auto;
  min-height: 0;
}

.recent-collections-total {
  flex-shrink: 0;
  position: sticky;
  bottom: 0;
  background: white;
  z-index: 1;
}

/* Focus styles for barcode input */
.v-text-field--focused .v-field {
  box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.3);
}

/* Button gradient effects */
.v-btn {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.v-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}

/* Avatar gradient animations */
.v-avatar {
  transition: all 0.3s ease;
}

.v-avatar:hover {
  transform: scale(1.1);
  box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

/* Fixed bottom total collections */
.fixed-bottom-total {
  position: fixed !important;
  bottom: 0 !important;
  left: 0 !important;
  right: 0 !important;
  z-index: 1000 !important;
  background: rgba(255, 255, 255, 0.95) !important;
  backdrop-filter: blur(20px);
  border-top: 1px solid rgba(0,0,0,0.08) !important;
  box-shadow: 0 -2px 20px rgba(0, 0, 0, 0.1) !important;
}

/* Shake animation for case selection */
@keyframes shake {
  0%, 100% { transform: translateX(0); }
  10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
  20%, 40%, 60%, 80% { transform: translateX(5px); }
}

.shake-animation {
  animation: shake 0.6s ease-in-out;
}

.case-selection-container {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.case-selection-container:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 40px rgba(0,0,0,0.2) !important;
}

/* List item hover effects */
.v-list-item {
  transition: all 0.2s ease;
}

.v-list-item:hover {
  background: rgba(102, 126, 234, 0.05) !important;
  transform: translateX(4px);
}

/* Chip animations */
.v-chip {
  transition: all 0.2s ease;
}

.v-chip:hover {
  transform: scale(1.05);
}
</style>
