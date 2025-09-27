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
                          <v-row>
                            <v-col cols="6">
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
                              <v-text-field
                                :value="parcel.changeAmount"
                                label="Change"
                                variant="outlined"
                                density="compact"
                                suffix="DA"
                                readonly
                                :color="parcel.changeAmount >= 0 ? 'success' : 'error'"
                              ></v-text-field>
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
                      <v-list-item-subtitle class="text-caption text-success">
                        Change: {{ collection.changeAmount }} DA
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
import AppLayout from '@/Layouts/AppLayout.vue'

export default {
  name: 'StopDeskPayment',
  components: {
    AppLayout
  },
  data() {
    return {
      barcodeInput: '',
      searching: false,
      activeParcels: [],
      recentCollections: []
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
      
      router.post('/parcels/search-by-tracking', {
        tracking_number: this.barcodeInput
      }, {
        preserveState: true,
        preserveScroll: true,
        onStart: () => {
          this.searching = true
        },
        onSuccess: (page) => {
          const data = page.props?.flash || {}
          if (data.success && data.parcel) {
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
              this.$toast.success(`Parcel ${data.parcel.tracking_number} added to queue`)
            } else {
              this.$toast.warning('Parcel already in queue')
            }
          } else {
            this.$toast.error(data.message || 'Parcel not found')
          }
        },
        onError: (errors) => {
          console.error('Search error:', errors)
          this.$toast.error('Error searching for parcel')
        },
        onFinish: () => {
          this.searching = false
          this.barcodeInput = ''
          // Refocus on input for next scan
          this.$nextTick(() => {
            if (this.$refs.barcodeInput) {
              this.$refs.barcodeInput.focus()
            }
          })
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
      router.post('/parcels/confirm-payment', {
        parcel_id: parcel.id,
        amount_paid: parcel.amountGiven
      }, {
        onSuccess: (page) => {
          const flash = page.props.flash;
          if (flash && flash.success) {
            // Add to recent collections
            const collection = {
              ...parcel,
              collected_at: new Date(),
              changeAmount: parcel.changeAmount
            }
            this.recentCollections.unshift(collection)
            
            // Keep only last 20 collections
            if (this.recentCollections.length > 20) {
              this.recentCollections = this.recentCollections.slice(0, 20)
            }
            
            // Remove from active parcels
            this.activeParcels.splice(index, 1)
            
            this.$toast.success(`Payment confirmed for ${parcel.tracking_number}`)
            
            // Refocus on barcode input
            this.$nextTick(() => {
              if (this.$refs.barcodeInput) {
                this.$refs.barcodeInput.focus()
              }
            })
          } else {
            this.$toast.error(flash?.message || 'Error confirming payment')
          }
        },
        onError: (errors) => {
          console.error('Payment confirmation error:', errors)
          this.$toast.error('Error confirming payment')
        }
      })
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