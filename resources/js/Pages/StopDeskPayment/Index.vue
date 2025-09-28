<template>
  <AppLayout>
    <Head title="Stop Desk Payment" />
    
    <template #title>
      <div class="d-flex justify-space-between align-center">
        <span class="text-h4 font-weight-bold" 
              >
          Stop Desk Payment
        </span>
        <v-chip variant="outlined" size="large">
          <v-icon left>mdi-store</v-icon>
          Agent Interface
        </v-chip>
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
              style="border-radius: 12px;"
            >
              <v-card-title class="pa-4 bg-primary text-white">
                <v-icon left>mdi-barcode-scan</v-icon>
                Barcode Scanner
              </v-card-title>
              <v-card-text class="pa-4">
                <v-text-field
                  ref="barcodeInput"
                  v-model="barcodeInput"
                  label="Scan or type tracking number"
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
                      color="primary" 
                      @click="searchParcel"
                      :loading="searching"
                      size="large"
                    >
                      Search
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
              style="border-radius: 12px;"
            >
              <v-card-title class="pa-4 bg-warning text-white">
                <v-icon left>mdi-package-variant-plus</v-icon>
                Manual Parcel Entry
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
                      label="Tracking Number"
                      prepend-inner-icon="mdi-barcode"
                      variant="outlined"
                      required
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="manualParcel.cod_amount"
                      label="COD Amount (DA)"
                      prepend-inner-icon="mdi-currency-usd"
                      variant="outlined"
                      type="number"
                      required
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="manualParcel.recipient_name"
                      label="Recipient Name"
                      prepend-inner-icon="mdi-account"
                      variant="outlined"
                      required
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="manualParcel.recipient_phone"
                      label="Recipient Phone"
                      prepend-inner-icon="mdi-phone"
                      variant="outlined"
                      required
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12">
                    <v-text-field
                      v-model="manualParcel.recipient_address"
                      label="Recipient Address"
                      prepend-inner-icon="mdi-map-marker"
                      variant="outlined"
                      required
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="manualParcel.company"
                      label="Company (Optional)"
                      prepend-inner-icon="mdi-domain"
                      variant="outlined"
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="manualParcel.state"
                      label="State (Optional)"
                      prepend-inner-icon="mdi-map"
                      variant="outlined"
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="manualParcel.city"
                      label="City (Optional)"
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
                    >
                      Cancel
                    </v-btn>
                    <v-btn 
                      color="success" 
                      @click="addManualParcel"
                      :disabled="!isManualParcelValid"
                    >
                      <v-icon left>mdi-plus</v-icon>
                      Add to Queue
                    </v-btn>
                  </v-col>
                </v-row>
              </v-card-text>
            </v-card>

            <!-- Active Parcels List -->
            <v-card 
              elevation="2"
              style="border-radius: 12px;"
            >
              <v-card-title class="pa-4 bg-orange text-white">
                <v-icon left>mdi-package-variant</v-icon>
                Pending Payments ({{ activeParcels.length }})
              </v-card-title>
              <v-card-text class="pa-0">
                <div v-if="activeParcels.length === 0" class="text-center pa-8">
                  <v-icon size="64" color="grey-lighten-1">mdi-package-variant-closed</v-icon>
                  <p class="text-h6 text-grey mt-4">No parcels in queue</p>
                  <p class="text-body-2 text-grey">Scan a barcode to add parcels</p>
                </div>
                
                <v-list v-else>
                  <v-list-item
                    v-for="(parcel, index) in activeParcels"
                    :key="parcel.id"
                    class="pa-4 border-b"
                  >
                    <template v-slot:prepend>
                      <v-avatar color="primary" class="mr-4">
                        <v-icon>mdi-package</v-icon>
                      </v-avatar>
                    </template>
                    
                    <v-list-item-content>
                      <v-row>
                        <v-col cols="12" md="6">
                          <div class="mb-2">
                            <strong class="text-h6">{{ parcel.tracking_number }}</strong>
                            <v-chip size="small" color="info" class="ml-2">
                              {{ parcel.cod_amount }} DA
                            </v-chip>
                          </div>
                          <p class="text-body-2 text-grey mb-1">
                            <v-icon size="small" class="mr-1">mdi-account</v-icon>
                            {{ parcel.recipient_name }}
                          </p>
                          <p class="text-body-2 text-grey">
                            <v-icon size="small" class="mr-1">mdi-phone</v-icon>
                            {{ parcel.recipient_phone }}
                          </p>
                        </v-col>
                        
                        <v-col cols="12" md="6">
                          <v-row align="center">
                            <v-col cols="6" class="mt-8">
                              <v-text-field
                                v-model.number="parcel.amountGiven"
                                label="Amount Given"
                                type="number"
                                variant="outlined"
                                density="compact"
                                suffix="DA"
                                @input="calculateChange(parcel)"
                              ></v-text-field>
                            </v-col>
                            <v-col cols="6">
                              <div class="change-display">
                                <label class="text-caption text-grey-darken-1 mb-1 d-block">Change</label>
                                <div 
                                  class="text-h6 font-weight-bold pa-3 rounded border"
                                  :class="parcel.changeAmount >= 0 ? 'text-success bg-success-lighten-5 border-success' : 'text-error bg-error-lighten-5 border-error'"
                                >
                                  {{ parcel.changeAmount }} DA
                                </div>
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
                              Remove
                            </v-btn>
                            <v-btn
                              color="success"
                              @click="confirmPayment(parcel, index)"
                              :disabled="!parcel.amountGiven || parcel.amountGiven < parcel.cod_amount"
                              size="small"
                            >
                              <v-icon left size="small">mdi-check</v-icon>
                              Confirm Payment
                            </v-btn>
                          </div>
                        </v-col>
                      </v-row>
                    </v-list-item-content>
                  </v-list-item>
                </v-list>
              </v-card-text>
            </v-card>
          </v-col>

          <!-- Recent Collections Sidebar -->
          <v-col cols="12" md="4">
            <v-card 
              elevation="2"
              style="border-radius: 12px; position: sticky; top: 20px;"
            >
              <v-card-title class="pa-4 bg-success text-white">
                <v-icon left>mdi-check-circle</v-icon>
                Recent Collections
              </v-card-title>
              <v-card-text class="pa-0" style="max-height: 600px; overflow-y: auto;">
                <div v-if="recentCollections.length === 0" class="text-center pa-4">
                  <v-icon size="48" color="grey-lighten-1">mdi-history</v-icon>
                  <p class="text-body-2 text-grey mt-2">No recent collections</p>
                </div>
                
                <v-list v-else density="compact">
                  <v-list-item
                    v-for="(collection, index) in recentCollections"
                    :key="`collection-${collection.id}-${index}`"
                    class="pa-3 border-b"
                  >
                    <template v-slot:prepend>
                      <v-avatar size="32" color="success">
                        <v-icon size="16">mdi-check</v-icon>
                      </v-avatar>
                    </template>
                    
                    <v-list-item-content>
                      <v-list-item-title class="text-body-2 font-weight-medium">
                        {{ collection.tracking_number }}
                      </v-list-item-title>
                      <v-list-item-subtitle class="text-caption">
                        {{ collection.cod_amount }} DA - {{ formatTime(collection.collected_at) }}
                      </v-list-item-subtitle>
                     
                    </v-list-item-content>
                  </v-list-item>
                </v-list>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </template>
  </AppLayout>
