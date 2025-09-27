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
            <div class="text-h3 font-weight-bold mb-2 text-white">
              {{ stat.value }}
            </div>
            <div class="text-subtitle-1 text-white opacity-90">
              {{ $t(stat.title) }}
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Recent Parcels and Quick Actions -->
    <v-row>
      <!-- Recent Parcels -->
      <v-col cols="12" md="8">
        <v-card 
          elevation="1"
          style="border-radius: 8px; background: white;"
        >
          <v-card-title class="pa-4" style="background: #f5f5f5; border-bottom: 1px solid #e0e0e0;">
            <v-icon left color="#666" size="24">mdi-package-variant</v-icon>
            <span class="text-h6 font-weight-medium" style="color: #333;">{{ $t('dashboard.recent_parcels') }}</span>
          </v-card-title>
          <v-card-text class="pa-0">
            <v-data-table
              :headers="parcelHeaders"
              :items="recentParcels"
              :items-per-page="5"
              hide-default-footer
              class="elevation-0 simple-table"
            >
              <template v-slot:[`item.tracking_number`]="{ item }">
                <v-chip
                  small
                  color="primary"
                  @click="$inertia.visit(`/parcels/${item.id}`)"
                  class="cursor-pointer"
                >
                  {{ item.tracking_number }}
                </v-chip>
              </template>
              
              <template v-slot:[`item.status`]="{ item }">
                <v-chip
                  small
                  :color="getStatusColor(item.status)"
                  outlined
                >
                  {{ $t(`parcels.status_${item.status}`) }}
                </v-chip>
              </template>
              
              <template v-slot:[`item.cod_amount`]="{ item }">
                <span v-if="item.cod_amount">
                  {{ formatCurrency(item.cod_amount) }}
                </span>
                <span v-else class="text--secondary">-</span>
              </template>
              
              <template v-slot:[`item.actions`]="{ item }">
                <v-btn
                  icon
                  small
                  @click="$inertia.visit(`/parcels/${item.id}`)"
                >
                  <v-icon small>mdi-eye</v-icon>
                </v-btn>
                <v-btn
                  icon
                  small
                  @click="$inertia.visit(`/parcels/${item.id}/edit`)"
                >
                  <v-icon small>mdi-pencil</v-icon>
                </v-btn>
              </template>
            </v-data-table>
          </v-card-text>
          <v-card-actions class="pa-4">
            <v-spacer></v-spacer>
            <v-btn
              color="primary"
              text
              @click="$inertia.visit('/parcels')"
            >
              {{ $t('dashboard.view_all_parcels') }}
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-col>

      <!-- Quick Actions -->
      <v-col cols="12" md="4">
        <v-card 
          elevation="8"
          style="border-radius: 16px;
                 background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
                 border: 1px solid rgba(255,255,255,0.1);"
        >
          <v-card-title class="pa-6" style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px);">
            <v-icon left color="white" size="28">mdi-lightning-bolt</v-icon>
            <span class="text-h5 font-weight-bold text-white">{{ $t('dashboard.quick_actions') }}</span>
          </v-card-title>
          <v-card-text class="pa-4">
            <v-list class="transparent">
              <v-list-item
                v-for="action in quickActions"
                :key="action.title"
                @click="$inertia.visit(action.route)"
                class="mb-3 quick-action-item"
                style="border-radius: 12px;
                       background: rgba(255,255,255,0.2);
                       backdrop-filter: blur(10px);
                       border: 1px solid rgba(255,255,255,0.1);
                       transition: all 0.3s ease;"
                @mouseover="$event.target.style.background = 'rgba(255,255,255,0.3)'"
                @mouseleave="$event.target.style.background = 'rgba(255,255,255,0.2)'"
              >
                <v-list-item-icon>
                  <v-avatar
                    :color="action.color"
                    size="40"
                    style="background: linear-gradient(135deg, rgba(255,255,255,0.3), rgba(255,255,255,0.1));"
                  >
                    <v-icon color="white" size="20">{{ action.icon }}</v-icon>
                  </v-avatar>
                </v-list-item-icon>
                <v-list-item-content>
                  <v-list-item-title class="font-weight-bold text-white">{{ $t(action.title) }}</v-list-item-title>
                  <v-list-item-subtitle class="text-white opacity-80">{{ $t(action.subtitle) }}</v-list-item-subtitle>
                </v-list-item-content>
                <v-list-item-action>
                  <v-icon color="white">mdi-chevron-right</v-icon>
                </v-list-item-action>
              </v-list-item>
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
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
          value: this.formatCurrency(statsData.total_revenue || 0),
          icon: 'mdi-currency-usd',
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
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
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