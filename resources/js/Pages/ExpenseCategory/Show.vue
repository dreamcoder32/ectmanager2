<template>
  <AppLayout>
    <Head :title="`Expense Category: ${category.name}`" />
    
    <template #title>
      <div class="d-flex justify-space-between align-center">
        <span class="text-h4 font-weight-bold">
          Expense Category Details
        </span>
        <div class="d-flex gap-2">
          <v-btn
            :to="{ name: 'expense-categories.edit', params: { expense_category: category.id } }"
            color="primary"
            variant="elevated"
            prepend-icon="mdi-pencil"
          >
            Edit
          </v-btn>
          <v-btn
            :to="{ name: 'expense-categories.index' }"
            variant="outlined"
            prepend-icon="mdi-arrow-left"
          >
            Back to List
          </v-btn>
        </div>
      </div>
    </template>
    
    <template #content>
      <v-container fluid>
        <v-row>
          <!-- Category Information Card -->
          <v-col cols="12" md="8">
            <v-card elevation="2" class="mb-4">
              <v-card-title class="text-h5 mb-4">
                <v-icon left color="primary">mdi-tag</v-icon>
                {{ category.name }}
                <v-spacer></v-spacer>
                <v-chip
                  :color="category.is_active ? 'success' : 'error'"
                  :prepend-icon="category.is_active ? 'mdi-check-circle' : 'mdi-close-circle'"
                  variant="elevated"
                >
                  {{ category.is_active ? 'Active' : 'Inactive' }}
                </v-chip>
              </v-card-title>
              
              <v-card-text>
                <v-row>
                  <v-col cols="12" v-if="category.description">
                    <div class="mb-4">
                      <h3 class="text-h6 mb-2">
                        <v-icon left>mdi-text</v-icon>
                        Description
                      </h3>
                      <p class="text-body-1">{{ category.description }}</p>
                    </div>
                  </v-col>
                  
                  <v-col cols="12" md="6">
                    <div class="mb-4">
                      <h3 class="text-h6 mb-2">
                        <v-icon left>mdi-calendar-plus</v-icon>
                        Created
                      </h3>
                      <p class="text-body-1">{{ formatDate(category.created_at) }}</p>
                    </div>
                  </v-col>
                  
                  <v-col cols="12" md="6">
                    <div class="mb-4">
                      <h3 class="text-h6 mb-2">
                        <v-icon left>mdi-calendar-edit</v-icon>
                        Last Updated
                      </h3>
                      <p class="text-body-1">{{ formatDate(category.updated_at) }}</p>
                    </div>
                  </v-col>
                </v-row>
              </v-card-text>
            </v-card>
          </v-col>
          
          <!-- Statistics Card -->
          <v-col cols="12" md="4">
            <v-card elevation="2" class="mb-4">
              <v-card-title class="text-h6">
                <v-icon left color="primary">mdi-chart-box</v-icon>
                Statistics
              </v-card-title>
              
              <v-card-text>
                <div class="text-center">
                  <div class="mb-4">
                    <div class="text-h3 font-weight-bold text-primary">
                      {{ category.expenses_count || 0 }}
                    </div>
                    <div class="text-body-2 text-medium-emphasis">
                      Total Expenses
                    </div>
                  </div>
                  
                  <v-divider class="my-4"></v-divider>
                  
                  <div v-if="category.total_amount !== undefined" class="mb-4">
                    <div class="text-h4 font-weight-bold text-success">
                      ${{ formatCurrency(category.total_amount || 0) }}
                    </div>
                    <div class="text-body-2 text-medium-emphasis">
                      Total Amount
                    </div>
                  </div>
                </div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
        
        <!-- Recent Expenses -->
        <v-row v-if="category.recent_expenses && category.recent_expenses.length > 0">
          <v-col cols="12">
            <v-card elevation="2">
              <v-card-title class="text-h6">
                <v-icon left color="primary">mdi-receipt</v-icon>
                Recent Expenses
              </v-card-title>
              
              <v-card-text>
                <v-data-table
                  :headers="expenseHeaders"
                  :items="category.recent_expenses"
                  :items-per-page="5"
                  class="elevation-0"
                >
                  <template v-slot:[`item.amount`]="{ item }">
                    <span class="font-weight-bold text-success">
                      ${{ formatCurrency(item.amount) }}
                    </span>
                  </template>
                  
                  <template v-slot:[`item.status`]="{ item }">
                    <v-chip
                      :color="getStatusColor(item.status)"
                      size="small"
                      variant="elevated"
                    >
                      {{ item.status }}
                    </v-chip>
                  </template>
                  
                  <template v-slot:[`item.date`]="{ item }">
                    {{ formatDate(item.date) }}
                  </template>
                  
                  <template v-slot:[`item.actions`]="{ item }">
                    <v-btn
                      :to="{ name: 'expenses.show', params: { expense: item.id } }"
                      size="small"
                      variant="text"
                      icon="mdi-eye"
                    ></v-btn>
                  </template>
                </v-data-table>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </template>
  </AppLayout>
</template>

<script>
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

export default {
  name: 'ExpenseCategoryShow',
  components: {
    Head,
    AppLayout
  },
  props: {
    category: {
      type: Object,
      required: true
    }
  },
  data() {
    return {
      expenseHeaders: [
        { title: 'Description', key: 'description', sortable: false },
        { title: 'Amount', key: 'amount', sortable: true },
        { title: 'Status', key: 'status', sortable: true },
        { title: 'Date', key: 'date', sortable: true },
        { title: 'Actions', key: 'actions', sortable: false, width: '80px' }
      ]
    }
  },
  methods: {
    formatDate(date) {
      if (!date) return 'N/A'
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
      const colors = {
        'pending': 'warning',
        'approved': 'success',
        'paid': 'primary',
        'rejected': 'error'
      }
      return colors[status?.toLowerCase()] || 'default'
    }
  }
}
</script>

<style scoped>
.v-card {
  border-radius: 12px;
}

.v-card-title {
  background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
  color: white;
  margin: -16px -16px 16px -16px;
  padding: 20px 24px;
  border-radius: 12px 12px 0 0;
}

.text-h3 {
  line-height: 1.2;
}

.gap-2 {
  gap: 8px;
}
</style>