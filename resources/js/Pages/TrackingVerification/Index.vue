<template>
  <AppLayout title="Tracking Verification">
    <v-row>
      <v-col cols="12" md="8" offset-md="2">
        <v-card class="pa-6" elevation="0" style="border-radius: 16px; border: 1px solid #e5e7eb;">
          <v-card-title class="text-h5 font-weight-bold mb-4">
            Verify Tracking Status
          </v-card-title>
          
          <v-card-text>
            <div class="mb-6 text-body-1 text-grey-darken-1">
              Enter a tracking number to check its latest status on EcoTrack.
            </div>

            <v-form @submit.prevent="verify" :disabled="loading">
              <v-row align="center">
                <v-col cols="12" sm="8" md="9">
                  <v-text-field
                    v-model="form.tracking_number"
                    label="Tracking Number"
                    placeholder="Example: ECCOXF2601163861598"
                    variant="outlined"
                    density="comfortable"
                    hide-details="auto"
                    :error-messages="form.errors.tracking_number"
                    prepend-inner-icon="mdi-barcode-scan"
                  ></v-text-field>
                </v-col>
                <v-col cols="12" sm="4" md="3">
                  <v-btn
                    type="submit"
                    color="primary"
                    block
                    height="48"
                    :loading="loading"
                    class="text-none font-weight-bold"
                    elevation="0"
                  >
                    Check Status
                  </v-btn>
                </v-col>
              </v-row>
            </v-form>

            <v-expand-transition>
              <div v-if="result" class="mt-8">
                <v-divider class="mb-6"></v-divider>
                
                <div class="d-flex align-center mb-4">
                  <v-icon size="24" color="primary" class="mr-2">mdi-cube-send</v-icon>
                  <span class="text-h6 font-weight-bold">Result for {{ result.tracking_number }}</span>
                </div>

                <v-sheet
                  rounded="lg"
                  class="pa-6"
                  :class="result.status !== 'Status not found' && !result.status.startsWith('Error') ? 'bg-green-lighten-5 border-success' : 'bg-red-lighten-5 border-error'"
                  style="border: 1px solid;"
                >
                  <div class="d-flex flex-column flex-sm-row justify-space-between align-start align-sm-center">
                    <div>
                      <div class="text-overline text-grey-darken-1 mb-1">Current Status</div>
                      <div class="text-h4 font-weight-bold" :class="result.status !== 'Status not found' && !result.status.startsWith('Error') ? 'text-success' : 'text-error'">
                        {{ result.status }}
                      </div>
                    </div>
                    
                    <div v-if="result.date" class="mt-4 mt-sm-0 text-right">
                      <div class="text-overline text-grey-darken-1 mb-1">Last Update</div>
                      <div class="text-h6 font-weight-medium">
                        {{ result.date }}
                      </div>
                    </div>
                  </div>

                  <v-divider class="my-4" v-if="result.internal_status"></v-divider>

                  <div class="d-flex flex-column flex-sm-row justify-space-between align-start align-sm-center" v-if="result.internal_status">
                    <div>
                      <div class="text-overline text-grey-darken-1 mb-1">Internal Status</div>
                      <div class="text-h5 font-weight-bold" :class="getInternalStatusColor(result.internal_status)">
                        {{ result.internal_status }}
                      </div>
                      <div v-if="result.recipient_name" class="text-body-2 text-grey-darken-1 mt-1">
                        {{ result.recipient_name }} - {{ result.recipient_phone }}
                      </div>
                    </div>
                    
                     <div v-if="result.internal_updated_at" class="mt-4 mt-sm-0 text-right">
                      <div class="text-overline text-grey-darken-1 mb-1">Last Internal Update</div>
                      <div class="text-body-1 font-weight-medium">
                        {{ result.internal_updated_at }}
                      </div>
                    </div>
                  </div>
                </v-sheet>
              </div>
            </v-expand-transition>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </AppLayout>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm } from '@inertiajs/vue3'

export default {
  components: {
    AppLayout
  },
  props: {
    // Expect result from flash data or direct prop
  },
  data() {
    return {
      form: useForm({
        tracking_number: ''
      }),
      loading: false,
      result: null
    }
  },
  computed: {
    flashResult() {
      return this.$page.props.flash?.result
    }
  },
  watch: {
    flashResult: {
      immediate: true,
      handler(newVal) {
        if (newVal) {
          this.result = newVal
          // Update the form tracking number if not set (e.g. if loaded URL with query param in future)
          if (!this.form.tracking_number && newVal.tracking_number) {
            this.form.tracking_number = newVal.tracking_number
          }
        }
      }
    }
  },
  methods: {
    verify() {
      this.loading = true
      this.result = null
      
      this.form.post(route('tracking-verification.verify'), {
        preserveScroll: true,
        onSuccess: () => {
          this.loading = false
          // Result will be updated via flash prop watcher
        },
        onError: () => {
          this.loading = false
        },
        onFinish: () => {
          this.loading = false
        }
      })
    },
    getInternalStatusColor(status) {
        if (!status) return 'text-grey';
        const s = status.toLowerCase();
        if (s === 'delivered') return 'text-success';
        if (s === 'returned') return 'text-error';
        if (s === 'pending') return 'text-warning';
        if (s === 'not found') return 'text-grey';
        return 'text-info';
    }
  }
}
</script>

<style scoped>
.border-success {
  border-color: #4caf50 !important;
}
.border-error {
  border-color: #ef5350 !important;
}
</style>
