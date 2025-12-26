<template>
  <AppLayout>
    <Head :title="`Money Case: ${moneyCase.name}`" />
    
    <template #title>
      <div class="d-flex align-center">
        <v-btn
          icon
          @click="$inertia.visit('/money-cases')"
          class="mr-3"
          style="border-radius: 8px;"
        >
          <v-icon>mdi-arrow-left</v-icon>
        </v-btn>
        <span class="text-h4 font-weight-bold" 
              style="background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
                     -webkit-background-clip: text;
                     -webkit-text-fill-color: transparent;
                     background-clip: text;">
          {{ moneyCase.name }}
        </span>
        <v-chip
          :color="moneyCase.status === 'active' ? 'success' : 'warning'"
          text-color="white"
          class="ml-3"
        >
          {{ moneyCase.status }}
        </v-chip>
      </div>
    </template>
    
    <template #content>
      <v-container fluid>
        <!-- Case Overview -->
        <v-row class="mb-4">
          <v-col cols="12">
            <v-card elevation="2" style="border-radius: 12px;">
              <v-card-title class="pa-6" style="background: #f5f5f5; border-bottom: 1px solid #e0e0e0;">
                <v-icon left color="primary" size="28">mdi-wallet</v-icon>
                <span class="text-h5 font-weight-medium">Case Overview</span>
                <v-spacer></v-spacer>
                <v-btn
                  color="primary"
                  :href="`/money-cases/${moneyCase.id}/edit`"
                  style="border-radius: 8px;"
                >
                  <v-icon left>mdi-pencil</v-icon>
                  Edit
                </v-btn>
              </v-card-title>
              
              <v-card-text class="pa-6">
                <v-row>
                  <v-col cols="12" md="6">
                    <div class="mb-4">
                      <h3 class="text-h6 mb-2">Description</h3>
                      <p class="text-body-1">{{ moneyCase.description || 'No description provided' }}</p>
                    </div>
                    <div>
                      <h3 class="text-h6 mb-2">Created</h3>
                      <p class="text-body-1">{{ formatDate(moneyCase.created_at) }}</p>
                    </div>
                    <div>
                      <h3 class="text-h6 mb-2">Company</h3>
                      <div class="d-flex align-center">
                         <v-icon color="primary" class="mr-2">mdi-domain</v-icon>
                         <p class="text-body-1 mb-0">{{ moneyCase.company?.name || 'N/A' }}</p>
                      </div>
                    </div>
                  </v-col>
                  <v-col cols="12" md="6">
                    <!-- Balance Card -->
                    <v-card 
                      :color="moneyCase.calculated_balance >= 0 ? 'success' : 'error'"
                      dark
                      style="border-radius: 12px;"
                    >
                      <v-card-text class="text-center pa-4">
                        <v-icon size="48" class="mb-2">
                          {{ moneyCase.calculated_balance >= 0 ? 'mdi-trending-up' : 'mdi-trending-down' }}
                        </v-icon>
                        <div class="text-h4 font-weight-bold">{{ formatCurrency(moneyCase.calculated_balance) }}</div>
                        <div class="text-subtitle-1">Current Balance</div>
                      </v-card-text>
                    </v-card>
                  </v-col>
                </v-row>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>

        <!-- Statistics Cards -->
        <v-row class="mb-4">
          <v-col cols="12" sm="6" md="3">
            <v-card style="border-radius: 12px;" elevation="1">
              <v-card-text class="text-center pa-4">
                <v-avatar color="primary" size="56" class="mb-3">
                  <v-icon color="white" size="28">mdi-cash-plus</v-icon>
                </v-avatar>
                <div class="text-h5 font-weight-bold">{{ moneyCase.collections_count || 0 }}</div>
                <div class="text-subtitle-2 text--secondary">Total Collections</div>
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="12" sm="6" md="3">
            <v-card style="border-radius: 12px;" elevation="1">
              <v-card-text class="text-center pa-4">
                <v-avatar color="error" size="56" class="mb-3">
                  <v-icon color="white" size="28">mdi-cash-minus</v-icon>
                </v-avatar>
                <div class="text-h5 font-weight-bold">{{ moneyCase.expenses_count || 0 }}</div>
                <div class="text-subtitle-2 text--secondary">Total Expenses</div>
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="12" sm="6" md="3">
            <v-card style="border-radius: 12px;" elevation="1">
              <v-card-text class="text-center pa-4">
                <v-avatar color="success" size="56" class="mb-3">
                  <v-icon color="white" size="28">mdi-arrow-up-bold</v-icon>
                </v-avatar>
                <div class="text-h6 font-weight-bold">{{ formatCurrency(moneyCase.total_collections || 0) }}</div>
                <div class="text-subtitle-2 text--secondary">Money In</div>
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="12" sm="6" md="3">
            <v-card style="border-radius: 12px;" elevation="1">
              <v-card-text class="text-center pa-4">
                <v-avatar color="warning" size="56" class="mb-3">
                  <v-icon color="white" size="28">mdi-arrow-down-bold</v-icon>
                </v-avatar>
                <div class="text-h6 font-weight-bold">{{ formatCurrency(moneyCase.total_expenses || 0) }}</div>
                <div class="text-subtitle-2 text--secondary">Money Out</div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>

        <!-- Tabs for Collections and Expenses -->
        <v-card elevation="1" style="border-radius: 8px;">
          <v-tabs v-model="activeTab" color="primary">
            <v-tab value="collections">
              <v-icon left>mdi-cash-plus</v-icon>
              Collections ({{ collections.length }})
            </v-tab>
            <v-tab value="expenses">
              <v-icon left>mdi-cash-minus</v-icon>
              Expenses ({{ expenses.length }})
            </v-tab>
          </v-tabs>

          <v-tabs-window v-model="activeTab">
            <!-- Collections Tab -->
            <v-tabs-window-item value="collections">
              <v-card-text>
                <v-data-table
                  :headers="collectionsHeaders"
                  :items="collections"
                  class="simple-table"
                  item-key="id"
                  :items-per-page="10"
                >
                  <template v-slot:[`item.parcel.tracking_number`]="{ item }">
                    <v-chip small color="info" text-color="white">
                      {{ item.parcel?.tracking_number || 'N/A' }}
                    </v-chip>
                  </template>

                  <template v-slot:[`item.amount`]="{ item }">
                    <v-chip small color="success" text-color="white">
                      {{ formatCurrency(item.amount) }}
                    </v-chip>
                  </template>

                  <template v-slot:[`item.created_at`]="{ item }">
                    <span class="text-body-2">{{ formatDate(item.created_at) }}</span>
                  </template>
                </v-data-table>
              </v-card-text>
            </v-tabs-window-item>

            <!-- Expenses Tab -->
            <v-tabs-window-item value="expenses">
              <v-card-text>
                <v-data-table
                  :headers="expensesHeaders"
                  :items="expenses"
                  class="simple-table"
                  item-key="id"
                  :items-per-page="10"
                >
                  <template v-slot:[`item.amount`]="{ item }">
                    <v-chip small color="error" text-color="white">
                      {{ formatCurrency(item.amount) }}
                    </v-chip>
                  </template>

                  <template v-slot:[`item.status`]="{ item }">
                    <v-chip
                      small
                      :color="getExpenseStatusColor(item.status)"
                      text-color="white"
                    >
                      {{ item.status }}
                    </v-chip>
                  </template>

                  <template v-slot:[`item.created_at`]="{ item }">
                    <span class="text-body-2">{{ formatDate(item.created_at) }}</span>
                  </template>
                </v-data-table>
              </v-card-text>
            </v-tabs-window-item>
          </v-tabs-window>
        </v-card>
      </v-container>
    </template>
  </AppLayout>
