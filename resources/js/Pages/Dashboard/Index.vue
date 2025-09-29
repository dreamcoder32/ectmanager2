<template>
  <AppLayout :title="$t('dashboard.title')">
    <!-- Dashboard Stats Cards -->
    <v-row class="mb-6">
      <v-col
        v-for="stat in stats"
        :key="stat.title"
        cols="12"
        sm="6"
        md="3"
      >
        <v-card 
          class="text-center dashboard-stat-card" 
          elevation="3"
          style="background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
                 border-radius: 16px;
                 transition: all 0.3s ease;
                 border: 1px solid rgba(255,255,255,0.1);"
          @mouseover="$event.target.style.transform = 'translateY(-4px)'"
          @mouseleave="$event.target.style.transform = 'translateY(0px)'"
        >
          <v-card-text class="pa-6">
            <div
              class="mb-4 d-flex align-center justify-center"
              style="width: 64px; height: 64px; margin: 0 auto;
                     background: rgba(255,255,255,0.15);
                     border-radius: 16px;
                     backdrop-filter: blur(10px);"
            >
              <v-icon
                color="white"
                size="32"
              >
                {{ stat.icon }}
              </v-icon>
            </div>
            <div class="text-h4 font-weight-bold mb-2 text-white">
              {{ stat.value }}
            </div>
            <div class="text-subtitle-1 text-white opacity-90">
              {{ $t(stat.title) }}
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Money Case Statistics - Only visible to admin and supervisor -->
    <v-row class="mb-6" v-if="caseStats && ($page.props.auth.user.role === 'admin' || $page.props.auth.user.role === 'supervisor')">
      <v-col cols="12">
        <v-card elevation="3" style="border-radius: 16px;">
          <v-card-title class="pa-4 bg-gradient-primary text-white">
            <v-icon left>mdi-wallet</v-icon>
            Money Case Overview
          </v-card-title>
          <v-card-text class="pa-0">
            <v-row class="ma-0">
              <v-col cols="12" sm="6" md="3" class="pa-3">
                <div class="text-center">
                  <v-avatar color="success" size="48" class="mb-2">
                    <v-icon color="white">mdi-cash-plus</v-icon>
                  </v-avatar>
                  <div class="text-h5 font-weight-bold">{{ formatCurrency(caseStats.total_balance) }}</div>
                  <div class="text-subtitle-2 text--secondary">Total Balance</div>
                </div>
              </v-col>
              <v-col cols="12" sm="6" md="3" class="pa-3">
                <div class="text-center">
                  <v-avatar color="info" size="48" class="mb-2">
                    <v-icon color="white">mdi-trending-up</v-icon>
                  </v-avatar>
                  <div class="text-h5 font-weight-bold">{{ caseStats.total_collections }}</div>
                  <div class="text-subtitle-2 text--secondary">Total Collections</div>
                </div>
              </v-col>
              <v-col cols="12" sm="6" md="3" class="pa-3">
                <div class="text-center">
                  <v-avatar color="error" size="48" class="mb-2">
                    <v-icon color="white">mdi-cash-minus</v-icon>
                  </v-avatar>
                  <div class="text-h5 font-weight-bold">{{ formatCurrency(caseStats.total_expenses) }}</div>
                  <div class="text-subtitle-2 text--secondary">Approved Expenses</div>
                </div>
              </v-col>
              <v-col cols="12" sm="6" md="3" class="pa-3">
                <div class="text-center">
                  <v-avatar color="warning" size="48" class="mb-2">
                    <v-icon color="white">mdi-clock-outline</v-icon>
                  </v-avatar>
                  <div class="text-h5 font-weight-bold">{{ formatCurrency(caseStats.pending_expenses) }}</div>
                  <div class="text-subtitle-2 text--secondary">Pending Expenses</div>
                </div>
              </v-col>
            </v-row>
            
            <!-- Active Cases Quick View -->
            <v-divider></v-divider>
            <div class="pa-4">
              <div class="d-flex align-center justify-space-between mb-3">
                <h3 class="text-h6">Active Cases ({{ caseStats.active_cases_count }})</h3>
                <v-btn 
                  color="primary" 
                  variant="text" 
                  size="small"
                  :href="'/money-cases'"
                >
                  View All
                  <v-icon right>mdi-arrow-right</v-icon>
                </v-btn>
              </div>
              <v-row>
                 <v-col 
                   v-for="moneyCase in caseStats.cases" 
                   :key="moneyCase.id"
                   cols="12" 
                   sm="6" 
                   md="4"
                 >
                   <v-card outlined style="border-radius: 8px;">
                     <v-card-text class="pa-3">
                       <div class="d-flex align-center justify-space-between mb-2">
                         <h4 class="text-subtitle-1 font-weight-bold">{{ moneyCase.name }}</h4>
                         <v-chip 
                           :color="moneyCase.calculated_balance >= 0 ? 'success' : 'error'"
                           size="small"
                           text-color="white"
                         >
                           {{ formatCurrency(moneyCase.calculated_balance) }}
                         </v-chip>
                       </div>
                       <div class="text-caption text--secondary mb-2">
                         {{ moneyCase.description || 'No description' }}
                       </div>
                       <div class="d-flex justify-space-between text-caption">
                         <span>Collections: {{ moneyCase.collections_count }}</span>
                         <span>Expenses: {{ moneyCase.expenses_count }}</span>
                       </div>
                     </v-card-text>
                   </v-card>
                 </v-col>
               </v-row>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Recent Parcels and Quick Actions -->
 
  </AppLayout>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue'

