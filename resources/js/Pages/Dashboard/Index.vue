<template>
  <AppLayout :title="$t('dashboard.title')">
    <!-- Date Filter -->
    <v-row class="mb-4">
      <v-col cols="12">
        <v-card elevation="0" class="pa-4" style="border-radius: 16px; border: 1px solid #e5e7eb; background: #ffffff;">
          <div class="d-flex flex-wrap align-center gap-4">
            <v-text-field
              v-model="filterStart"
              label="Start date"
              type="date"
              variant="outlined"
              density="compact"
              hide-details
              style="max-width: 220px;"
              color="#667eea"
            />
            <v-text-field
              v-model="filterEnd"
              label="End date"
              type="date"
              variant="outlined"
              density="compact"
              hide-details
              style="max-width: 220px;"
              color="#667eea"
            />
            <v-select
              v-model="filterCompany"
              :items="companyOptions"
              label="Company"
              variant="outlined"
              density="compact"
              hide-details
              style="max-width: 220px;"
              color="#667eea"
              clearable
            />
            <v-btn 
              style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-transform: none; border-radius: 10px;"
              prepend-icon="mdi-filter" 
              @click="applyFilters"
              elevation="0"
            >
              Apply Filter
            </v-btn>
            <v-spacer></v-spacer>
            <v-btn 
              variant="outlined" 
              prepend-icon="mdi-calendar-today" 
              @click="quickToday"
              style="border-color: #e5e7eb; color: #6b7280; text-transform: none; border-radius: 10px;"
            >
              Today
            </v-btn>
          </div>
        </v-card>
      </v-col>
    </v-row>

    <!-- Dashboard Stats Cards -->
    <v-row class="mb-6">
      <v-col
        v-for="(stat, index) in stats"
        :key="stat.title"
        cols="12"
        sm="6"
        md="3"
      >
        <v-card 
          class="text-center dashboard-stat-card" 
          elevation="0"
          :style="{
            background: getCardGradient(index),
            borderRadius: '16px',
            transition: 'all 0.3s ease',
            border: '1px solid rgba(255,255,255,0.1)',
            overflow: 'hidden',
            position: 'relative'
          }"
        >
          <!-- Decorative background circle -->
          <div 
            style="position: absolute; top: -30px; right: -30px; width: 120px; height: 120px; 
                   background: rgba(255,255,255,0.1); border-radius: 50%;">
          </div>
          
          <v-card-text class="pa-6" style="position: relative; z-index: 1;">
            <div
              class="mb-4 d-flex align-center justify-center"
              style="width: 64px; height: 64px; margin: 0 auto;
                     background: rgba(255,255,255,0.2);
                     border-radius: 16px;
                     backdrop-filter: blur(10px);
                     box-shadow: 0 4px 12px rgba(0,0,0,0.1);"
            >
              <v-icon
                color="white"
                size="32"
              >
                {{ stat.icon }}
              </v-icon>
            </div>
            <div class="text-h4 font-weight-bold mb-2 text-white" :class="{'revenue-blur': stat.title === 'dashboard.total_revenue'}">
              {{ stat.value }}
            </div>
            <div class="text-subtitle-1 text-white" style="opacity: 0.95;">
              {{ $t(stat.title) }}
            </div>
            <!-- Details under Delivered card: show stopdesk and home delivery counts -->
            <div v-if="stat.title === 'dashboard.delivered_parcels'" class="text-caption text-white mt-2" style="opacity: 0.85;">
              {{ $t('dashboard.stopdesk_collections_count') }}: {{ $props.stats.stopdesk_collections_count || 0 }} · 
              {{ $t('dashboard.home_delivery_collections_count') }}: {{ $props.stats.home_delivery_collections_count || 0 }}
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Charts Section - Show when date range is more than 1 day -->
    <v-row class="mb-6" v-if="showCharts && $page.props.auth.user.role !== 'agent'">
      <v-col cols="12">
        <v-card elevation="0" style="border-radius: 16px; border: 1px solid #e5e7eb; background: #ffffff;">
          <v-card-title class="pa-5 d-flex align-center justify-space-between" style="border-bottom: 1px solid #e5e7eb;">
            <div class="d-flex align-center">
              <v-icon size="24" style="color: #667eea;" class="mr-2">mdi-chart-line</v-icon>
              <span class="font-weight-bold" style="color: #1a1d29;">Performance Analytics</span>
            </div>
            <v-chip 
              size="small" 
              style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;"
            >
              {{ dateRangeDays }} Days
            </v-chip>
          </v-card-title>
          
          <v-card-text class="pa-5">
            <!-- Chart Tabs -->
            <v-tabs 
              v-model="activeChart" 
              style="margin-bottom: 24px;"
              color="#667eea"
            >
              <!-- <v-tab value="revenue">
                <v-icon left size="20">mdi-cash</v-icon>
                Revenue
              </v-tab>
              <v-tab value="collected">
                <v-icon left size="20">mdi-cash-multiple</v-icon>
                Money Collected
              </v-tab>
              <v-tab value="parcels">
                <v-icon left size="20">mdi-package-variant</v-icon>
                Delivered Parcels
              </v-tab> -->
              <v-tab value="combined">
                <v-icon left size="20">mdi-chart-multiple</v-icon>
                Combined View
              </v-tab>
            </v-tabs>

            <!-- Chart Container -->
            <div style="position: relative; height: 400px;">
              <canvas ref="chartCanvas"></canvas>
            </div>

            <!-- Chart Legend/Stats -->
            <v-row class="mt-4">
              <v-col cols="12" sm="4" v-if="$page.props.auth.user.role === 'admin'">
                <div class="text-center pa-3" style="background: #f9fafb; border-radius: 12px;">
                  <div class="text-caption" style="color: #6b7280;">Total Revenue</div>
                  <div class="text-h6 font-weight-bold" style="color: #8b5cf6;">
                    {{ formatCurrency(totalRevenue) }}
                  </div>
                </div>
              </v-col>
              <v-col cols="12" :sm="$page.props.auth.user.role === 'admin' ? '4' : '6'">
                <div class="text-center pa-3" style="background: #f9fafb; border-radius: 12px;">
                  <div class="text-caption" style="color: #6b7280;">Total Collected</div>
                  <div class="text-h6 font-weight-bold" style="color: #10b981;">
                    {{ formatCurrency(totalCollected) }}
                  </div>
                </div>
              </v-col>
              <v-col cols="12" :sm="$page.props.auth.user.role === 'admin' ? '4' : '6'">
                <div class="text-center pa-3" style="background: #f9fafb; border-radius: 12px;">
                  <div class="text-caption" style="color: #6b7280;">Total Delivered</div>
                  <div class="text-h6 font-weight-bold" style="color: #3b82f6;">
                    {{ totalDelivered }}
                  </div>
                </div>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Recolted Money Statistics -->
    <v-row class="mb-6" v-if="recoltedStats && $page.props.auth.user.role !== 'agent'">
      <v-col cols="12">
        <v-card elevation="0" style="border-radius: 16px; border: 1px solid #e5e7eb; background: #ffffff;">
          <v-card-title class="pa-5" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border-radius: 16px 16px 0 0;">
            <v-icon left color="white">mdi-cash-multiple</v-icon>
            <span class="font-weight-bold">{{ $t('dashboard.recolted_money.title') }}</span>
          </v-card-title>
          <v-card-text class="pa-0">
            <v-row class="ma-0">
              <v-col cols="12" sm="6" md="3" class="pa-4">
                <div class="text-center">
                  <v-avatar 
                    size="56" 
                    class="mb-3"
                    style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);"
                  >
                    <v-icon color="white" size="28">mdi-cash-plus</v-icon>
                  </v-avatar>
                  <div class="text-h5 font-weight-bold" style="color: #1a1d29;">{{ formatCurrency(recoltedStats.total_recolted_amount) }}</div>
                  <div class="text-body-2" style="color: #6b7280;">{{ $t('dashboard.recolted_money.total_recolted') }}</div>
                </div>
              </v-col>
              <v-col cols="12" sm="6" md="3" class="pa-4">
                <div class="text-center">
                  <v-avatar 
                    size="56" 
                    class="mb-3"
                    style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);"
                  >
                    <v-icon color="white" size="28">mdi-cash-edit</v-icon>
                  </v-avatar>
                  <div class="text-h5 font-weight-bold" style="color: #1a1d29;">{{ formatCurrency(recoltedStats.total_recolted_amount || 0) }}</div>
                  <div class="text-body-2" style="color: #6b7280;">{{ $t('dashboard.recolted_money.manual_amount') }}</div>
                </div>
              </v-col>
              <v-col cols="12" sm="6" md="3" class="pa-4">
                <div class="text-center">
                  <v-avatar 
                    size="56" 
                    class="mb-3"
                    style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);"
                  >
                    <v-icon color="white" size="28">mdi-file-document-multiple</v-icon>
                  </v-avatar>
                  <div class="text-h5 font-weight-bold" style="color: #1a1d29;">{{ recoltedStats.recoltes_count }}</div>
                  <div class="text-body-2" style="color: #6b7280;">{{ $t('dashboard.recolted_money.total_recoltes') }}</div>
                </div>
              </v-col>
              <v-col cols="12" sm="6" md="3" class="pa-4">
                <div class="text-center">
                  <v-avatar 
                    size="56" 
                    class="mb-3"
                    style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);"
                  >
                    <v-icon color="white" size="28">mdi-alert-circle</v-icon>
                  </v-avatar>
                  <div class="text-h5 font-weight-bold" style="color: #1a1d29;">{{ recoltedStats.discrepancy_count }}</div>
                  <div class="text-body-2" style="color: #6b7280;">{{ $t('dashboard.recolted_money.discrepancies') }}</div>
                </div>
              </v-col>
            </v-row>
            
            <!-- Recent Recoltes -->
            <v-divider style="border-color: #e5e7eb;"></v-divider>
            <div class="pa-5" v-if="recoltedStats.recent_recoltes && recoltedStats.recent_recoltes.length > 0">
              <div class="d-flex align-center justify-space-between mb-4">
                <h3 class="text-h6 font-weight-bold" style="color: #1a1d29;">{{ $t('dashboard.recolted_money.recent_recoltes') }} ({{ recoltedStats.recent_recoltes.length }})</h3>
                <v-btn 
                  variant="text" 
                  size="small"
                  :href="'/recoltes'"
                  style="color: #10b981; text-transform: none;"
                >
                  {{ $t('dashboard.recolted_money.view_all') }}
                  <v-icon right size="18">mdi-arrow-right</v-icon>
                </v-btn>
              </div>
              <v-row>
                <v-col 
                  v-for="recolte in recoltedStats.recent_recoltes" 
                  :key="recolte.id"
                  cols="12" 
                  sm="6" 
                  md="4"
                >
                  <v-card 
                    elevation="0" 
                    style="border-radius: 12px; border: 1px solid #e5e7eb; background: #f9fafb; transition: all 0.2s ease;"
                    class="recolte-card"
                  >
                    <v-card-text class="pa-4">
                      <div class="d-flex align-center justify-space-between mb-3">
                        <h4 class="text-subtitle-1 font-weight-bold" style="color: #1a1d29;">
                          <v-icon size="20" style="color: #10b981;">mdi-file-document-multiple</v-icon>
                      RCT-{{ recolte.code }}
                        </h4>
                        <v-chip 
                          :style="{
                            background: recolte.has_discrepancy ? 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)' : 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
                            color: 'white'
                          }"
                          size="small"
                        >
                          {{ formatCurrency(recolte.calculated_amount) }}
                        </v-chip>
                      </div>
                      <div class="text-caption mb-3" style="color: #6b7280;">
                        {{ recolte.company_name }} • {{ recolte.collections_count }} {{ $t('dashboard.recolted_money.collections_count') }}
                      </div>
                      <div class="d-flex justify-space-between align-center">
                        <div class="text-caption" style="color: #6b7280;">
                          {{ formatDate(recolte.created_at) }}
                        </div>
                        <div v-if="recolte.has_discrepancy" class="d-flex align-center">
                          <v-icon size="16" color="warning" class="mr-1">mdi-alert-circle</v-icon>
                          <span class="text-caption" style="color: #f59e0b;">{{ $t('dashboard.recolted_money.discrepancy_indicator') }}</span>
                        </div>
                      </div>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>
            </div>
            <div v-else class="pa-5 text-center">
              <v-icon size="48" color="grey-lighten-1" class="mb-3">mdi-file-document-multiple-outline</v-icon>
              <div class="text-body-2" style="color: #6b7280;">{{ $t('dashboard.recolted_money.no_recoltes') }}</div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Money Case Statistics - Only visible to admin and supervisor -->
    <v-row class="mb-6" v-if="caseStats && ($page.props.auth.user.role === 'admin' || $page.props.auth.user.role === 'supervisor')">
      <v-col cols="12">
        <v-card elevation="0" style="border-radius: 16px; border: 1px solid #e5e7eb; background: #ffffff;">
          <v-card-title class="pa-5" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 16px 16px 0 0;">
            <v-icon left color="white">mdi-wallet</v-icon>
            <span class="font-weight-bold">Money Case Overview</span>
          </v-card-title>
          <v-card-text class="pa-0">
            <v-row class="ma-0">
              <v-col cols="12" sm="6" md="3" class="pa-4">
                <div class="text-center">
                  <v-avatar 
                    size="56" 
                    class="mb-3"
                    style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);"
                  >
                    <v-icon color="white" size="28">mdi-cash-plus</v-icon>
                  </v-avatar>
                  <div class="text-h5 font-weight-bold" style="color: #1a1d29;">{{ formatCurrency(caseStats.total_balance) }}</div>
                  <div class="text-body-2" style="color: #6b7280;">Total Balance</div>
                </div>
              </v-col>
              <v-col cols="12" sm="6" md="3" class="pa-4">
                <div class="text-center">
                  <v-avatar 
                    size="56" 
                    class="mb-3"
                    style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);"
                  >
                    <v-icon color="white" size="28">mdi-trending-up</v-icon>
                  </v-avatar>
                  <div class="text-h5 font-weight-bold" style="color: #1a1d29;">{{ caseStats.total_collections }}</div>
                  <div class="text-body-2" style="color: #6b7280;">Total Collections</div>
                </div>
              </v-col>
              <v-col cols="12" sm="6" md="3" class="pa-4">
                <div class="text-center">
                  <v-avatar 
                    size="56" 
                    class="mb-3"
                    style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);"
                  >
                    <v-icon color="white" size="28">mdi-cash-minus</v-icon>
                  </v-avatar>
                  <div class="text-h5 font-weight-bold" style="color: #1a1d29;">{{ formatCurrency(caseStats.total_expenses) }}</div>
                  <div class="text-body-2" style="color: #6b7280;">Approved Expenses</div>
                </div>
              </v-col>
              <v-col cols="12" sm="6" md="3" class="pa-4">
                <div class="text-center">
                  <v-avatar 
                    size="56" 
                    class="mb-3"
                    style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);"
                  >
                    <v-icon color="white" size="28">mdi-clock-outline</v-icon>
                  </v-avatar>
                  <div class="text-h5 font-weight-bold" style="color: #1a1d29;">{{ formatCurrency(caseStats.pending_expenses) }}</div>
                  <div class="text-body-2" style="color: #6b7280;">Pending Expenses</div>
                </div>
              </v-col>
            </v-row>
            
            <!-- Active Cases Quick View -->
            <v-divider style="border-color: #e5e7eb;"></v-divider>
            <div class="pa-5">
              <div class="d-flex align-center justify-space-between mb-4">
                <h3 class="text-h6 font-weight-bold" style="color: #1a1d29;">Active Cases ({{ caseStats.active_cases_count }})</h3>
                <v-btn 
                  variant="text" 
                  size="small"
                  :href="'/money-cases'"
                  style="color: #667eea; text-transform: none;"
                >
                  View All
                  <v-icon right size="18">mdi-arrow-right</v-icon>
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
                   <v-card 
                     elevation="0" 
                     style="border-radius: 12px; border: 1px solid #e5e7eb; background: #f9fafb; transition: all 0.2s ease;"
                     class="money-case-card"
                   >
                     <v-card-text class="pa-4">
                       <div class="d-flex align-center justify-space-between mb-3">
                         <h4 class="text-subtitle-1 font-weight-bold" style="color: #1a1d29;">
                          <v-icon size="20" style="color: #667eea;">mdi-cash-register</v-icon>
                          {{ moneyCase.name }}
                         </h4>
                         <v-chip 
                           :style="{
                             background: moneyCase.calculated_balance >= 0 ? 'linear-gradient(135deg, #10b981 0%, #059669 100%)' : 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)',
                             color: 'white'
                           }"
                           size="small"
                         >
                           {{ formatCurrency(moneyCase.calculated_balance) }}
                         </v-chip>
                       </div>
                       <div class="text-caption mb-3" style="color: #6b7280;">
                         {{ moneyCase.description || 'No description' }}
                       </div>
                       <!-- Money Case Status -->
                       <div class="mb-3">
                         <v-chip 
                           :style="{
                             background: moneyCase.last_active_by ? '#eff6ff' : '#f0fdf4',
                             color: moneyCase.last_active_by ? '#3b82f6' : '#10b981',
                             border: moneyCase.last_active_by ? '1px solid #bfdbfe' : '1px solid #bbf7d0'
                           }"
                           size="small"
                           variant="flat"
                         >
                           <v-icon left size="14">
                             {{ moneyCase.last_active_by ? 'mdi-account-lock' : 'mdi-check-circle' }}
                           </v-icon>
                           {{ moneyCase.last_active_by ? `Occupied by ${moneyCase.last_active_user.uid || 'Unknown'}` : 'Free' }}
                         </v-chip>
                       </div>
                       <div class="d-flex justify-space-between text-caption" style="color: #6b7280;">
                         <span><strong style="color: #1a1d29;">Collections:</strong> {{ moneyCase.collections_count }}</span>
                         <span><strong style="color: #1a1d29;">Expenses:</strong> {{ moneyCase.expenses_count }}</span>
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
  </AppLayout>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue'
