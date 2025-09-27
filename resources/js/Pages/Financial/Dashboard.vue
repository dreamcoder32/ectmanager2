<template>
  <v-app>
    <!-- Navigation Drawer -->
    <v-navigation-drawer
      v-model="drawer"
      app
      permanent
      width="280"
      color="primary"
      dark
    >
      <v-list-item class="px-2">
        <v-list-item-avatar>
          <v-icon size="40">mdi-truck-delivery</v-icon>
        </v-list-item-avatar>
        <v-list-item-content>
          <v-list-item-title class="text-h6">
            {{ $t('app.name') }}
          </v-list-item-title>
        </v-list-item-content>
      </v-list-item>

      <v-divider></v-divider>

      <v-list dense nav>
        <v-list-item
          v-for="item in menuItems"
          :key="item.title"
          :to="item.to"
          link
        >
          <v-list-item-icon>
            <v-icon>{{ item.icon }}</v-icon>
          </v-list-item-icon>
          <v-list-item-content>
            <v-list-item-title>{{ $t(item.title) }}</v-list-item-title>
          </v-list-item-content>
        </v-list-item>
      </v-list>
    </v-navigation-drawer>

    <!-- App Bar -->
    <v-app-bar app color="white" elevation="1">
      <v-app-bar-nav-icon @click="drawer = !drawer"></v-app-bar-nav-icon>
      
      <v-toolbar-title>{{ $t('financial.dashboard.title') }}</v-toolbar-title>
      
      <v-spacer></v-spacer>
      
      <!-- Period Selector -->
      <v-select
        v-model="selectedPeriod"
        :items="periodOptions"
        item-title="text"
        item-value="value"
        dense
        outlined
        hide-details
        class="mr-4"
        style="max-width: 150px;"
        @update:model-value="loadDashboardData"
      ></v-select>
      
      <!-- Refresh Button -->
      <v-btn icon @click="loadDashboardData" :loading="loading">
        <v-icon>mdi-refresh</v-icon>
      </v-btn>
      
      <!-- User Menu -->
      <v-menu offset-y>
        <template v-slot:activator="{ props }">
          <v-btn icon v-bind="props">
            <v-avatar size="32">
              <v-icon>mdi-account</v-icon>
            </v-avatar>
          </v-btn>
        </template>
        <v-list>
          <v-list-item>
            <v-list-item-content>
              <v-list-item-title>{{ $page.props.auth.user.display_name }}</v-list-item-title>
              <v-list-item-subtitle>{{ $page.props.auth.user.email }}</v-list-item-subtitle>
            </v-list-item-content>
          </v-list-item>
          <v-divider></v-divider>
          <v-list-item @click="logout">
            <v-list-item-icon>
              <v-icon>mdi-logout</v-icon>
            </v-list-item-icon>
            <v-list-item-content>
              <v-list-item-title>{{ $t('auth.logout') }}</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
        </v-list>
      </v-menu>
    </v-app-bar>

    <!-- Main Content -->
    <v-main>
      <v-container fluid class="pa-6">
        <!-- Loading State -->
        <v-row v-if="loading" justify="center">
          <v-col cols="12" class="text-center">
            <v-progress-circular indeterminate size="64" color="primary"></v-progress-circular>
            <div class="mt-4 text-h6">{{ $t('common.loading') }}</div>
          </v-col>
        </v-row>

        <!-- Dashboard Content -->
        <div v-else>
          <!-- Key Metrics Cards -->
          <v-row>
            <v-col cols="12" sm="6" md="3">
              <v-card elevation="2" class="text-center financial-card revenue-card">
                <v-card-text>
                  <v-icon size="48" color="success" class="mb-2">mdi-cash-multiple</v-icon>
                  <div class="text-h4 font-weight-bold">{{ formatCurrency(dashboardData.revenue?.total_revenue || 0) }}</div>
                  <div class="text-subtitle-1 text-grey">{{ $t('financial.dashboard.total_revenue') }}</div>
                  <div class="text-caption mt-1">
                    {{ dashboardData.revenue?.total_collections || 0 }} {{ $t('financial.dashboard.collections') }}
                  </div>
                </v-card-text>
              </v-card>
            </v-col>

            <v-col cols="12" sm="6" md="3">
              <v-card elevation="2" class="text-center financial-card expense-card">
                <v-card-text>
                  <v-icon size="48" color="error" class="mb-2">mdi-cash-minus</v-icon>
                  <div class="text-h4 font-weight-bold">{{ formatCurrency(dashboardData.expenses?.total_expenses || 0) }}</div>
                  <div class="text-subtitle-1 text-grey">{{ $t('financial.dashboard.total_expenses') }}</div>
                  <div class="text-caption mt-1">
                    {{ $t('financial.dashboard.salary') }}: {{ formatCurrency(dashboardData.expenses?.salary_expenses || 0) }}
                  </div>
                </v-card-text>
              </v-card>
            </v-col>

            <v-col cols="12" sm="6" md="3">
              <v-card elevation="2" class="text-center financial-card profit-card">
                <v-card-text>
                  <v-icon size="48" :color="getProfitColor(dashboardData.profitability?.net_profit || 0)" class="mb-2">
                    {{ dashboardData.profitability?.net_profit >= 0 ? 'mdi-trending-up' : 'mdi-trending-down' }}
                  </v-icon>
                  <div class="text-h4 font-weight-bold" :class="getProfitColor(dashboardData.profitability?.net_profit || 0) + '--text'">
                    {{ formatCurrency(dashboardData.profitability?.net_profit || 0) }}
                  </div>
                  <div class="text-subtitle-1 text-grey">{{ $t('financial.dashboard.net_profit') }}</div>
                  <div class="text-caption mt-1">
                    {{ $t('financial.dashboard.margin') }}: {{ (dashboardData.profitability?.profit_margin || 0).toFixed(1) }}%
                  </div>
                </v-card-text>
              </v-card>
            </v-col>

            <v-col cols="12" sm="6" md="3">
              <v-card elevation="2" class="text-center financial-card completion-card">
                <v-card-text>
                  <v-icon size="48" color="info" class="mb-2">mdi-chart-pie</v-icon>
                  <div class="text-h4 font-weight-bold">{{ (dashboardData.collections?.average_collection_amount || 0).toFixed(0) }} DA</div>
                  <div class="text-subtitle-1 text-grey">{{ $t('financial.dashboard.average_collection') }}</div>
                  <div class="text-caption mt-1">
                    {{ dashboardData.collections?.total_collections || 0 }} {{ $t('financial.dashboard.total_collections') }}
                  </div>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>

          <!-- Charts Row -->
          <v-row class="mt-6">
            <!-- Revenue vs Expenses Chart -->
            <v-col cols="12" md="8">
              <v-card elevation="2">
                <v-card-title>{{ $t('financial.dashboard.revenue_vs_expenses') }}</v-card-title>
                <v-card-text>
                  <canvas ref="revenueExpenseChart" height="300"></canvas>
                </v-card-text>
              </v-card>
            </v-col>

            <!-- Expense Breakdown -->
            <v-col cols="12" md="4">
              <v-card elevation="2">
                <v-card-title>{{ $t('financial.dashboard.expense_breakdown') }}</v-card-title>
                <v-card-text>
                  <canvas ref="expenseBreakdownChart" height="300"></canvas>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>

          <!-- Detailed Metrics -->
          <v-row class="mt-6">
            <!-- Payment Summary -->
            <v-col cols="12" md="6">
              <v-card elevation="2">
                <v-card-title>{{ $t('financial.dashboard.payment_summary') }}</v-card-title>
                <v-card-text>
                  <v-row>
                    <v-col cols="6">
                      <div class="text-center">
                        <div class="text-h6 font-weight-bold text-primary">
                          {{ formatCurrency(dashboardData.payments?.salary_payments?.total || 0) }}
                        </div>
                        <div class="text-caption">{{ $t('financial.dashboard.salary_payments') }}</div>
                        <div class="text-caption text-warning">
                          {{ $t('financial.dashboard.pending') }}: {{ formatCurrency(dashboardData.payments?.salary_payments?.pending || 0) }}
                        </div>
                      </div>
                    </v-col>
                    <v-col cols="6">
                      <div class="text-center">
                        <div class="text-h6 font-weight-bold text-secondary">
                          {{ formatCurrency(dashboardData.payments?.commission_payments?.total || 0) }}
                        </div>
                        <div class="text-caption">{{ $t('financial.dashboard.commission_payments') }}</div>
                        <div class="text-caption text-warning">
                          {{ $t('financial.dashboard.pending') }}: {{ formatCurrency(dashboardData.payments?.commission_payments?.pending || 0) }}
                        </div>
                      </div>
                    </v-col>
                  </v-row>
                  <v-divider class="my-3"></v-divider>
                  <div class="text-center">
                    <div class="text-h6 font-weight-bold">
                      {{ formatCurrency(dashboardData.payments?.total_staff_costs || 0) }}
                    </div>
                    <div class="text-caption">{{ $t('financial.dashboard.total_staff_costs') }}</div>
                  </div>
                </v-card-text>
              </v-card>
            </v-col>

            <!-- Collection Status -->
            <v-col cols="12" md="6">
              <v-card elevation="2">
                <v-card-title>{{ $t('financial.dashboard.collection_status') }}</v-card-title>
                <v-card-text>
                  <v-row>
                    <v-col cols="4" v-for="status in collectionStatuses" :key="status.key" class="text-center">
                      <v-icon :color="status.color" size="32" class="mb-2">{{ status.icon }}</v-icon>
                      <div class="text-h6 font-weight-bold">{{ dashboardData.collections?.[status.key] || 0 }}</div>
                      <div class="text-caption">{{ $t(status.label) }}</div>
                    </v-col>
                  </v-row>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>

          <!-- Quick Actions -->
          <v-row class="mt-6">
            <v-col cols="12">
              <v-card elevation="2">
                <v-card-title>{{ $t('financial.dashboard.quick_actions') }}</v-card-title>
                <v-card-text>
                  <v-row>
                    <v-col cols="12" sm="6" md="3" v-for="action in quickActions" :key="action.title">
                      <v-btn
                        :color="action.color"
                        block
                        large
                        :to="action.to"
                        class="mb-2"
                      >
                        <v-icon left>{{ action.icon }}</v-icon>
                        {{ $t(action.title) }}
                      </v-btn>
                    </v-col>
                  </v-row>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>
        </div>
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import Chart from 'chart.js/auto'

