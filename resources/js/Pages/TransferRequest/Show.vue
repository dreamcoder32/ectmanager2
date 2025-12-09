<template>
  <div>
    <Head title="Transfer Request Details" />
    
    <AppLayout>
      <template #content>
        <v-container fluid>
          <!-- Header -->
          <div class="d-flex justify-space-between align-center mb-6">
            <div class="d-flex align-center">
              <v-btn
                icon
                variant="text"
                class="mr-4"
                @click="$inertia.visit('/transfer-requests')"
              >
                <v-icon>mdi-arrow-left</v-icon>
              </v-btn>
              <div>
                <div class="text-h5 font-weight-bold">
                  Transfer Request #{{ transfer.id }}
                </div>
                <div class="text-subtitle-2 text-grey">
                  Created on {{ formatDate(transfer.created_at) }}
                </div>
              </div>
            </div>
            
            <div class="d-flex align-center">
              <v-chip
                :color="getStatusColor(transfer.status)"
                text-color="white"
                class="mr-4"
                size="large"
                style="font-weight: 600;"
              >
                {{ transfer.status.toUpperCase() }}
              </v-chip>
              
              <v-btn
                v-if="transfer.status === 'pending' && isSupervisor"
                color="primary"
                @click="verifyDialog = true"
                prepend-icon="mdi-check-circle"
                class="mr-2"
              >
                Enter Code
              </v-btn>
              
              <v-btn
                v-if="transfer.status === 'pending' && isAdmin"
                color="success"
                @click="codeDialog = true"
                prepend-icon="mdi-check-decagram"
              >
                Approve & View Code
              </v-btn>
            </div>
          </div>

          <!-- Summary Cards -->
          <v-row class="mb-6">
            <v-col cols="12" md="4">
              <v-card elevation="1" class="h-100" style="border-radius: 12px;">
                <v-card-text class="d-flex flex-column align-center justify-center py-6">
                  <div class="text-subtitle-1 text-grey mb-2">Total Amount</div>
                  <div class="text-h4 font-weight-bold text-success">
                    {{ formatCurrency(transfer.total_amount) }} Da
                  </div>
                </v-card-text>
              </v-card>
            </v-col>
            <v-col cols="12" md="4">
              <v-card elevation="1" class="h-100" style="border-radius: 12px;">
                <v-card-text class="py-6">
                  <div class="text-subtitle-1 text-grey mb-4">Participants</div>
                  <div class="d-flex align-center mb-3">
                    <v-icon color="primary" class="mr-3">mdi-account-tie</v-icon>
                    <div>
                      <div class="text-caption text-grey">Supervisor (Sender)</div>
                      <div class="font-weight-medium">{{ transfer.supervisor.first_name }} {{ transfer.supervisor.last_name }}</div>
                    </div>
                  </div>
                  <div class="d-flex align-center">
                    <v-icon color="secondary" class="mr-3">mdi-shield-account</v-icon>
                    <div>
                      <div class="text-caption text-grey">Admin (Receiver)</div>
                      <div class="font-weight-medium">{{ transfer.admin.first_name }} {{ transfer.admin.last_name }}</div>
                    </div>
                  </div>
                </v-card-text>
              </v-card>
            </v-col>
            <v-col cols="12" md="4" v-if="canSeeCode">
              <v-card elevation="1" class="h-100" style="border-radius: 12px; border: 1px dashed #bdbdbd;">
                <v-card-text class="d-flex flex-column align-center justify-center py-6">
                  <div class="text-subtitle-1 text-grey mb-2">Verification Code</div>
                  <div class="text-h4 font-weight-bold font-monospace text-primary">
                    {{ verificationCode }}
                  </div>
                  <div class="text-caption text-grey mt-2">Share this with the supervisor</div>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>

          <!-- Recoltes List -->
          <v-card elevation="1" style="border-radius: 12px;">
            <v-card-title class="pa-4 bg-grey-lighten-4">
              Included Recoltes ({{ transfer.recoltes.length }})
            </v-card-title>
            <v-data-table
              :headers="headers"
              :items="transfer.recoltes"
              class="elevation-0"
            >
              <template v-slot:[`item.code`]="{ item }">
                <span class="font-weight-medium">#RCT-{{ item.code }}</span>
              </template>
              
              <template v-slot:[`item.amount`]="{ item }">
                <span class="text-success font-weight-bold">
                  {{ formatCurrency(item.manual_amount || 0) }} Da
                </span>
              </template>

              <template v-slot:[`item.expenses`]="{ item }">
                <span v-if="item.expenses && item.expenses.length > 0" class="text-error">
                  -{{ formatCurrency(item.expenses.reduce((sum, e) => sum + parseFloat(e.amount), 0)) }} Da
                </span>
                <span v-else class="text-grey">-</span>
              </template>

              <template v-slot:[`item.net`]="{ item }">
                <span class="font-weight-bold">
                  {{ formatCurrency((item.manual_amount || 0) - (item.expenses ? item.expenses.reduce((sum, e) => sum + parseFloat(e.amount), 0) : 0)) }} Da
                </span>
              </template>
              
              <template v-slot:[`item.actions`]="{ item }">
                 <v-btn
                    icon
                    size="small"
                    variant="text"
                    @click="$inertia.visit(`/recoltes/${item.id}`)"
                  >
                    <v-icon>mdi-eye</v-icon>
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
            v-model="inputCode"
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
            :disabled="!inputCode"
          >
            Verify
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Code Display Dialog -->
    <v-dialog v-model="codeDialog" max-width="400px">
      <v-card style="border-radius: 12px;">
        <v-card-title class="text-h5 pa-6 bg-success text-white">
          Approve Transfer
        </v-card-title>
        <v-card-text class="pa-6 text-center">
          <p class="text-subtitle-1 mb-4">Provide this code to the supervisor to complete the transfer:</p>
          <div class="text-h2 font-weight-bold my-4 font-monospace text-primary">
            {{ verificationCode }}
          </div>
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
  name: 'TransferRequestShow',
  components: {
    Head,
    AppLayout
  },
  props: {
    transfer: Object,
    canSeeCode: Boolean,
    verificationCode: String
  },
  data() {
    return {
      verifyDialog: false,
      codeDialog: false,
      inputCode: '',
      verifying: false,
      headers: [
        { title: 'Recolte Code', key: 'code' },
        { title: 'Note', key: 'note' },
        { title: 'Collected Amount', key: 'amount' },
        { title: 'Expenses', key: 'expenses' },
        { title: 'Net Amount', key: 'net' },
        { title: 'Actions', key: 'actions', sortable: false, align: 'end' }
      ]
    }
  },
  computed: {
    isSupervisor() {
      return this.$page.props.auth.user.id === this.transfer.supervisor_id
    },
    isAdmin() {
      return this.$page.props.auth.user.id === this.transfer.admin_id
    }
  },
  methods: {
    formatDate(date) {
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    },
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
    submitVerify() {
      if (!this.inputCode) return

      this.verifying = true
      router.post(`/transfer-requests/${this.transfer.id}/verify`, {
        code: this.inputCode
      }, {
        onSuccess: () => {
          this.verifyDialog = false
          this.verifying = false
          this.inputCode = ''
        },
        onError: () => {
          this.verifying = false
        }
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
