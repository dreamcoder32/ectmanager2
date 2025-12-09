<template>
  <AppLayout>
    <Head title="Create Expense" />
    
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Create New Expense
        </h2>
        <v-btn
          :to="route('expenses.index')"
          variant="outlined"
          color="grey"
          prepend-icon="mdi-arrow-left"
        >
          Back to Expenses
        </v-btn>
      </div>
    </template>

    <v-container fluid class="py-8">
      <v-row justify="center">
        <v-col cols="12" md="8" lg="6">
          <v-card elevation="2" style="border-radius: 12px;">
            <v-card-title class="text-h5 pa-6 bg-primary text-white">
              <v-icon left class="mr-2">mdi-plus-circle</v-icon>
              Create New Expense
            </v-card-title>
            
            <v-card-text class="pa-6">
              <v-form @submit.prevent="submit">
                <v-row>
                  <!-- Title -->
                  <v-col cols="12">
                    <v-text-field
                      v-model="form.title"
                      label="Title"
                      variant="outlined"
                      required
                      :error-messages="errors.title"
                      prepend-inner-icon="mdi-text"
                      placeholder="Enter expense title"
                    ></v-text-field>
                  </v-col>

                  <!-- Amount and Currency -->
                  <v-col cols="12">
                    <v-text-field
                      v-model="form.amount"
                      label="Amount (DZD)"
                      type="number"
                      step="0.01"
                      min="0.01"
                      variant="outlined"
                      required
                      :error-messages="errors.amount"
                      prepend-inner-icon="mdi-currency-usd"
                      placeholder="0.00"
                      suffix="DZD"
                    ></v-text-field>
                  </v-col>

                  <!-- Category -->
                  <v-col cols="12">
                    <v-select
                      v-model="form.category_id"
                      :items="categoryOptions"
                      label="Category"
                      variant="outlined"
                      required
                      :error-messages="errors.category_id"
                      prepend-inner-icon="mdi-tag"
                      placeholder="Select category"
                    ></v-select>
                  </v-col>

                  <!-- Description -->
                  <v-col cols="12">
                    <v-textarea
                      v-model="form.description"
                      label="Description"
                      variant="outlined"
                      rows="3"
                      :error-messages="errors.description"
                      prepend-inner-icon="mdi-text-long"
                      placeholder="Enter expense description..."
                    ></v-textarea>
                  </v-col>

                  <!-- Expense Date -->
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.expense_date"
                      label="Expense Date"
                      type="date"
                      variant="outlined"
                      required
                      :error-messages="errors.expense_date"
                      prepend-inner-icon="mdi-calendar"
                    ></v-text-field>
                  </v-col>

                  <!-- Payment Method -->
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="form.payment_method"
                      :items="paymentMethodOptions"
                      label="Payment Method"
                      variant="outlined"
                      :error-messages="errors.payment_method"
                      prepend-inner-icon="mdi-credit-card"
                      placeholder="Select payment method"
                    ></v-select>
                  </v-col>

                  <!-- Money Case Selection -->
                  <v-col cols="12">
                    <v-select
                      v-model="form.case_id"
                      :items="moneyCaseOptions"
                      label="Assign to Money Case"
                      variant="outlined"
                      :error-messages="errors.case_id"
                      prepend-inner-icon="mdi-briefcase"
                      placeholder="No case assignment"
                      clearable
                    ></v-select>
                    <v-alert
                      type="info"
                      variant="tonal"
                      class="mt-2"
                      density="compact"
                    >
                      Optional: Select a money case to deduct this expense from its balance.
                    </v-alert>
                  </v-col>

                  <!-- Recolte Selection -->
                  <v-col cols="12" v-if="recoltes.length > 0">
                    <v-select
                      v-model="form.recolte_id"
                      :items="recolteOptions"
                      label="Link to Recolte"
                      variant="outlined"
                      :error-messages="errors.recolte_id"
                      prepend-inner-icon="mdi-barcode-scan"
                      placeholder="No recolte linked"
                      clearable
                    ></v-select>
                    <v-alert
                      type="info"
                      variant="tonal"
                      class="mt-2"
                      density="compact"
                    >
                      Optional: Link this expense to a specific Recolte (e.g. deduction from collected amount).
                    </v-alert>
                  </v-col>
                </v-row>
              </v-form>
            </v-card-text>

            <v-card-actions class="pa-6 pt-0">
              <v-spacer></v-spacer>
              <v-btn
                :to="route('expenses.index')"
                variant="outlined"
                color="grey"
                class="mr-3"
              >
                Cancel
              </v-btn>
              <v-btn
                @click="submit"
                :loading="form.processing"
                color="primary"
                variant="elevated"
                prepend-icon="mdi-check"
              >
                Create Expense
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </AppLayout>
</template>

