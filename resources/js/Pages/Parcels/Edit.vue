<template>
  <AppLayout>
    <Head :title="`Edit Parcel #${parcel.tracking_number}`" />
    
    <template #title>
      <span style="background: linear-gradient(135deg, #1976d2, #1565c0); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-weight: 600;">
        Edit Parcel #{{ parcel.tracking_number }}
      </span>
    </template>
    
    <template #content>
      
      <v-btn color="info" @click="$inertia.visit(`/parcels/${parcel.id}`)">
        <v-icon left>mdi-eye</v-icon>
        View Parcel
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
    </v-app-bar>

    <!-- Main Content -->
    <v-main>
      <v-container fluid>
        <v-form @submit.prevent="submit">
          <v-row>
            <!-- Parcel Information -->
            <v-col cols="12" md="6">
              <v-card>
                <v-card-title class="primary white--text">
                  <v-icon left color="white">mdi-package-variant</v-icon>
                  Parcel Information
                </v-card-title>
                <v-card-text>
                  <v-text-field
                    v-model="form.tracking_number"
                    :label="$t('parcels.tracking_number')"
                    :error-messages="errors.tracking_number"
                    outlined
                    dense
                    readonly
                  ></v-text-field>

                  <v-text-field
                    v-model="form.weight"
                    :label="$t('parcels.weight')"
                    :error-messages="errors.weight"
                    type="number"
                    step="0.01"
                    suffix="kg"
                    outlined
                    dense
                  ></v-text-field>

                  <v-text-field
                    v-model="form.declared_value"
                    :label="$t('parcels.declared_value')"
                    :error-messages="errors.declared_value"
                    type="number"
                    step="0.01"
                    prefix="$"
                    outlined
                    dense
                  ></v-text-field>

                  <v-text-field
                    v-model="form.cod_amount"
                    label="COD Amount"
                    :error-messages="errors.cod_amount"
                    type="number"
                    step="0.01"
                    prefix="$"
                    outlined
                    dense
                  ></v-text-field>

                  <v-select
                    v-model="form.status"
                    :items="statusOptions"
                    label="Status"
                    :error-messages="errors.status"
                    outlined
                    dense
                  ></v-select>

                  <v-textarea
                    v-model="form.notes"
                    label="Notes"
                    :error-messages="errors.notes"
                    outlined
                    dense
                    rows="3"
                  ></v-textarea>
                </v-card-text>
              </v-card>
            </v-col>

            <!-- Sender Information -->
            <v-col cols="12" md="6">
              <v-card>
                <v-card-title class="success white--text">
                  <v-icon left color="white">mdi-account-arrow-right</v-icon>
                  Sender Information
                </v-card-title>
                <v-card-text>
                  <v-text-field
                    v-model="form.sender_name"
                    :label="$t('parcels.sender_name')"
                    :error-messages="errors.sender_name"
                    outlined
                    dense
                  ></v-text-field>

                  <v-text-field
                    v-model="form.sender_phone"
                    :label="$t('parcels.sender_phone')"
                    :error-messages="errors.sender_phone"
                    outlined
                    dense
                  ></v-text-field>

                  <v-textarea
                    v-model="form.sender_address"
                    :label="$t('parcels.sender_address')"
                    :error-messages="errors.sender_address"
                    outlined
                    dense
                    rows="3"
                  ></v-textarea>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>

          <v-row class="mt-4">
            <!-- Recipient Information -->
            <v-col cols="12" md="6">
              <v-card>
                <v-card-title class="info white--text">
                  <v-icon left color="white">mdi-account-arrow-left</v-icon>
                  Recipient Information
                </v-card-title>
                <v-card-text>
                  <v-text-field
                    v-model="form.recipient_name"
                    :label="$t('parcels.receiver_name')"
                    :error-messages="errors.recipient_name"
                    outlined
                    dense
                  ></v-text-field>

                  <v-text-field
                    v-model="form.recipient_phone"
                    :label="$t('parcels.receiver_phone')"
                    :error-messages="errors.recipient_phone"
                    outlined
                    dense
                  ></v-text-field>

                  <v-textarea
                    v-model="form.recipient_address"
                    :label="$t('parcels.receiver_address')"
                    :error-messages="errors.recipient_address"
                    outlined
                    dense
                    rows="3"
                  ></v-textarea>
                </v-card-text>
              </v-card>
            </v-col>

            <!-- Location Information -->
            <v-col cols="12" md="6">
              <v-card>
                <v-card-title class="warning white--text">
                  <v-icon left color="white">mdi-map-marker</v-icon>
                  Location Information
                </v-card-title>
                <v-card-text>
                  <v-select
                    v-model="form.state_id"
                    :items="stateOptions"
                    :label="$t('parcels.state')"
                    :error-messages="errors.state_id"
                    outlined
                    dense
                    @change="onStateChange"
                  ></v-select>

                  <v-select
                    v-model="form.city_id"
                    :items="cityOptions"
                    :label="$t('parcels.city')"
                    :error-messages="errors.city_id"
                    outlined
                    dense
                    :disabled="!form.state_id"
                  ></v-select>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>

          <!-- Action Buttons -->
          <v-row class="mt-6">
            <v-col cols="12" class="text-right">
              <v-btn
                color="grey"
                @click="$inertia.visit('/parcels')"
                class="mr-2"
              >
                Cancel
              </v-btn>
              <v-btn
                color="primary"
                type="submit"
                :loading="processing"
              >
                <v-icon left>mdi-content-save</v-icon>
                Update Parcel
              </v-btn>
            </v-col>
          </v-row>
        </v-form>
      </v-container>
    </template>
  </AppLayout>
</template>

<script>
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

export default {
  name: 'ParcelEdit',
  components: {
    AppLayout
  },
  props: {
    parcel: Object,
    states: Array,
    cities: Array,
    errors: Object
  },
  data() {
    return {
      processing: false,
      form: {
        tracking_number: this.parcel.tracking_number,
        sender_name: this.parcel.sender_name,
        sender_phone: this.parcel.sender_phone,
        sender_address: this.parcel.sender_address,
        recipient_name: this.parcel.recipient_name,
        recipient_phone: this.parcel.recipient_phone,
        recipient_address: this.parcel.recipient_address,
        state_id: this.parcel.state_id,
        city_id: this.parcel.city_id,
        weight: this.parcel.weight,
        declared_value: this.parcel.declared_value,
        cod_amount: this.parcel.cod_amount,
        status: this.parcel.status,
        notes: this.parcel.notes
      }
    }
  },
  computed: {
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
      if (!this.form.state_id) return []
      return this.cities
        .filter(city => city.state_id == this.form.state_id)
        .map(city => ({
          text: city.name,
          value: city.id
        }))
    }
  },
  methods: {
    submit() {
      this.processing = true
      router.put(`/parcels/${this.parcel.id}`, this.form, {
        onFinish: () => {
          this.processing = false
        },
        onSuccess: () => {
          // Redirect will be handled by the server
        }
      })
    },
    onStateChange() {
      this.form.city_id = null
    },
    logout() {
      router.post('/logout')
    }
  }
}
</script>