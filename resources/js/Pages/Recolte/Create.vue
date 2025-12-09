<template>
  <div>
    <Head title="Create Collection Transfer" />
    
    <AppLayout>
      <template #title>
        <span style="background: linear-gradient(135deg, #1976d2, #1565c0); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-weight: 600;">
          Create Collection Transfer
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
                @click="$inertia.visit('/recoltes')"
                class="mb-4"
              >
                <v-icon left>mdi-arrow-left</v-icon>
                Back to Collection Transfers
              </v-btn>

              <!-- Create Form Card -->
              <v-card elevation="2" style="border-radius: 12px;">
                <v-card-title class="pa-6" style="background: #f8f9fa; border-bottom: 1px solid #e0e0e0;">
                  <v-icon left color="primary" size="28">mdi-cash-multiple</v-icon>
                  <span class="text-h5 font-weight-medium">New Collection Transfer</span>
                </v-card-title>
                
                <v-card-text class="pa-6">
                  <v-form ref="form" @submit.prevent="submitForm">
                    <v-row>
                      <!-- User Selection -->
                      <v-col cols="12" md="6">
                        <v-select
                          v-model="selectedUserId"
                          :items="users"
                          item-value="id"
                          item-title="first_name"
                          label="Select User"
                          outlined
                          :loading="loadingUsers"
                          @update:model-value="onUserChange"
                          :error-messages="errors.user"
                        >
                          <template #prepend-inner>
                            <v-icon color="primary">mdi-account</v-icon>
                          </template>
                          <template #item="{ props, item }">
                            <v-list-item v-bind="props">
                              <!-- <v-list-item-title>{{ item.raw.first_name }}</v-list-item-title> -->
                              <v-list-item-subtitle>{{ item.raw.email }} - {{ item.raw.role }}</v-list-item-subtitle>
                            </v-list-item>
                          </template>
                        </v-select>
                      </v-col>

                      <!-- Case Selection -->
                      <!-- <v-col cols="12" md="6">
                        <v-select
                          v-model="form.case_id"
                          :items="activeCases"
                          item-text="name"
                          item-value="id"
                          label="Select Money Case (Optional)"
                          outlined
                          clearable
                          :error-messages="errors.case_id"
                          prepend-inner-icon="mdi-briefcase"
                        >
                          <template v-slot:item="{ item }">
                            <v-list-item-content>
                              <v-list-item-title>{{ item.name }}</v-list-item-title>
                              <v-list-item-subtitle>
                                Balance: {{ formatCurrency(item.balance) }} - {{ item.description }}
                              </v-list-item-subtitle>
                            </v-list-item-content>
                          </template>
                        </v-select>
                      </v-col> -->

                      <!-- Note Field -->
                      <v-col cols="12" md="6">
                        <v-textarea
                          v-model="form.note"
                          label="Note (Optional)"
                          placeholder="Add any notes or comments..."
                          outlined
                          rows="2"
                          :error-messages="errors.note"
                          @input="clearError('note')"
                        >
                          <template #prepend-inner>
                            <v-icon color="grey">mdi-note-text</v-icon>
                          </template>
                        </v-textarea>
                      </v-col>


                      <!-- Collections Table -->
                      <v-col cols="12" v-if="selectedUserId && collections.length > 0">
                        <v-card outlined style="border-radius: 8px;">
                          <v-card-title class="pb-2">
                            <v-icon left color="success">mdi-package-variant</v-icon>
                            <span class="text-h6">Collections by {{ selectedUserName }}</span>
                            <v-spacer></v-spacer>
                            <v-chip
                              color="primary"
                              text-color="white"
                              small
                            >
                              Total: {{ formatCurrency(totalAmount) }}
                            </v-chip>
                          </v-card-title>
                          
                          <v-card-text class="pa-0">
                            <v-data-table
                              v-model="form.collection_ids"
                              :headers="headers"
                              :items="collections"
                              :loading="loadingCollections"
                              show-select
                              item-key="id"
                              class="elevation-0"
                              :items-per-page="60"
                            >
                              <template #[`item.parcel.tracking_number`]="{ item }">
                                <v-chip
                                  small
                                  color="info"
                                  text-color="white"
                                >
                                  {{ item.parcel?.tracking_number || 'N/A' }}
                                </v-chip>
                              </template>

                              <template #[`item.parcel.recipient_name`]="{ item }">
                                <div class="font-weight-medium">
                                  {{ item.parcel?.recipient_name || 'N/A' }}
                                </div>
                              </template>

                              <template #[`item.amount`]="{ item }">
                                <v-chip
                                  small
                                  color="success"
                                  text-color="white"
                                >
                                  {{ formatCurrency(item.amount) }}
                                </v-chip>
                              </template>

                              <template #[`item.collected_at`]="{ item }">
                                <div class="text-caption">
                                  {{ formatDate(item.collected_at) }}
                                </div>
                              </template>
                            </v-data-table>
                          </v-card-text>
                        </v-card>
                      </v-col>

                      <!-- Unlinked Expenses Alert -->
                      <v-col cols="12" v-if="selectedUserId && unlinkedExpenses.length > 0">
                        <v-alert
                          type="warning"
                          variant="tonal"
                          border="start"
                          elevation="2"
                        >
                          <template #prepend>
                            <v-icon color="warning">mdi-cash-minus</v-icon>
                          </template>
                          <div class="text-subtitle-1 font-weight-bold">
                            Pending Expenses Found
                          </div>
                          <div class="text-body-2">
                            This user has {{ unlinkedExpenses.length }} unlinked expenses totaling 
                            <strong>{{ formatCurrency(unlinkedExpenses.reduce((sum, e) => sum + parseFloat(e.amount), 0)) }}</strong>.
                            These will be automatically linked to this Recolte.
                          </div>
                          <v-divider class="my-2"></v-divider>
                          <v-list density="compact" bg-color="transparent">
                            <v-list-item v-for="expense in unlinkedExpenses" :key="expense.id" :title="expense.title" :subtitle="formatDate(expense.expense_date)">
                              <template #append>
                                <span class="text-error font-weight-bold">- {{ formatCurrency(expense.amount) }}</span>
                              </template>
                            </v-list-item>
                          </v-list>
                        </v-alert>
                      </v-col>


                      <!-- Manual Amount Field -->
                      <v-col cols="12" md="6">
                        <v-text-field
                          v-model="form.manual_amount"
                          label="Manual Amount (DZD) *"
                          type="number"
                          step="0.01"
                          outlined
                          required
                          :error-messages="errors.manual_amount"
                          @input="clearError('manual_amount')"
                          prepend-inner-icon="mdi-cash"
                          :hint="`Calculated total: ${formatCurrency(totalAmount)}`"
                          persistent-hint
                        ></v-text-field>
                      </v-col>

                      <!-- Discrepancy Note Field (shown when amounts differ) -->
                      <v-col cols="12" v-if="hasAmountDiscrepancy">
                        <v-textarea
                          v-model="form.amount_discrepancy_note"
                          label="Amount Discrepancy Note *"
                          placeholder="Please explain why the manual amount differs from the calculated total..."
                          outlined
                          rows="3"
                          required
                          :error-messages="errors.amount_discrepancy_note"
                          @input="clearError('amount_discrepancy_note')"
                          color="warning"
                        >
                          <template #prepend-inner>
                            <v-icon color="warning">mdi-alert-circle</v-icon>
                          </template>
                        </v-textarea>
                      </v-col>

                      <!-- No Collections Message -->
                      <v-col cols="12" v-else-if="selectedUserId && collections.length === 0 && !loadingCollections">
                        <v-alert
                          type="info"
                          outlined
                          class="ma-0"
                        >
                          <v-icon slot="prepend">mdi-information</v-icon>
                          No available collections found for the selected user.
                        </v-alert>
                      </v-col>

                      <!-- User Selection Prompt -->
                      <v-col cols="12" v-else-if="!selectedUserId">
                        <v-alert
                          type="info"
                          outlined
                          class="ma-0"
                        >
                          <v-icon slot="prepend">mdi-account-search</v-icon>
                          Please select a user to view their collections.
                        </v-alert>
                      </v-col>
                    </v-row>

                    <!-- Submit Button -->
                    <v-row class="mt-4">
                      <v-col cols="12" class="text-right">
                        <v-btn
                          color="grey"
                          text
                          @click="$inertia.visit('/recoltes')"
                          class="mr-3"
                        >
                          Cancel
                        </v-btn>
                        <v-btn
                          type="submit"
                          color="primary"
                          :loading="processing"
                          :disabled="!canSubmit"
                          elevation="2"
                        >
                          <v-icon left>mdi-check</v-icon>
                          Create Collection Transfer
                        </v-btn>
                      </v-col>
                    </v-row>
                  </v-form>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>
        </v-container>
      </template>
    </AppLayout>
  </div>