const { t, locale } = useI18n()
const { props: pageProps } = usePage()

const drawer = ref(true)
const loading = ref(false)
const dashboardData = ref({})
const selectedPeriod = ref('month')

// Chart references
const revenueExpenseChart = ref(null)
const expenseBreakdownChart = ref(null)
let revenueExpenseChartInstance = null
let expenseBreakdownChartInstance = null

const periodOptions = [
  { text: t('financial.dashboard.today'), value: 'day' },
  { text: t('financial.dashboard.this_week'), value: 'week' },
  { text: t('financial.dashboard.this_month'), value: 'month' },
  { text: t('financial.dashboard.this_year'), value: 'year' },
]

const menuItems = computed(() => {
  const items = [
    { title: 'dashboard.title', icon: 'mdi-view-dashboard', to: '/dashboard' },
    { title: 'financial.dashboard.title', icon: 'mdi-chart-line', to: '/financial/dashboard' },
    { title: 'parcels.title', icon: 'mdi-package-variant', to: '/parcels' },
    { title: 'collections.title', icon: 'mdi-truck', to: '/collections' },
    { title: 'drivers.title', icon: 'mdi-account-group', to: '/drivers' },
    { title: 'companies.title', icon: 'mdi-domain', to: '/companies' },
  ]
  
  if (pageProps.auth.user.role === 'admin') {
    items.push(
      { title: 'users.title', icon: 'mdi-account-multiple', to: '/users' },
      { title: 'settings.title', icon: 'mdi-cog', to: '/settings' }
    )
  }
  
  return items
})

