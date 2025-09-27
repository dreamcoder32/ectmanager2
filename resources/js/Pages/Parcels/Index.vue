<template>
  <AppLayout>
    <Head title="Parcels" />
    
    <template #title>
      <div class="d-flex justify-space-between align-center">
        <span class="text-h4 font-weight-bold" 
              style="background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
                     -webkit-background-clip: text;
                     -webkit-text-fill-color: transparent;
                     background-clip: text;">
          {{ $t('parcels.title') }}
        </span>
        <v-btn
          color="primary"
          href="/parcels/create"
          prepend-icon="mdi-plus"
          style="font-weight: 600; border-radius: 12px;"
          elevation="2"
        >
          <v-icon left>mdi-plus</v-icon>
          {{ $t('parcels.create_new') }}
        </v-btn>
      </div>
    </template>
    
    <template #content>
      <v-container fluid>
        <!-- Filters Card -->
        <v-card 
          class="mb-4" 
          elevation="1"
          style="border-radius: 8px; background: white;"
        >
          <v-card-title class="pa-4" style="background: #f5f5f5; border-bottom: 1px solid #e0e0e0;">
            <v-icon left color="#666" size="24">mdi-filter-variant</v-icon>
            <span class="text-h6 font-weight-medium" style="color: #333;">Filters</span>
          </v-card-title>
          <v-card-text class="pa-4">
            <v-row>
              <v-col cols="12" md="3">
                <v-text-field
                  v-model="filters.search"
                  :label="$t('parcels.search')"
                  prepend-inner-icon="mdi-magnify"
                  outlined
                  dense
                  @input="debounceSearch"
                  style="border-radius: 8px;"
                  color="primary"
                ></v-text-field>
              </v-col>
              
              <v-col cols="12" md="3">
                <v-select
                  v-model="filters.status"
                  :items="statusOptions"
                  :label="$t('parcels.status')"
                  item-title="text"
                  item-value="value"
                  outlined
                  dense
                  clearable
                  @change="applyFilters"
                  style="border-radius: 8px;"
                  color="primary"
                  :menu-props="{ offsetY: true, maxHeight: 300 }"
                ></v-select>
              </v-col>
              
              <v-col cols="12" md="3">
                <v-select
                  v-model="filters.state_id"
                  :items="stateOptions"
                  :label="$t('parcels.state')"
                  item-title="text"
                  item-value="value"
                  outlined
                  dense
                  clearable
                  @change="onStateChange"
                  style="border-radius: 8px;"
                  color="primary"
                  :menu-props="{ offsetY: true, maxHeight: 300 }"
                ></v-select>
              </v-col>
              
              <v-col cols="12" md="3">
                <v-select
                  v-model="filters.city_id"
                  :items="cityOptions"
                  :label="$t('parcels.city')"
                  item-title="text"
                  item-value="value"
                  outlined
                  dense
                  clearable
                  :disabled="!filters.state_id"
                  @change="applyFilters"
                  style="border-radius: 8px;"
                  color="primary"
                  :menu-props="{ offsetY: true, maxHeight: 300 }"
                ></v-select>
              </v-col>
            </v-row>
            
            <v-row>
              <v-col cols="12" class="text-right">
                <v-btn 
                  color="primary" 
                  @click="clearFilters"
                  outlined
                  style="border-radius: 8px;"
                >
                  {{ $t('common.clear') }}
                </v-btn>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>

        <!-- Parcels Data Table -->
        <v-card 
          elevation="1"
          style="border-radius: 8px; background: white;"
        >
          <v-card-title class="pa-4" style="background: #f5f5f5; border-bottom: 1px solid #e0e0e0;">
            <v-icon left color="#666" size="24">mdi-package-variant</v-icon>
            <span class="text-h6 font-weight-medium" style="color: #333;">{{ $t('parcels.title') }}</span>
          </v-card-title>
          <v-card-text class="pa-0">
            <v-data-table
              :headers="headers"
              :items="parcels.data"
              :loading="loading"
              :server-items-length="parcels.total"
              :options.sync="options"
              :footer-props="{
                'items-per-page-options': [10, 25, 50, 100]
              }"
              @update:options="updateOptions"
              class="elevation-0 simple-table"
              item-key="id"
            >
              <template v-slot:[`item.status`]="{ item }">
                 <v-chip
                   :color="getStatusColor(item.status)"
                   small
                   outlined
                 >
                   {{ $t(`parcels.status_${item.status}`) }}
                 </v-chip>
               </template>
               
               <template v-slot:[`item.cod_amount`]="{ item }">
                 <span v-if="item.cod_amount" class="font-weight-medium">
                   ${{ parseFloat(item.cod_amount).toFixed(2) }}
                 </span>
                 <span v-else class="text-grey">-</span>
               </template>
               
               <template v-slot:[`item.actions`]="{ item }">
                 <v-btn
                   icon
                   small
                   color="primary"
                   @click="$inertia.visit(`/parcels/${item.id}`)"
                   class="mr-1"
                 >
                   <v-icon small>mdi-eye</v-icon>
                 </v-btn>
                 <v-btn
                   icon
                   small
                   color="primary"
                   @click="$inertia.visit(`/parcels/${item.id}/edit`)"
                 >
                   <v-icon small>mdi-pencil</v-icon>
                 </v-btn>
               </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-container>
    </template>
  </AppLayout>