</template>

<script>
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import AppLayout from '@/Layouts/AppLayout.vue'

export default {
  name: 'StopDeskPayment',
  components: {
    AppLayout
  },
  props: {
    recentCollections: {
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      barcodeInput: '',
      searching: false,
      activeParcels: [],
      showManualEntry: false,
      manualParcel: {
        tracking_number: '',
        recipient_name: '',
        recipient_phone: '',
        recipient_address: '',
        cod_amount: null,
        company: '',
        state: '',
        city: ''
      }
    }
  },
  mounted() {
    // Focus on barcode input when component mounts
    this.$nextTick(() => {
      if (this.$refs.barcodeInput) {
        this.$refs.barcodeInput.focus()
      }
    })
  },
  methods: {
    async searchParcel() {
      if (!this.barcodeInput.trim()) return
      
      this.searching = true
      
      try {
        const response = await axios.post('/parcels/search-by-tracking', {
          tracking_number: this.barcodeInput
        })
        
        console.log('Full response:', response)
        console.log('Response data:', response.data)
        
        const data = response.data.searchResult
        console.log('Search result data:', data)
        console.log('data.success:', data.success)
        console.log('data.allow_manual_entry:', data.allow_manual_entry)
        console.log('data.message:', data.message)
        
        if (data && data.success) {
          console.log('SUCCESS BRANCH: Parcel found and valid')
          if (data.parcel) {
            // Check if parcel is already in the list
            const existingIndex = this.activeParcels.findIndex(p => p.id === data.parcel.id)
            if (existingIndex === -1) {
              // Add parcel to active list with payment fields
              const parcel = {
                ...data.parcel,
                amountGiven: null,
                changeAmount: 0
              }
              this.activeParcels.push(parcel)
              this.showManualEntry = false // Hide manual entry if shown
              console.log('Parcel added to active list, manual entry hidden')
            }
          }
        } else {
          console.log('ELSE BRANCH: Parcel not found or not valid')
          // Check if manual entry should be allowed
          if (data && data.allow_manual_entry === false) {
            console.log('DELIVERED PARCEL BRANCH: Manual entry NOT allowed')
            console.log('Current showManualEntry before change:', this.showManualEntry)
            
            // Parcel exists but is already delivered - show message and don't allow manual entry
            // this.$toast.warning(data.message || 'Parcel already delivered and collected')
            
            // Explicitly hide manual entry form
            this.showManualEntry = false
            
            // Also reset the manual parcel data
            this.resetManualParcel()
            
            console.log('Manual entry set to false, toast shown')
            console.log('Current showManualEntry after change:', this.showManualEntry)
            
            // Force Vue to update the DOM
            this.$nextTick(() => {
              console.log('After nextTick - showManualEntry:', this.showManualEntry)
            })
          } else if (data && data.allow_manual_entry === true) {
            console.log('NOT FOUND BRANCH: Manual entry allowed')
            // Parcel not found - show manual entry form
            this.showManualEntry = true
            this.manualParcel.tracking_number = this.barcodeInput
            console.log('Manual entry set to true, tracking number populated')
          } else {
            console.log('UNKNOWN RESPONSE STRUCTURE')
            console.log('data:', data)
            // Default behavior - don't show manual entry for unknown responses
            this.showManualEntry = false
          }
        }
        
        console.log('Final showManualEntry value:', this.showManualEntry)
        
      } catch (error) {
        console.error('Search error:', error)
        // Only show manual entry form on actual network/server errors, not for delivered parcels
        if (error.response && error.response.status === 422) {
          // Validation error - don't show manual entry
          this.$toast.error('Invalid tracking number format')
          this.showManualEntry = false
        } else {
          // Network or server error - allow manual entry
          this.showManualEntry = true
          this.manualParcel.tracking_number = this.barcodeInput
        }
      } finally {
        this.searching = false
        this.barcodeInput = ''
        // Refocus on input for next scan
        this.$nextTick(() => {
          if (this.$refs.barcodeInput) {
            this.$refs.barcodeInput.focus()
          }
        })
      }
    },
    
    calculateChange(parcel) {
      if (parcel.amountGiven && parcel.cod_amount) {
        parcel.changeAmount = parcel.amountGiven - parcel.cod_amount
      } else {
        parcel.changeAmount = 0
      }
    },
    
    async confirmPayment(parcel, index) {
      if (!parcel.amountGiven || parcel.amountGiven < parcel.cod_amount) {
        return
      }

      // Handle manual parcels differently
      if (parcel.isManual) {
        this.confirmManualParcelPayment(parcel, index)
        return
      }

      try {
        const response = await axios.post('/parcels/confirm-payment', {
          parcel_id: parcel.id,
          amount_given: parcel.amountGiven
        })
        
        const result = response.data.paymentResult
        if (result && result.success) {
          // Remove parcel from active list
          this.activeParcels.splice(index, 1)
          
          // Add to recent collections at the top
          const newCollection = {
            id: Date.now(), // Temporary ID for display
            collected_at: new Date().toISOString(),
            amount: parcel.amountGiven,
            tracking_number: parcel.tracking_number, // Add tracking number directly
            cod_amount: parcel.cod_amount, // Add cod_amount directly
            parcel: {
              id: parcel.id,
              tracking_number: parcel.tracking_number,
              recipient_name: parcel.recipient_name,
              cod_amount: parcel.cod_amount
            }
          }
          
          this.recentCollections.unshift(newCollection)
          
          // Keep only last 20 collections
          if (this.recentCollections.length > 20) {
            this.recentCollections = this.recentCollections.slice(0, 20)
          }
        }
      } catch (error) {
        console.error('Payment confirmation error:', error)
      }
    },
    
    async confirmManualParcelPayment(parcel, index) {
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
          city: parcel.city
        })
        
        const result = response.data.paymentResult
        if (result && result.success) {
          // Remove parcel from active list
          this.activeParcels.splice(index, 1)
          
          // Add to recent collections at the top
          if (result.collection) {
            this.recentCollections.unshift(result.collection)
            
            // Keep only last 20 collections
            if (this.recentCollections.length > 20) {
              this.recentCollections = this.recentCollections.slice(0, 20)
            }
          }
        }
      } catch (error) {
        console.error('Manual parcel creation error:', error)
      }
    },
    
    removeParcel(index) {
      const parcel = this.activeParcels[index]
      this.activeParcels.splice(index, 1)
      this.$toast.info(`Removed ${parcel.tracking_number} from queue`)
      
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
    
    addManualParcel() {
      if (!this.isManualParcelValid) return
      
      // Create a temporary parcel object for the queue
      const manualParcel = {
        id: 'manual_' + Date.now(), // Temporary ID
        tracking_number: this.manualParcel.tracking_number,
        recipient_name: this.manualParcel.recipient_name,
        recipient_phone: this.manualParcel.recipient_phone,
        recipient_address: this.manualParcel.recipient_address,
        cod_amount: parseFloat(this.manualParcel.cod_amount),
        company: this.manualParcel.company || null,
        state: this.manualParcel.state || null,
        city: this.manualParcel.city || null,
        amountGiven: null,
        changeAmount: 0,
        isManual: true // Flag to identify manual parcels
      }
      
      this.activeParcels.push(manualParcel)
      this.showManualEntry = false
      this.resetManualParcel()
      this.barcodeInput = ''
      
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

/* Custom scrollbar for sidebar */
::-webkit-scrollbar {
  width: 6px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

/* Focus styles for barcode input */
.v-text-field--focused .v-field {
  box-shadow: 0 0 0 2px rgba(25, 118, 210, 0.2);
}
</style>