</template>

<script>
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

export default {
  name: 'MoneyCaseShow',
  components: {
    Head,
    AppLayout
  },
  props: {
    moneyCase: {
      type: Object,
      required: true
    },
    collections: {
      type: Array,
      default: () => []
    },
    expenses: {
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      activeTab: 'collections',
      collectionsHeaders: [
        {
          title: 'Tracking Number',
          key: 'parcel.tracking_number',
          sortable: false,
          width: '200px'
        },
        {
          title: 'Client Name',
          key: 'parcel.client_name',
          sortable: false,
          width: '200px'
        },
        {
          title: 'Amount',
          key: 'amount',
          sortable: true,
          width: '150px'
        },
        {
          title: 'Created At',
          key: 'created_at',
          sortable: true,
          width: '180px'
        }
      ],
      expensesHeaders: [
        {
          title: 'Description',
          key: 'description',
          sortable: false,
          width: '300px'
        },
        {
          title: 'Category',
          key: 'category',
          sortable: true,
          width: '150px'
        },
        {
          title: 'Amount',
          key: 'amount',
          sortable: true,
          width: '150px'
        },
        {
          title: 'Status',
          key: 'status',
          sortable: true,
          width: '120px'
        },
        {
          title: 'Created At',
          key: 'created_at',
          sortable: true,
          width: '180px'
        }
      ]
    }
  },
  methods: {
    formatDate(date) {
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    },
    formatCurrency(amount) {
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'DZD',
        minimumFractionDigits: 2
      }).format(amount || 0)
    },
    getExpenseStatusColor(status) {
      const colors = {
        'pending': 'warning',
        'approved': 'success',
        'rejected': 'error',
        'paid': 'info'
      }
      return colors[status] || 'grey'
    }
  }
}
</script>

<style scoped>
.simple-table >>> .v-data-table thead th {
  background: #fafafa !important;
  color: #333 !important;
  font-weight: 500 !important;
  border-bottom: 1px solid #e0e0e0 !important;
}

.simple-table >>> .v-data-table tbody tr:hover {
  background: #f5f5f5 !important;
}

.simple-table >>> .v-data-table tbody td {
  color: #333 !important;
  border-bottom: 1px solid #f0f0f0 !important;
}
</style>