<template>
  <AppLayout>
    <Head :title="`Parcel #${parcel.tracking_number}`" />
    
    <template #title>
      <span style="background: linear-gradient(135deg, #1976d2, #1565c0); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-weight: 600;">
        Parcel #{{ parcel.tracking_number }}
      </span>
    </template>
    
    <template #content>
      
      <v-btn color="primary" @click="$inertia.visit(`/parcels/${parcel.id}/edit`)">
        <v-icon left>mdi-pencil</v-icon>
        Edit Parcel
      </v-btn>

      <v-btn color="grey" @click="$inertia.visit('/parcels')" class="ml-2">
        <v-icon left>mdi-arrow-left</v-icon>
        Back to Parcels
      </v-btn>

      <v-menu offset-y>
        <template v-slot:activator="{ on, attrs }">
          <v-btn icon v-bind="attrs" v-on="on" class="ml-2">
            <v-avatar size="32">
              <v-icon>mdi-account-circle</v-icon>
            </v-avatar>
          </v-btn>
        </template>
        <v-list>
          <v-list-item @click="$inertia.visit('/profile')">
            <v-list-item-title>Profile</v-list-item-title>
          </v-list-item>
          <v-list-item @click="logout">
            <v-list-item-title>Log Out</v-list-item-title>
          </v-list-item>
        </v-list>
      </v-menu>

    <!-- Main Content -->
    <v-main>
      <v-container fluid>
        <!-- Status Badge -->
        <v-row class="mb-4">
          <v-col cols="12">
            <v-chip
              :color="getStatusColor(parcel.status)"
              dark
              large
            >
              <v-icon left>{{ getStatusIcon(parcel.status) }}</v-icon>
              {{ getStatusLabel(parcel.status) }}
            </v-chip>
          </v-col>
        </v-row>

        <v-row>
          <!-- Sender Information -->
          <v-col cols="12" md="6">
            <v-card>
              <v-card-title class="primary white--text">
                <v-icon left color="white">mdi-account-arrow-right</v-icon>
                Sender Information
              </v-card-title>
              <v-card-text>
                <v-list>
                  <v-list-item>
                    <v-list-item-content>
                      <v-list-item-title>Name</v-list-item-title>
                      <v-list-item-subtitle>{{ parcel.sender_name }}</v-list-item-subtitle>
                    </v-list-item-content>
                  </v-list-item>
                  <v-list-item>
                    <v-list-item-content>
                      <v-list-item-title>Phone</v-list-item-title>
                      <v-list-item-subtitle>{{ parcel.sender_phone }}</v-list-item-subtitle>
                    </v-list-item-content>
                  </v-list-item>
                  <v-list-item>
                    <v-list-item-content>
                      <v-list-item-title>Address</v-list-item-title>
                      <v-list-item-subtitle>{{ parcel.sender_address }}</v-list-item-subtitle>
                    </v-list-item-content>
                  </v-list-item>
                </v-list>
              </v-card-text>
            </v-card>
          </v-col>

          <!-- Recipient Information -->
          <v-col cols="12" md="6">
            <v-card>
              <v-card-title class="success white--text">
                <v-icon left color="white">mdi-account-arrow-left</v-icon>
                Recipient Information
              </v-card-title>
              <v-card-text>
                <v-list>
                  <v-list-item>
                    <v-list-item-content>
                      <v-list-item-title>Name</v-list-item-title>
                      <v-list-item-subtitle>{{ parcel.recipient_name }}</v-list-item-subtitle>
                    </v-list-item-content>
                  </v-list-item>
                  <v-list-item>
                    <v-list-item-content>
                      <v-list-item-title>Phone</v-list-item-title>
                      <v-list-item-subtitle>{{ parcel.recipient_phone }}</v-list-item-subtitle>
                    </v-list-item-content>
                  </v-list-item>
                  <v-list-item>
                    <v-list-item-content>
                      <v-list-item-title>Address</v-list-item-title>
                      <v-list-item-subtitle>{{ parcel.recipient_address }}</v-list-item-subtitle>
                    </v-list-item-content>
                  </v-list-item>
                  <v-list-item>
                    <v-list-item-content>
                      <v-list-item-title>State</v-list-item-title>
                      <v-list-item-subtitle>{{ parcel.state?.name }}</v-list-item-subtitle>
                    </v-list-item-content>
                  </v-list-item>
                  <v-list-item>
                    <v-list-item-content>
                      <v-list-item-title>City</v-list-item-title>
                      <v-list-item-subtitle>{{ parcel.city?.name }}</v-list-item-subtitle>
                    </v-list-item-content>
                  </v-list-item>
                </v-list>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>

        <v-row class="mt-4">
          <!-- Parcel Details -->
          <v-col cols="12" md="6">
            <v-card>
              <v-card-title class="info white--text">
                <v-icon left color="white">mdi-package-variant</v-icon>
                Parcel Details
              </v-card-title>
              <v-card-text>
                <v-list>
                  <v-list-item>
                    <v-list-item-content>
                      <v-list-item-title>Tracking Number</v-list-item-title>
                      <v-list-item-subtitle>{{ parcel.tracking_number }}</v-list-item-subtitle>
                    </v-list-item-content>
                  </v-list-item>
                  <v-list-item>
                    <v-list-item-content>
                      <v-list-item-title>Weight</v-list-item-title>
                      <v-list-item-subtitle>{{ parcel.weight }} kg</v-list-item-subtitle>
                    </v-list-item-content>
                  </v-list-item>
                  <v-list-item>
                    <v-list-item-content>
                      <v-list-item-title>Declared Value</v-list-item-title>
                      <v-list-item-subtitle>${{ parseFloat(parcel.declared_value || 0).toFixed(2) }}</v-list-item-subtitle>
                    </v-list-item-content>
                  </v-list-item>
                  <v-list-item>
                    <v-list-item-content>
                      <v-list-item-title>COD Amount</v-list-item-title>
                      <v-list-item-subtitle>
                        <span v-if="parcel.cod_amount">${{ parseFloat(parcel.cod_amount).toFixed(2) }}</span>
                        <span v-else>No COD</span>
                      </v-list-item-subtitle>
                    </v-list-item-content>
                  </v-list-item>
                  <v-list-item v-if="parcel.notes">
                    <v-list-item-content>
                      <v-list-item-title>Notes</v-list-item-title>
                      <v-list-item-subtitle>{{ parcel.notes }}</v-list-item-subtitle>
                    </v-list-item-content>
                  </v-list-item>
                </v-list>
              </v-card-text>
            </v-card>
          </v-col>

          <!-- Tracking History -->
          <v-col cols="12" md="6">
            <v-card>
              <v-card-title class="warning white--text">
                <v-icon left color="white">mdi-timeline</v-icon>
                Tracking History
              </v-card-title>
              <v-card-text>
                <v-timeline dense>
                  <v-timeline-item
                    v-for="(event, index) in trackingHistory"
                    :key="index"
                    :color="getEventColor(event.status)"
                    small
                  >
                    <template v-slot:opposite>
                      <span class="text-caption">{{ formatDate(event.created_at) }}</span>
                    </template>
                    <v-card class="elevation-2">
                      <v-card-title class="text-h6">
                        {{ getStatusLabel(event.status) }}
                      </v-card-title>
                      <v-card-text v-if="event.notes">
                        {{ event.notes }}
                      </v-card-text>
                    </v-card>
                  </v-timeline-item>
                </v-timeline>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </v-main>
    </template>
  </AppLayout>
</template>

<script>
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

export default {
  name: 'ParcelShow',
  components: {
    AppLayout
  },
  props: {
    parcel: Object,
    trackingHistory: Array
  },
  data() {
    return {
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
    getStatusIcon(status) {
      const icons = {
        pending: 'mdi-clock-outline',
        picked_up: 'mdi-truck',
        in_transit: 'mdi-truck-fast',
        out_for_delivery: 'mdi-truck-delivery',
        delivered: 'mdi-check-circle',
        returned: 'mdi-arrow-u-left-top',
        cancelled: 'mdi-close-circle'
      }
      return icons[status] || 'mdi-help-circle'
    },
    getStatusLabel(status) {
      const labels = {
        pending: 'Pending',
        picked_up: 'Picked Up',
        in_transit: 'In Transit',
        out_for_delivery: 'Out for Delivery',
        delivered: 'Delivered',
        returned: 'Returned',
        cancelled: 'Cancelled'
      }
      return labels[status] || status
    },
    getEventColor(status) {
      return this.getStatusColor(status)
    },
    formatDate(date) {
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    },
    logout() {
      router.post('/logout')
    }
  }
}
</script>