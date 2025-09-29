<template>
  <AppLayout>
    <template #title>
      {{ $t('expenses.title') }}
    </template>
    
  

    <v-container fluid class="pa-6">
      <!-- Statistics Cards -->
      <v-row class="mb-6">
        <v-col cols="12" sm="6" md="3">
          <v-card style="border-radius: 12px;" elevation="1">
            <v-card-text class="text-center pa-4">
              <v-avatar color="warning" size="56" class="mb-3">
                <v-icon color="white" size="28">mdi-clock-outline</v-icon>
              </v-avatar>
              <div class="text-h5 font-weight-bold">{{ stats.pending || 0 }}</div>
              <div class="text-subtitle-2 text--secondary">{{ $t('expenses.status.pending') }}</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" sm="6" md="3">
          <v-card style="border-radius: 12px;" elevation="1">
            <v-card-text class="text-center pa-4">
              <v-avatar color="success" size="56" class="mb-3">
                <v-icon color="white" size="28">mdi-check-circle</v-icon>
              </v-avatar>
              <div class="text-h5 font-weight-bold">{{ stats.approved || 0 }}</div>
              <div class="text-subtitle-2 text--secondary">{{ $t('expenses.status.approved') }}</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" sm="6" md="3">
          <v-card style="border-radius: 12px;" elevation="1">
            <v-card-text class="text-center pa-4">
              <v-avatar color="primary" size="56" class="mb-3">
                <v-icon color="white" size="28">mdi-cash-check</v-icon>
              </v-avatar>
              <div class="text-h5 font-weight-bold">{{ stats.paid || 0 }}</div>
              <div class="text-subtitle-2 text--secondary">{{ $t('expenses.status.paid') }}</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" sm="6" md="3">
          <v-card style="border-radius: 12px;" elevation="1">
            <v-card-text class="text-center pa-4">
              <v-avatar color="error" size="56" class="mb-3">
                <v-icon color="white" size="28">mdi-close-circle</v-icon>
              </v-avatar>
              <div class="text-h5 font-weight-bold">{{ stats.rejected || 0 }}</div>
              <div class="text-subtitle-2 text--secondary">{{ $t('expenses.status.rejected') }}</div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Filters -->
      <v-card class="mb-6" style="border-radius: 12px;" elevation="1">
        <v-card-text>
          <v-row>
            <v-col cols="12" md="3">
              <v-select
                v-model="filters.status"
                :items="statusOptions"
                :label="$t('expenses.filter_by_status')"
                clearable
                variant="outlined"
                density="compact"
                @update:model-value="applyFilters"
              ></v-select>
            </v-col>
            <v-col cols="12" md="3">
              <v-select
                v-model="filters.category"
                :items="categoryOptions"
                :label="$t('expenses.filter_by_category')"
                clearable
                variant="outlined"
                density="compact"
                @update:model-value="applyFilters"
              ></v-select>
            </v-col>
            <v-col cols="12" md="3">
              <v-text-field
                v-model="filters.search"
                :label="$t('expenses.search_placeholder')"
                prepend-inner-icon="mdi-magnify"
                clearable
                variant="outlined"
                density="compact"
                @update:model-value="applyFilters"
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="3" class="d-flex align-center">
              <v-btn
                @click="clearFilters"
                variant="outlined"
                color="grey"
                prepend-icon="mdi-filter-off"
              >
                {{ $t('expenses.clear_filters') }}
              </v-btn>
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>


    <v-row class="mb-4">
        <v-col>
          <div class="d-flex justify-space-between align-center">
            <div>
              <h1 class="text-h4 font-weight-bold">Expenses</h1>
              <p class="text-subtitle-1 text-grey-darken-1">Manage expenses</p>
            </div>
            <v-btn
              color="primary"
              prepend-icon="mdi-plus"
              @click="$inertia.visit('/expenses/create')"
            >
        {{ $t('expenses.create_new') }}
            </v-btn>
          </div>
        </v-col>
      </v-row>
      <!-- Data Table -->
      <v-card style="border-radius: 12px;" elevation="1">
        <v-data-table
          :headers="headers"
          :items="expenses.data"
          :loading="loading"
          class="simple-table"
          item-key="id"
          :items-per-page="15"
          :server-items-length="expenses.total"
        >
          <template v-slot:[`item.title`]="{ item }">
            <div>
              <div class="text-subtitle-2 font-weight-medium">{{ item.title }}</div>
              <div class="text-caption text--secondary" v-if="item.description">
                {{ item.description.substring(0, 60) }}{{ item.description.length > 60 ? '...' : '' }}
              </div>
            </div>
          </template>

          <template v-slot:[`item.amount`]="{ item }">
            <v-chip
              small
              :color="getAmountColor(item.amount)"
              text-color="white"
            >
              {{ formatCurrency(item.amount, item.currency) }}
            </v-chip>
          </template>

          <template v-slot:[`item.category.name`]="{ item }">
            <v-chip
              small
              color="info"
              text-color="white"
              v-if="item.category"
            >
              {{ item.category.name }}
            </v-chip>
            <span v-else class="text--secondary">{{ $t('expenses.no_category') }}</span>
          </template>

          <template v-slot:[`item.status`]="{ item }">
            <v-chip
              small
              :color="getStatusColor(item.status)"
              text-color="white"
            >
              <v-icon left size="16">{{ getStatusIcon(item.status) }}</v-icon>
              {{ item.status.charAt(0).toUpperCase() + item.status.slice(1) }}
            </v-chip>
          </template>

          <template v-slot:[`item.money_case.name`]="{ item }">
            <v-chip
              small
              color="secondary"
              text-color="white"
              v-if="item.money_case"
            >
              {{ item.money_case.name }}
            </v-chip>
            <span v-else class="text--secondary">{{ $t('expenses.no_case_assigned') }}</span>
          </template>

          <template v-slot:[`item.created_by.name`]="{ item }">
            <div class="d-flex align-center">
              <v-avatar size="24" class="mr-2">
                <v-icon>mdi-account</v-icon>
              </v-avatar>
              <span class="text-body-2">{{ item.created_by?.uid || $t('common.unknown') }}</span>
            </div>
          </template>

          <template v-slot:[`item.expense_date`]="{ item }">
            <span class="text-body-2">{{ formatDate(item.expense_date) }}</span>
          </template>

          <template v-slot:[`item.actions`]="{ item }">
            <div class="d-flex align-center">
              <v-btn
                :href="route('expenses.show', item.id)"
                icon
                size="small"
                color="info"
                variant="text"
              >
                <v-icon>mdi-eye</v-icon>
                <v-tooltip activator="parent">{{ $t('common.view_details') }}</v-tooltip>
              </v-btn>

              <v-btn
                v-if="canEdit(item)"
                :href="route('expenses.edit', item.id)"
                icon
                size="small"
                color="primary"
                variant="text"
              >
                <v-icon>mdi-pencil</v-icon>
                <v-tooltip activator="parent">{{ $t('common.edit') }}</v-tooltip>
              </v-btn>

              <v-btn
                v-if="canApprove(item)"
                @click="approveExpense(item)"
                icon
                size="small"
                color="success"
                variant="text"
              >
                <v-icon>mdi-check</v-icon>
                <v-tooltip activator="parent">{{ $t('expenses.approve') }}</v-tooltip>
              </v-btn>

              <v-btn
                v-if="canMarkPaid(item)"
                @click="showPaymentDialog(item)"
                icon
                size="small"
                color="purple"
                variant="text"
              >
                <v-icon>mdi-cash-check</v-icon>
                <v-tooltip activator="parent">{{ $t('expenses.mark_as_paid') }}</v-tooltip>
              </v-btn>

              <v-btn
                v-if="canReject(item)"
                @click="rejectExpense(item)"
                icon
                size="small"
                color="error"
                variant="text"
              >
                <v-icon>mdi-close</v-icon>
                <v-tooltip activator="parent">{{ $t('expenses.reject') }}</v-tooltip>
              </v-btn>

              <v-btn
                v-if="canDelete(item)"
                @click="confirmDelete(item)"
                icon
                size="small"
                color="error"
                variant="text"
              >
                <v-icon>mdi-delete</v-icon>
                <v-tooltip activator="parent">{{ $t('common.delete') }}</v-tooltip>
              </v-btn>
            </div>
          </template>
        </v-data-table>
      </v-card>
    </v-container>

    <!-- Payment Dialog -->
    <v-dialog v-model="showPaymentModal" max-width="500px">
      <v-card style="border-radius: 12px;">
        <v-card-title class="text-h5">{{ $t('expenses.mark_as_paid') }}</v-card-title>
        <v-card-text>
          <v-form @submit.prevent="markAsPaid">
            <v-select
              v-model="paymentForm.payment_method"
              :items="paymentMethods"
              :label="$t('expenses.payment_method')"
              variant="outlined"
              required
              class="mb-4"
            ></v-select>
            <v-text-field
              v-model="paymentForm.payment_date"
              type="date"
              :label="$t('expenses.payment_date')"
              variant="outlined"
              required
            ></v-text-field>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="closePaymentDialog" color="grey">{{ $t('common.cancel') }}</v-btn>
          <v-btn @click="markAsPaid" color="success">{{ $t('expenses.mark_as_paid') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Delete Confirmation Dialog -->
    <v-dialog v-model="showDeleteModal" max-width="400px">
      <v-card style="border-radius: 12px;">
        <v-card-title class="text-h5">{{ $t('common.confirm_delete') }}</v-card-title>
        <v-card-text>
          <p>{{ $t('expenses.delete_confirmation') }}</p>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="closeDeleteDialog" color="grey">{{ $t('common.cancel') }}</v-btn>
          <v-btn @click="deleteExpense" color="error">{{ $t('common.delete') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>


  </AppLayout>
</template>

<script>
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

export default {
  name: 'ExpenseIndex',
  components: {
    Head,
    AppLayout
  },
  props: {
    expenses: {
      type: Object,
      required: true
    },
    stats: {
      type: Object,
      default: () => ({})
    },
    categories: {
      type: Array,
      default: () => []
    },
    auth: {
      type: Object,
      required: true
    }
  },
  data() {
    return {
      loading: false,
      showPaymentModal: false,
      showDeleteModal: false,
      selectedExpense: null,
      filters: {
        status: null,
        category: null,
        search: ''
      },
      paymentForm: {
        payment_method: '',
        payment_date: new Date().toISOString().split('T')[0]
      },
      headers: [
        {
          title: this.$t('expenses.table.title'),
          key: 'title',
          sortable: false,
          width: '250px'
        },
        {
          title: this.$t('expenses.table.amount'),
          key: 'amount',
          sortable: true,
          width: '120px'
        },
        {
          title: this.$t('expenses.table.category'),
          key: 'category.name',
          sortable: false,
          width: '150px'
        },
        {
          title: this.$t('expenses.table.status'),
          key: 'status',
          sortable: true,
          width: '120px'
        },
        {
          title: this.$t('expenses.table.money_case'),
          key: 'money_case.name',
          sortable: false,
          width: '150px'
        },
        {
          title: this.$t('expenses.table.created_by'),
          key: 'created_by.name',
          sortable: false,
          width: '150px'
        },
        {
          title: this.$t('expenses.table.date'),
          key: 'expense_date',
          sortable: true,
          width: '120px'
        },
        {
          title: this.$t('common.actions'),
          key: 'actions',
          sortable: false,
          width: '200px'
        }
      ],
      statusOptions: [
        { title: this.$t('expenses.status.pending'), value: 'pending' },
        { title: this.$t('expenses.status.approved'), value: 'approved' },
        { title: this.$t('expenses.status.paid'), value: 'paid' },
        { title: this.$t('expenses.status.rejected'), value: 'rejected' }
      ],
      paymentMethods: [
        { title: this.$t('expenses.payment_methods.cash'), value: 'cash' },
        { title: this.$t('expenses.payment_methods.bank_transfer'), value: 'bank_transfer' },
        { title: this.$t('expenses.payment_methods.check'), value: 'check' },
        { title: this.$t('expenses.payment_methods.card'), value: 'card' }
      ]
    }
  },
  computed: {
    categoryOptions() {
      return this.categories.map(category => ({
        title: category.name,
        value: category.id
      }))
    },
    userRole() {
      return this.auth.user?.role || 'agent'
    }
  },
  methods: {
    formatCurrency(amount, currency = 'USD') {
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency
      }).format(amount)
    },
    formatDate(date) {
      return new Date(date).toLocaleDateString()
    },
    getStatusColor(status) {
      const colors = {
        pending: 'warning',
        approved: 'success',
        paid: 'primary',
        rejected: 'error'
      }
      return colors[status] || 'grey'
    },
    getStatusIcon(status) {
      const icons = {
        pending: 'mdi-clock-outline',
        approved: 'mdi-check-circle',
        paid: 'mdi-cash-check',
        rejected: 'mdi-close-circle'
      }
      return icons[status] || 'mdi-help-circle'
    },
    getAmountColor(amount) {
      if (amount > 1000) return 'error'
      if (amount > 500) return 'warning'
      return 'success'
    },
    canEdit(expense) {
      return expense.status === 'pending' && 
             (this.userRole === 'admin' || 
              this.userRole === 'supervisor' || 
              expense.created_by?.id === this.auth.user?.id)
    },
    canApprove(expense) {
      return expense.status === 'pending' && 
             (this.userRole === 'admin' || this.userRole === 'supervisor')
    },
    canMarkPaid(expense) {
      return expense.status === 'approved' && 
             (this.userRole === 'admin' || this.userRole === 'supervisor')
    },
    canReject(expense) {
      return expense.status === 'pending' && 
             (this.userRole === 'admin' || this.userRole === 'supervisor')
    },
    canDelete(expense) {
      return expense.status === 'pending' && 
             (this.userRole === 'admin' || 
              this.userRole === 'supervisor' || 
              expense.created_by?.id === this.auth.user?.id)
    },
    approveExpense(expense) {
      if (confirm('Are you sure you want to approve this expense?')) {
        router.post(route('expenses.approve', expense.id))
      }
    },
    rejectExpense(expense) {
      if (confirm('Are you sure you want to reject this expense?')) {
        router.post(route('expenses.reject', expense.id))
      }
    },
    showPaymentDialog(expense) {
      this.selectedExpense = expense
      this.showPaymentModal = true
    },
    closePaymentDialog() {
      this.showPaymentModal = false
      this.selectedExpense = null
      this.paymentForm = {
        payment_method: '',
        payment_date: new Date().toISOString().split('T')[0]
      }
    },
    markAsPaid() {
      router.post(route('expenses.mark-as-paid', this.selectedExpense.id), this.paymentForm, {
        onSuccess: () => {
          this.closePaymentDialog()
        }
      })
    },
    confirmDelete(expense) {
      this.selectedExpense = expense
      this.showDeleteModal = true
    },
    closeDeleteDialog() {
      this.showDeleteModal = false
      this.selectedExpense = null
    },
    deleteExpense() {
      router.delete(route('expenses.destroy', this.selectedExpense.id), {
        onSuccess: () => {
          this.closeDeleteDialog()
        }
      })
    },
    applyFilters() {
      // Implement filtering logic
      const params = {}
      if (this.filters.status) params.status = this.filters.status
      if (this.filters.category) params.category = this.filters.category
      if (this.filters.search) params.search = this.filters.search
      
      router.get(route('expenses.index'), params, {
        preserveState: true,
        preserveScroll: true
      })
    },
    clearFilters() {
      this.filters = {
        status: null,
        category: null,
        search: ''
      }
      router.get(route('expenses.index'), {}, {
        preserveState: true,
        preserveScroll: true
      })
    }
  }
}
</script>