import { router } from '@inertiajs/vue3'
import { Chart, registerables } from 'chart.js'

// Register Chart.js components
Chart.register(...registerables)

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
      default: () => ({})
    },
    dailyStats: {
      type: Array,
      default: () => []
    },
    companies: {
      type: Array,
      default: () => []
    },
    recoltedStats: {
      type: Object,
      default: () => ({})
    },
      filters: {
      type: Object,
      default: () => ({ start: new Date().toISOString().slice(0, 10), end: new Date().toISOString().slice(0, 10), company_id: null })
    }
  },
  data() {
    return {
      filterStart: this.$props.filters?.start || new Date().toISOString().slice(0, 10),
      filterEnd: this.$props.filters?.end || new Date().toISOString().slice(0, 10),
      filterCompany: this.$props.filters?.company_id || null,
      activeChart: 'combined',
      chart: null
    }
  },
  computed: {
    companyOptions() {
      const options = [{ title: 'All Companies', value: null }]
      if (this.$props.companies && this.$props.companies.length > 0) {
        this.$props.companies.forEach(company => {
          options.push({
            title: company.name || company.code,
            value: company.id
          })
        })
      }
      return options
    },
    stats() {
      const statsData = this.$props.stats || {}
      const items = [
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
        // Revenue card visible only to admins
        {
          title: 'dashboard.total_revenue',
          value: statsData.total_revenue  || 0,
          icon: 'mdi-cash',
          color: 'info',
          adminOnly: true
        }
      ]
      const isAdmin = this.$page?.props?.auth?.user?.role === 'admin'
      return items.filter(item => !item.adminOnly || isAdmin)
    },
    showCharts() {
      return this.dateRangeDays > 1 && this.dailyStats.length > 1
    },
    dateRangeDays() {
      const start = new Date(this.filterStart)
      const end = new Date(this.filterEnd)
      const diffTime = Math.abs(end - start)
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
      return diffDays + 1
    },
    totalRevenue() {
      return this.dailyStats.reduce((sum, day) => sum + day.revenue, 0)
    },
    totalCollected() {
      return this.dailyStats.reduce((sum, day) => sum + day.money_collected, 0)
    },
    totalDelivered() {
      return this.dailyStats.reduce((sum, day) => sum + day.delivered_parcels, 0)
    }
  },
  watch: {
    activeChart() {
      this.updateChart()
    },
    dailyStats() {
      this.$nextTick(() => {
        this.updateChart()
      })
    }
  },
  mounted() {
    if (this.showCharts) {
      this.$nextTick(() => {
        this.initChart()
      })
    }
  },
  beforeUnmount() {
    if (this.chart) {
      this.chart.destroy()
    }
  },
  methods: {
    getCardGradient(index) {
      const gradients = [
        'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', // Purple
        'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)', // Orange
        'linear-gradient(135deg, #10b981 0%, #059669 100%)', // Green
        'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)'  // Blue
      ]
      return gradients[index % gradients.length]
    },
    initChart() {
      if (!this.$refs.chartCanvas) return
      
      const ctx = this.$refs.chartCanvas.getContext('2d')
      
      this.chart = new Chart(ctx, {
        type: 'line',
        data: this.getChartData(),
        options: this.getChartOptions()
      })
    },
    updateChart() {
      if (!this.chart) {
        this.initChart()
        return
      }
      
      this.chart.data = this.getChartData()
      this.chart.options = this.getChartOptions()
      this.chart.update()
    },
    getChartData() {
      const labels = this.dailyStats.map(d => d.date_formatted)
      
      const datasets = []
      
      if ((this.activeChart === 'revenue' || this.activeChart === 'combined') && this.$page.props.auth.user.role === 'admin') {
        datasets.push({
          label: 'Revenue (DZD)',
          data: this.dailyStats.map(d => d.revenue),
          borderColor: '#8b5cf6',
          backgroundColor: 'rgba(139, 92, 246, 0.1)',
          tension: 0.4,
          fill: true,
          pointRadius: 5,
          pointHoverRadius: 7,
          pointBackgroundColor: '#8b5cf6',
          pointBorderColor: '#fff',
          pointBorderWidth: 2
        })
      }
      
      if (this.activeChart === 'collected' || this.activeChart === 'combined') {
        datasets.push({
          label: 'Money Collected (DZD)',
          data: this.dailyStats.map(d => d.money_collected),
          borderColor: '#10b981',
          backgroundColor: 'rgba(16, 185, 129, 0.1)',
          tension: 0.4,
          fill: true,
          pointRadius: 5,
          pointHoverRadius: 7,
          pointBackgroundColor: '#10b981',
          pointBorderColor: '#fff',
          pointBorderWidth: 2
        })
      }
      
      if (this.activeChart === 'parcels' || this.activeChart === 'combined') {
        datasets.push({
          label: 'Delivered Parcels',
          data: this.dailyStats.map(d => d.delivered_parcels),
          borderColor: '#3b82f6',
          backgroundColor: 'rgba(59, 130, 246, 0.1)',
          tension: 0.4,
          fill: true,
          pointRadius: 5,
          pointHoverRadius: 7,
          pointBackgroundColor: '#3b82f6',
          pointBorderColor: '#fff',
          pointBorderWidth: 2,
          yAxisID: this.activeChart === 'combined' ? 'y1' : 'y'
        })
      }
      
      return { labels, datasets }
    },
    getChartOptions() {
      const options = {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          mode: 'index',
          intersect: false
        },
        plugins: {
          legend: {
            display: true,
            position: 'top',
            labels: {
              usePointStyle: true,
              padding: 20,
              font: {
                size: 12,
                weight: '500'
              }
            }
          },
          tooltip: {
            backgroundColor: 'rgba(26, 29, 41, 0.95)',
            titleColor: '#fff',
            bodyColor: '#fff',
            borderColor: '#667eea',
            borderWidth: 1,
            padding: 12,
            displayColors: true,
            callbacks: {
              label: (context) => {
                let label = context.dataset.label || ''
                if (label) {
                  label += ': '
                }
                if (context.parsed.y !== null) {
                  if (label.includes('Parcels')) {
                    label += context.parsed.y
                  } else {
                    label += this.formatCurrency(context.parsed.y)
                  }
                }
                return label
              }
            }
          }
        },
        scales: {
          x: {
            grid: {
              display: false
            },
            ticks: {
              font: {
                size: 11
              }
            }
          },
          y: {
            beginAtZero: true,
            grid: {
              color: '#f3f4f6'
            },
            ticks: {
              callback: (value) => {
                if (this.activeChart === 'parcels') {
                  return value
                }
                return this.formatCurrency(value)
              },
              font: {
                size: 11
              }
            }
          }
        }
      }
      
      // Add second Y axis for combined view
      if (this.activeChart === 'combined') {
        options.scales.y1 = {
          beginAtZero: true,
          position: 'right',
          grid: {
            drawOnChartArea: false
          },
          ticks: {
            callback: (value) => value,
            font: {
              size: 11
            }
          }
        }
      }
      
      return options
    },
    formatCurrency(amount) {
      return new Intl.NumberFormat('ar', {
        style: 'currency',
        currency: 'DZD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }).format(amount)
    },
    formatDate(dateString) {
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    },
    applyFilters() {
      const params = {
        start: this.filterStart,
        end: this.filterEnd
      }
      if (this.filterCompany !== null) {
        params.company_id = this.filterCompany
      }
      router.get('/dashboard', params, {
        preserveState: false,
        preserveScroll: false
      })
    },
    quickToday() {
      const today = new Date().toISOString().slice(0, 10)
      this.filterStart = today
      this.filterEnd = today
      this.applyFilters()
    }
  }
}
</script>

<style scoped>
.dashboard-stat-card {
  cursor: pointer;
}

.dashboard-stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 40px rgba(0,0,0,0.15) !important;
}

/* Add blur to revenue value, remove blur when the card is hovered */
.revenue-blur {
  filter: blur(6px);
  transition: filter 0.2s ease;
}

.dashboard-stat-card:hover .revenue-blur {
  filter: blur(0);
}

.money-case-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.08) !important;
  border-color: #d1d5db !important;
}
</style>