<script>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

export default {
  name: 'ExpenseCreate',
  components: {
    Head,
    AppLayout
  },
  props: {
    activeCases: {
      type: Array,
      default: () => []
    },
    categories: {
      type: Array,
      default: () => []
    },
    errors: {
      type: Object,
      default: () => ({})
    },
    recoltes: {
      type: Array,
      default: () => []
    }
  },
  setup() {
    const form = useForm({
      title: '',
      amount: '',
      currency: 'DZD',
      category_id: '',
      description: '',
      expense_date: new Date().toISOString().split('T')[0],
      payment_method: '',
      case_id: null,
      recolte_id: null
    })

    return { form }
  },
  computed: {
    categoryOptions() {
      return this.categories?.map(category => ({
        title: category.name,
        value: category.id,
        subtitle: category.description
      })) || []
    },
    moneyCaseOptions() {
      return this.activeCases?.map(moneyCase => ({
        title: `${moneyCase.name} (${this.formatCurrency(moneyCase.balance)})`,
        value: moneyCase.id,
        subtitle: `Balance: ${this.formatCurrency(moneyCase.balance)}`
      })) || []
    },
    recolteOptions() {
      return this.recoltes?.map(recolte => ({
        title: `RCT-${recolte.code} (${recolte.creator}) - ${this.formatCurrency(recolte.balance)}`,
        value: recolte.id,
        subtitle: `Created by ${recolte.creator} | Net Total: ${this.formatCurrency(recolte.balance)}`
      })) || []
    },
    paymentMethodOptions() {
      return [
        { title: 'Cash', value: 'cash' },
        { title: 'Credit Card', value: 'credit_card' },
        { title: 'Debit Card', value: 'debit_card' },
        { title: 'Bank Transfer', value: 'bank_transfer' },
        { title: 'Check', value: 'check' },
        { title: 'Digital Wallet', value: 'digital_wallet' },
        { title: 'Other', value: 'other' }
      ]
    }
  },
  watch: {
    'form.case_id'(newVal) {
      if (newVal) {
        this.form.recolte_id = null
      }
    },
    'form.recolte_id'(newVal) {
      if (newVal) {
        this.form.case_id = null
      }
    }
  },
  methods: {
    submit() {
      // Client-side balance check
      if (this.form.case_id) {
        const selectedCase = this.activeCases.find(c => c.id === this.form.case_id)
        if (selectedCase && parseFloat(this.form.amount) > parseFloat(selectedCase.balance)) {
          this.form.setError('amount', `Amount exceeds Money Case balance (${this.formatCurrency(selectedCase.balance)})`)
          return
        }
      }

      if (this.form.recolte_id) {
        const selectedRecolte = this.recoltes.find(r => r.id === this.form.recolte_id)
        if (selectedRecolte && parseFloat(this.form.amount) > parseFloat(selectedRecolte.balance)) {
          this.form.setError('amount', `Amount exceeds Recolte net total (${this.formatCurrency(selectedRecolte.balance)})`)
          return
        }
      }

      this.form.post(route('expenses.store'), {
        onSuccess: () => {
          // Form will redirect on success
        }
      })
    },
    formatCurrency(amount, currency = 'DZD') {
      return new Intl.NumberFormat('fr-DZ', {
        style: 'currency',
        currency: currency
      }).format(amount)
    }
  }
}
</script>