const collectionStatuses = [
  { key: 'home_delivery_collections', label: 'financial.dashboard.home_delivery', icon: 'mdi-home', color: 'primary' },
  { key: 'stop_desk_collections', label: 'financial.dashboard.stop_desk', icon: 'mdi-desk', color: 'secondary' },
  { key: 'total_collections', label: 'financial.dashboard.total', icon: 'mdi-package-variant', color: 'info' },
]

const quickActions = [
  { title: 'financial.dashboard.generate_salary_payments', icon: 'mdi-account-cash', color: 'primary', to: '/salary-payments' },
  { title: 'financial.dashboard.calculate_commissions', icon: 'mdi-percent', color: 'secondary', to: '/commission-payments' },
  { title: 'financial.dashboard.view_expenses', icon: 'mdi-receipt', color: 'info', to: '/expenses' },
  { title: 'financial.dashboard.financial_reports', icon: 'mdi-file-chart', color: 'success', to: '/reports/financial' },
]

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-DZ', {
    style: 'currency',
    currency: 'DZD',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount)
}

const getProfitColor = (profit) => {
  return profit >= 0 ? 'success' : 'error'
}

const loadDashboardData = async () => {
  loading.value = true
  try {
    const response = await fetch(`/api/financial-dashboard?period=${selectedPeriod.value}`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    })
    
    if (response.ok) {
      const result = await response.json()
      dashboardData.value = result.data
      
      // Don't update charts here - let onMounted handle it
      // await nextTick()
      // updateCharts()
    } else {
      console.error('Failed to load dashboard data')
    }
  } catch (error) {
    console.error('Error loading dashboard data:', error)
  } finally {
    loading.value = false
  }
}

