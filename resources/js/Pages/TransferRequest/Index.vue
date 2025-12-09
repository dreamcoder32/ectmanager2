<template>
  <div>
    <Head title="Transfer Requests" />
    
    <AppLayout>
      <template #content>
        <v-container fluid>
          <div class="d-flex justify-space-between align-center mb-6">
            <span class="text-h4 font-weight-bold" 
                  style="background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
                         -webkit-background-clip: text;
                         -webkit-text-fill-color: transparent;
                         background-clip: text;">
              Transfer Requests
            </span>
            <v-btn
              color="secondary"
              @click="$inertia.visit('/recoltes')"
              prepend-icon="mdi-arrow-left"
              style="font-weight: 600; border-radius: 12px;"
              elevation="2"
            >
              Back to Recoltes
            </v-btn>
          </div>

          <!-- Cards Grid -->
          <v-row>
            <v-col 
              v-for="item in transfers.data" 
              :key="item.id"
              cols="12" 
              sm="6" 
              md="4" 
              lg="3"
            >
              <v-card 
                elevation="2" 
                class="h-100 d-flex flex-column"
                style="border-radius: 16px; transition: transform 0.2s;"
                hover
                @click="openTransfer(item)"
              >
                <!-- Card Header -->
                <div class="pa-4 d-flex justify-space-between align-center" style="border-bottom: 1px solid #f0f0f0;">
                  <div class="text-subtitle-1 font-weight-bold text-grey-darken-2">
                    #{{ item.id }}
                  </div>
                  <v-chip
                    :color="getStatusColor(item.status)"
                    text-color="white"
                    size="small"
                    style="font-weight: 600;"
                  >
                    {{ item.status.toUpperCase() }}
                  </v-chip>
                </div>

                <!-- Card Body -->
                <v-card-text class="flex-grow-1 pt-4">
                  <div class="mb-4 text-center">
                    <div class="text-caption text-grey mb-1">Total Amount</div>
                    <div class="text-h4 font-weight-bold text-success">
                      {{ formatCurrency(item.total_amount) }} <span class="text-subtitle-1">Da</span>
                    </div>
                  </div>

                  <div class="d-flex align-center mb-2">
                    <v-icon size="small" color="primary" class="mr-2">mdi-account-tie</v-icon>
                    <div class="text-body-2">
                      <span class="text-grey">From:</span> 
                      <span class="font-weight-medium ml-1">{{ item.supervisor.first_name }} {{ item.supervisor.last_name }}</span>
                    </div>
                  </div>

                  <div class="d-flex align-center mb-2">
                    <v-icon size="small" color="secondary" class="mr-2">mdi-shield-account</v-icon>
                    <div class="text-body-2">
                      <span class="text-grey">To:</span> 
                      <span class="font-weight-medium ml-1">{{ item.admin.first_name }} {{ item.admin.last_name }}</span>
                    </div>
                  </div>

                  <div class="d-flex align-center">
                    <v-icon size="small" color="grey" class="mr-2">mdi-calendar-clock</v-icon>
                    <div class="text-caption text-grey">
                      {{ formatDate(item.created_at) }}
                    </div>
                  </div>
                </v-card-text>

                <!-- Card Actions -->
                <v-card-actions class="pa-4 pt-0">
                  <v-btn
                    v-if="item.status === 'pending' && isSupervisor(item)"
                    color="primary"
                    variant="flat"
                    block
                    @click.stop="openVerifyDialog(item)"
                    prepend-icon="mdi-check-circle"
                    class="text-none"
                  >
                    Enter Code
                  </v-btn>
                  
                  <v-btn
                    v-else-if="item.status === 'pending' && isAdmin(item)"
                    color="info"
                    variant="flat"
                    block
                    @click.stop="showCode(item)"
                    prepend-icon="mdi-eye"
                    class="text-none"
                  >
                    View Code
                  </v-btn>

                  <v-btn
                    v-else
                    variant="tonal"
                    block
                    color="grey-darken-1"
                    @click.stop="openTransfer(item)"
                    prepend-icon="mdi-eye-outline"
                    class="text-none"
                  >
                    View Details
                  </v-btn>
                </v-card-actions>
              </v-card>
            </v-col>
          </v-row>

          <!-- Pagination -->
          <div class="d-flex justify-center mt-6" v-if="transfers.links && transfers.links.length > 3">
             <v-pagination
              v-model="page"
              :length="transfers.last_page"
              @update:modelValue="onPageChange"
              rounded="circle"
            ></v-pagination>
          </div>

        </v-container>
      </template>
    </AppLayout>

    <!-- Verify Dialog -->
    <v-dialog v-model="verifyDialog" max-width="400px">
      <v-card style="border-radius: 12px;">
        <v-card-title class="text-h5 pa-6">
          Verify Transfer
        </v-card-title>
        <v-card-text class="pa-6 pt-0">
          <p class="mb-4">Enter the verification code provided by the admin.</p>
          <v-text-field
            v-model="verificationCode"
            label="Verification Code"
            variant="outlined"
            density="comfortable"
            autofocus
            @keyup.enter="submitVerify"
          ></v-text-field>
        </v-card-text>
        <v-card-actions class="pa-6 pt-0">
          <v-spacer></v-spacer>
          <v-btn text @click="verifyDialog = false">Cancel</v-btn>
          <v-btn
            color="primary"
            @click="submitVerify"
            :loading="verifying"
            :disabled="!verificationCode"
          >
            Verify
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Code Display Dialog (Admin) -->
    <v-dialog v-model="codeDialog" max-width="400px">
      <v-card style="border-radius: 12px;">
        <v-card-title class="text-h5 pa-6 bg-primary text-white">
          Verification Code
        </v-card-title>
        <v-card-text class="pa-6 text-center">
          <div class="text-h2 font-weight-bold my-4 font-monospace">
            {{ selectedTransfer?.verification_code }}
          </div>
          <p class="text-grey">Share this code with the supervisor to complete the transfer.</p>
        </v-card-text>
        <v-card-actions class="pa-6 pt-0">
          <v-spacer></v-spacer>
          <v-btn color="primary" block @click="codeDialog = false">Close</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script>
