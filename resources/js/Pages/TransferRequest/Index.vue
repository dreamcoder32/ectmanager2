<template>
  <div>
    <Head title="Transfer Requests" />
    
    <AppLayout>
      <template #content>
        <v-container fluid>
          <div class="d-flex justify-space-between align-center mb-4">
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
              <v-icon left>mdi-arrow-left</v-icon>
              Back to Recoltes
            </v-btn>
          </div>

          <v-card elevation="1" style="border-radius: 12px;">
            <v-data-table
              :headers="headers"
              :items="transfers.data"
              :loading="loading"
              class="elevation-1"
              hover
              @click:row="openTransfer"
              style="cursor: pointer;"
            >
              <!-- Status Column -->
              <template v-slot:[`item.status`]="{ item }">
                <v-chip
                  :color="getStatusColor(item.status)"
                  text-color="white"
                  small
                  style="font-weight: 600;"
                >
                  {{ item.status.toUpperCase() }}
                </v-chip>
              </template>

              <!-- Amount Column -->
              <template v-slot:[`item.total_amount`]="{ item }">
                <span class="font-weight-bold text-success">
                  {{ formatCurrency(item.total_amount) }} Da
                </span>
              </template>

              <!-- Code Column (Only visible if Admin or Success) -->
              <template v-slot:[`item.verification_code`]="{ item }">
                <div v-if="canSeeCode(item)">
                  <v-chip color="grey-lighten-3" class="font-weight-bold font-monospace">
                    {{ item.verification_code }}
                  </v-chip>
                </div>
                <div v-else>
                  <span class="text-grey text-caption">Hidden</span>
                </div>
              </template>

              <!-- Actions Column -->
              <template v-slot:[`item.actions`]="{ item }">
                <v-btn
                  v-if="item.status === 'pending' && isSupervisor(item)"
                  color="primary"
                  size="small"
                  @click.stop="openVerifyDialog(item)"
                  prepend-icon="mdi-check-circle"
                >
                  Enter Code
                </v-btn>
                <v-btn
                  v-if="item.status === 'pending' && isAdmin(item)"
                  color="info"
                  size="small"
                  @click.stop="showCode(item)"
                  prepend-icon="mdi-eye"
                >
                  View Code
                </v-btn>
              </template>
            </v-data-table>
          </v-card>
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
      headers: [
        { title: 'ID', key: 'id' },
        { title: 'Date', key: 'created_at' },
        { title: 'Supervisor', key: 'supervisor.first_name' },
        { title: 'Admin', key: 'admin.first_name' },
        { title: 'Amount', key: 'total_amount' },
        { title: 'Status', key: 'status' },
        { title: 'Code', key: 'verification_code' },
        { title: 'Actions', key: 'actions', sortable: false }
      ]
    }
  },
  methods: {
    formatCurrency(amount) {
      return parseFloat(amount || 0).toFixed(2)
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
    canSeeCode(item) {
      // Admin can see code always (or maybe only if pending/success?)
      // Supervisor sees code only if success? Or never?
      // User said: "supervisor cant see it".
      // Admin sees it when he clicks approve.
      return this.isAdmin(item)
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
    openTransfer(event, { item }) {
      this.$inertia.visit(`/transfer-requests/${item.id}`)
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