const updateCharts = () => {
  // Add safety check to ensure DOM is ready and refs are available
  if (!revenueExpenseChart.value || !expenseBreakdownChart.value) {
    console.log('Chart refs not ready, skipping chart update')
    return
  }
  
  // Use nextTick to ensure DOM is fully rendered
  nextTick(() => {
    updateRevenueExpenseChart()
    updateExpenseBreakdownChart()
  })
}

const updateRevenueExpenseChart = () => {
  // Add comprehensive null checks
  if (!revenueExpenseChart.value || !dashboardData.value.trends) {
    console.log('Revenue expense chart ref or trends data not available')
    return
  }
  
  try {
    // Ensure the canvas element exists and has getContext method
    if (!revenueExpenseChart.value.getContext) {
      console.error('Canvas element does not have getContext method')
      return
    }
    
    const ctx = revenueExpenseChart.value.getContext('2d')
    if (!ctx) {
      console.error('Failed to get 2D context from canvas')
      return
    }
    
    if (revenueExpenseChartInstance) {
      revenueExpenseChartInstance.destroy()
    }
    
    const revenueTrend = dashboardData.value.trends.revenue || []
    const expenseTrend = dashboardData.value.trends.expenses || []
    
    // Merge and sort data by period
    const allPeriods = [...new Set([
      ...revenueTrend.map(item => item.period),
      ...expenseTrend.map(item => item.period)
    ])].sort()
    
    const revenueData = allPeriods.map(period => {
      const item = revenueTrend.find(r => r.period === period)
      return item ? item.revenue : 0
    })
    
    const expenseData = allPeriods.map(period => {
      const item = expenseTrend.find(e => e.period === period)
      return item ? item.expenses : 0
    })
    
    revenueExpenseChartInstance = new Chart(ctx, {
      type: 'line',
      data: {
        labels: allPeriods.map(period => new Date(period).toLocaleDateString()),
        datasets: [
          {
            label: t('financial.dashboard.revenue'),
            data: revenueData,
            borderColor: '#4CAF50',
            backgroundColor: 'rgba(76, 175, 80, 0.1)',
            tension: 0.4,
            fill: true,
          },
          {
            label: t('financial.dashboard.expenses'),
            data: expenseData,
            borderColor: '#F44336',
            backgroundColor: 'rgba(244, 67, 54, 0.1)',
            tension: 0.4,
            fill: true,
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'top',
          },
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function(value) {
                return formatCurrency(value)
              }
            }
          }
        }
      }
    })
  } catch (error) {
    console.error('Error creating revenue expense chart:', error)
  }
}

const updateExpenseBreakdownChart = () => {
  // Add comprehensive null checks
  if (!expenseBreakdownChart.value || !dashboardData.value.expenses?.expenses_by_category) {
    console.log('Expense breakdown chart ref or expenses data not available')
    return
  }
  
  try {
    // Ensure the canvas element exists and has getContext method
    if (!expenseBreakdownChart.value.getContext) {
      console.error('Canvas element does not have getContext method')
      return
    }
    
    const ctx = expenseBreakdownChart.value.getContext('2d')
    if (!ctx) {
      console.error('Failed to get 2D context from canvas')
      return
    }
    
    if (expenseBreakdownChartInstance) {
      expenseBreakdownChartInstance.destroy()
    }
    
    const expensesByCategory = dashboardData.value.expenses.expenses_by_category
    const labels = Object.keys(expensesByCategory)
    const data = Object.values(expensesByCategory)
    
    const colors = [
      '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', 
      '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
    ]
    
    expenseBreakdownChartInstance = new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: labels.map(label => t(`financial.categories.${label}`)),
        datasets: [{
          data: data,
          backgroundColor: colors.slice(0, labels.length),
          borderWidth: 2,
          borderColor: '#fff'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                return context.label + ': ' + formatCurrency(context.parsed)
              }
            }
          }
        }
      }
    })
  } catch (error) {
    console.error('Error creating expense breakdown chart:', error)
  }
}

const logout = () => {
  router.post('/logout')
}

onMounted(() => {
  // Load data first, then update charts after DOM is ready
  loadDashboardData().then(() => {
    // Ensure charts are updated after data is loaded and DOM is ready
    nextTick(() => {
      updateCharts()
    })
  })
})
</script>

<style scoped>
.financial-card {
  transition: transform 0.2s ease-in-out;
}

.financial-card:hover {
  transform: translateY(-2px);
}

.revenue-card {
  border-left: 4px solid #4CAF50;
}

.expense-card {
  border-left: 4px solid #F44336;
}

.profit-card {
  border-left: 4px solid #2196F3;
}

.completion-card {
  border-left: 4px solid #FF9800;
}
</style>