import { Head } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

export default {
  name: 'TransferRequestIndex',
  components: {
    Head,
    AppLayout
  },
  props: {
    transfers: Object
  },
  data() {
    return {
      loading: false,
      verifyDialog: false,
      codeDialog: false,
      selectedTransfer: null,
      verificationCode: '',
      verifying: false,
      page: this.transfers.current_page
    }
  },
  methods: {
    formatCurrency(amount) {
      return parseFloat(amount || 0).toFixed(2)
    },
    formatDate(date) {
      return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    },
    getStatusColor(status) {
      switch (status) {
        case 'pending': return 'warning'
        case 'success': return 'success'
        case 'rejected': return 'error'
        default: return 'grey'
      }
    },
    isSupervisor(item) {
      return this.$page.props.auth.user.id === item.supervisor_id
    },
    isAdmin(item) {
      return this.$page.props.auth.user.id === item.admin_id
    },
    openVerifyDialog(item) {
      this.selectedTransfer = item
      this.verificationCode = ''
      this.verifyDialog = true
    },
    submitVerify() {
      if (!this.selectedTransfer || !this.verificationCode) return

      this.verifying = true
      router.post(`/transfer-requests/${this.selectedTransfer.id}/verify`, {
        code: this.verificationCode
      }, {
        onSuccess: () => {
          this.verifyDialog = false
          this.selectedTransfer = null
          this.verifying = false
        },
        onError: () => {
          this.verifying = false
        }
      })
    },
    showCode(item) {
      this.selectedTransfer = item
      this.codeDialog = true
    },
    openTransfer(item) {
      this.$inertia.visit(`/transfer-requests/${item.id}`)
    },
    onPageChange(page) {
      this.$inertia.visit('/transfer-requests', {
        data: { page },
        preserveState: true,
        preserveScroll: true,
        only: ['transfers']
      })
    }
  }
}
</script>

<style scoped>
.font-monospace {
  font-family: monospace;
  letter-spacing: 2px;
}
</style>