</template>

<script>
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

export default {
  name: 'RecolteCreate',
  components: {
    Head,
    AppLayout,
  },
  props: {
    users: {
      type: Array,
      default: () => []
    },
    activeCases: {
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      selectedUserId: null,
      selectedUserName: '',
      collections: [],
      unlinkedExpenses: [],
      totalAmount: 0,
      loadingUsers: false,
      loadingCollections: false,
      processing: false,
      form: {
        note: '',
        collection_ids: [],
        case_id: null,
        manual_amount: null,
        amount_discrepancy_note: ''
      },
      errors: {},
      headers: [
        { text: 'Tracking Number', value: 'parcel.tracking_number', sortable: false },
        { text: 'Client Name', value: 'parcel.recipient_name', sortable: false },
        { text: 'Amount', value: 'amount', sortable: true },
        { text: 'Collected At', value: 'collected_at', sortable: true }
      ]
    }
  },
  computed: {
    hasAmountDiscrepancy() {
      if (!this.form.manual_amount || !this.totalAmount) return false
      return Math.abs(this.totalAmount - this.form.manual_amount) > 0.01
    },
    canSubmit() {
      const hasRequiredFields = this.selectedUserId && this.form.collection_ids.length > 0 && this.form.manual_amount > 0
      const hasDiscrepancyNote = !this.hasAmountDiscrepancy || (this.hasAmountDiscrepancy && this.form.amount_discrepancy_note.trim())
      return hasRequiredFields && hasDiscrepancyNote && !this.processing
    }
  },
  methods: {
    onUserChange() {
      if (!this.selectedUserId) {
        this.collections = []
        this.totalAmount = 0
        this.form.collection_ids = []
        this.form.manual_amount = null
        this.form.amount_discrepancy_note = ''
        return
      }

      const selectedUser = this.users.find(user => user.id === this.selectedUserId)
      if (selectedUser) {
        this.selectedUserName = selectedUser.first_name
        this.collections = selectedUser.collections || []
        this.unlinkedExpenses = selectedUser.unlinked_expenses || []
        this.totalAmount = selectedUser.total_amount || 0
        this.form.collection_ids = []
        // this.form.manual_amount = this.totalAmount // Pre-fill with calculated total
        this.form.amount_discrepancy_note = ''
        
        console.log('Selected user:', selectedUser.first_name)
        console.log('Collections:', this.collections)
        console.log('Total amount:', this.totalAmount)
      }
    },

    async submitForm() {
      if (!this.canSubmit) return

      this.processing = true
      this.errors = {}

      try {
        await this.$inertia.post('/recoltes', this.form, {
          onError: (errors) => {
            this.errors = errors
          },
          onSuccess: () => {
            this.$toast.success('Collection transfer created successfully!')
          }
        })
      } catch (error) {
        console.error('Error creating recolte:', error)
        this.$toast.error('Failed to create collection transfer')
      } finally {
        this.processing = false
      }
    },

    clearError(field) {
      if (this.errors[field]) {
        delete this.errors[field]
      }
    },

    formatCurrency(amount) {
      return new Intl.NumberFormat('fr-DZ', {
        style: 'currency',
        currency: 'DZD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
      }).format(amount || 0)
    },

    formatDate(date) {
      if (!date) return 'N/A'
      return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }
  },
  mounted() {
    console.log('Users prop:', this.users)
    console.log('Users length:', this.users.length)
    if (this.users.length > 0) {
      console.log('First user:', this.users[0])
    }
  }
}
</script>

<style scoped>
.v-data-table >>> .v-data-table__wrapper {
  border-radius: 8px;
}

.v-card {
  transition: all 0.3s ease;
}

.v-card:hover {
  transform: translateY(-2px);
}

.v-alert {
  border-radius: 8px;
}

.v-chip {
  font-weight: 500;
}
</style>