export default {
  name: 'Dashboard',
  components: {
    AppLayout
  },
  props: {
    stats: {
      type: Object,
      default: () => ({})
    },
    recentParcels: {
      type: Array,
      default: () => []
    },
    caseStats: {
      type: Object,
      default: () => ({
        totalBalance: 0,
        totalCollections: 0,
        totalExpenses: 0,
        pendingExpenses: 0,
        activeCases: 0,
        cases: []
      })
    }
  },
  data() {
    return {
      parcelHeaders: [
        { text: this.$t('parcels.tracking_number'), value: 'tracking_number' },
        { text: this.$t('parcels.receiver_name'), value: 'recipient_name' },
        { text: this.$t('parcels.receiver_phone'), value: 'recipient_phone' },
        { text: this.$t('parcels.state'), value: 'state.name' },
        { text: this.$t('parcels.city'), value: 'city.name' },
        { text: this.$t('parcels.status'), value: 'status' },
        { text: 'COD Amount', value: 'cod_amount' },
        { text: 'Actions', value: 'actions', sortable: false }
      ],
      quickActions: [
        {
          title: 'dashboard.create_parcel',
          subtitle: 'dashboard.create_parcel_desc',
          icon: 'mdi-plus-circle',
          color: 'primary',
          route: '/parcels/create'
        },
        {
          title: 'dashboard.track_parcel',
          subtitle: 'dashboard.track_parcel_desc',
          icon: 'mdi-magnify',
          color: 'info',
          route: '/parcels'
        },
        {
          title: 'dashboard.reports',
          subtitle: 'dashboard.reports_desc',
          icon: 'mdi-chart-line',
          color: 'success',
          route: '/reports'
        }
      ]
    }
  },
  computed: {
    stats() {
      const statsData = this.$props.stats || {}
      return [
        {
          title: 'dashboard.total_parcels',
          value: statsData.total_parcels || 0,
          icon: 'mdi-package-variant',
          color: 'primary'
        },
        {
          title: 'dashboard.pending_parcels',
          value: statsData.pending_parcels || 0,
          icon: 'mdi-clock-outline',
          color: 'warning'
        },
        {
          title: 'dashboard.delivered_parcels',
          value: statsData.delivered_parcels || 0,
          icon: 'mdi-check-circle',
          color: 'success'
        },
        {
          title: 'dashboard.total_revenue',
          value: statsData.total_revenue  || 0,
          icon: 'mdi-cash',
          color: 'info'
        }
      ]
    }
  },
  methods: {
    getStatusColor(status) {
      const colors = {
        pending: 'orange',
        picked_up: 'blue',
        in_transit: 'purple',
        out_for_delivery: 'indigo',
        delivered: 'green',
        returned: 'red',
        cancelled: 'grey'
      }
      return colors[status] || 'grey'
    },
    formatCurrency(amount) {
      return new Intl.NumberFormat('ar', {
        style: 'currency',
        currency: 'DZD'
      }).format(amount)
    }
  }
}
</script>

<style scoped>
.dashboard-stat-card:hover {
  box-shadow: 0 12px 40px rgba(0,0,0,0.15) !important;
}

.stat-avatar {
  box-shadow: 0 8px 32px rgba(0,0,0,0.1);
}

.quick-action-item:hover {
  cursor: pointer;
  transform: translateX(4px);
}

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

.simple-table >>> .v-data-table .v-data-table-footer {
  background: #fafafa !important;
  border-top: 1px solid #e0e0e0 !important;
}
</style>