</template>

<script>
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

export default {
  name: 'ParcelIndex',
  components: {
    AppLayout
  },
  props: {
    parcels: Object,
    states: Array,
    cities: Array,
    filters: Object
  },
  data() {
    return {
      loading: false,
      options: {},
      searchTimeout: null,
      localFilters: { ...this.filters }
    }
  },
  computed: {
    headers() {
      return [
        { text: this.$t('parcels.tracking_number'), value: 'tracking_number' },
        { text: this.$t('parcels.sender_name'), value: 'sender_name' },
        { text: this.$t('parcels.receiver_name'), value: 'recipient_name' },
        { text: this.$t('parcels.status'), value: 'status' },
        { text: 'COD Amount', value: 'cod_amount' },
        { text: this.$t('parcels.actions'), value: 'actions', sortable: false }
      ]
    },
    statusOptions() {
      return [
        { text: this.$t('parcels.status_pending'), value: 'pending' },
        { text: this.$t('parcels.status_picked_up'), value: 'picked_up' },
        { text: this.$t('parcels.status_in_transit'), value: 'in_transit' },
        { text: this.$t('parcels.status_out_for_delivery'), value: 'out_for_delivery' },
        { text: this.$t('parcels.status_delivered'), value: 'delivered' },
        { text: this.$t('parcels.status_returned'), value: 'returned' },
        { text: this.$t('parcels.status_cancelled'), value: 'cancelled' }
      ]
    },
    stateOptions() {
      return this.states.map(state => ({
        text: state.name,
        value: state.id
      }))
    },
    cityOptions() {
      if (!this.filters.state_id) return []
      return this.cities
        .filter(city => city.state_id === this.filters.state_id)
        .map(city => ({
          text: city.name,
          value: city.id
        }))
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
    debounceSearch() {
      clearTimeout(this.searchTimeout)
      this.searchTimeout = setTimeout(() => {
        this.applyFilters()
      }, 500)
    },
    applyFilters() {
      router.get('/parcels', this.filters, {
        preserveState: true,
        preserveScroll: true,
        onStart: () => { this.loading = true },
        onFinish: () => { this.loading = false }
      })
    },
    clearFilters() {
      this.filters = {
        search: '',
        status: null,
        state_id: null,
        city_id: null
      }
      this.applyFilters()
    },
    onStateChange() {
      this.filters.city_id = null
      this.applyFilters()
    },
    updateOptions(options) {
      this.options = options
      router.get('/parcels', {
        ...this.filters,
        page: options.page,
        per_page: options.itemsPerPage,
        sort_by: options.sortBy[0]?.key,
        sort_desc: options.sortBy[0]?.order === 'desc'
      }, {
        preserveState: true,
        preserveScroll: true,
        onStart: () => { this.loading = true },
        onFinish: () => { this.loading = false }
      })
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

.simple-table >>> .v-data-table .v-data-table-footer {
  background: #fafafa !important;
  border-top: 1px solid #e0e0e0 !important;
}

/* Dropdown menu styling */
.v-menu__content {
  border-radius: 8px !important;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
  border: 1px solid #e0e0e0 !important;
  max-height: 300px !important;
  overflow-y: auto !important;
}

.v-list {
  padding: 4px 0 !important;
}

.v-list-item {
  transition: background-color 0.2s ease !important;
  padding: 8px 16px !important;
  min-height: 40px !important;
}

.v-list-item:hover {
  background-color: #f5f5f5 !important;
}

.v-list-item--active {
  background-color: #e3f2fd !important;
  color: #1976d2 !important;
}

.v-list-item--active:hover {
  background-color: #bbdefb !important;
}

/* Input field styling improvements */
.v-text-field--outlined .v-input__control .v-input__slot {
  border-radius: 8px !important;
}

.v-select--outlined .v-input__control .v-input__slot {
  border-radius: 8px !important;
}

.v-text-field--outlined fieldset {
  border-radius: 8px !important;
}

.v-select--outlined fieldset {
  border-radius: 8px !important;
}
</style>