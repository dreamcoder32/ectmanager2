<template>
  <AppLayout>
    <Head title="WhatsApp Management" />
    
    <template #title>
      <div class="d-flex justify-space-between align-center">
        <span class="text-h4 font-weight-bold">
          WhatsApp Management
        </span>
        <v-btn
          color="primary"
          @click="showBulkMessageDialog = true"
          :disabled="selectedParcels.length === 0"
        >
          <v-icon left>mdi-message-multiple</v-icon>
          Send Bulk Messages ({{ selectedParcels.length }})
        </v-btn>
      </div>
    </template>
    
    <template #content>
      <v-container fluid>
        <!-- Filters -->
        <v-card class="mb-4" elevation="2">
          <v-card-text>
            <v-row>
              <v-col cols="12" md="3">
                <v-select
                  v-model="filters.status"
                  :items="statusOptions"
                  label="Status"
                  clearable
                  @update:model-value="applyFilters"
                ></v-select>
              </v-col>
              <v-col cols="12" md="3">
                <v-select
                  v-model="filters.company_id"
                  :items="companies"
                  item-title="name"
                  item-value="id"
                  label="Company"
                  clearable
                  @update:model-value="applyFilters"
                ></v-select>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field
                  v-model="filters.search"
                  label="Search by tracking number, name, or phone"
                  prepend-inner-icon="mdi-magnify"
                  clearable
                  @keyup.enter="applyFilters"
                ></v-text-field>
              </v-col>
            <v-col cols="12" md="2">
                <v-btn
                  color="primary"
                  @click="applyFilters"
                  block
                >
                  <v-icon left>mdi-filter</v-icon>
                  Filter
                </v-btn>
              </v-col>
              <v-col cols="12" md="2">
                <v-btn
                  color="success"
                  @click="showBulkVerifyDialog = true"
                  :disabled="selectedParcels.length === 0"
                  block
                >
                  <v-icon left>mdi-phone-check</v-icon>
                  Verify Phones ({{ selectedParcels.length }})
                </v-btn>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>

        <!-- Parcels Table -->
        <v-card elevation="2">
          <v-card-title>
            <span>Parcels</span>
            <v-spacer></v-spacer>
            <v-text-field
              v-model="selectAll"
              type="checkbox"
              label="Select All"
              @change="toggleSelectAll"
            ></v-text-field>
          </v-card-title>
          
          <v-data-table
            :headers="headers"
            :items="parcels.data"
            :loading="loading"
            :items-per-page="15"
            class="elevation-1"
            show-select
            v-model="selectedParcels"
            item-key="id"
          >
            <template v-slot:item.company.name="{ item }">
              <v-chip
                :color="getCompanyColor(item.company)"
                text-color="white"
                small
              >
                {{ item.company.name }}
              </v-chip>
            </template>

            <template v-slot:item.status="{ item }">
              <v-chip
                :color="getStatusColor(item.status)"
                text-color="white"
                small
              >
                {{ item.status }}
              </v-chip>
            </template>

            <template v-slot:item.delivery_type="{ item }">
              <v-chip
                :color="item.delivery_type === 'stopdesk' ? 'orange' : 'green'"
                text-color="white"
                small
              >
                {{ item.delivery_type === 'stopdesk' ? 'Stop Desk' : 'Home Delivery' }}
              </v-chip>
            </template>

            <template v-slot:item.has_whatsapp_tag="{ item }">
              <v-icon
                :color="item.has_whatsapp_tag ? 'green' : 'grey'"
                @click="toggleWhatsAppTag(item)"
                style="cursor: pointer;"
              >
                {{ item.has_whatsapp_tag ? 'mdi-whatsapp' : 'mdi-whatsapp' }}
              </v-icon>
            </template>

            <template v-slot:item.phone_status="{ item }">
              <div class="d-flex align-center">
                <v-icon
                  :color="getPhoneStatusColor(item)"
                  size="small"
                  class="mr-1"
                >
                  {{ getPhoneStatusIcon(item) }}
                </v-icon>
                <span class="text-caption">{{ getPhoneStatusText(item) }}</span>
              </div>
            </template>

            <template v-slot:item.actions="{ item }">
              <v-btn
                icon
                size="small"
                @click="sendDeskPickupNotification(item)"
                :disabled="!isCompanyConfigured(item.company)"
                color="success"
                title="Send Desk Pickup Notification"
              >
                <v-icon>mdi-bell-ring</v-icon>
              </v-btn>
              <v-btn
                icon
                size="small"
                @click="openMessageDialog(item)"
                :disabled="!isCompanyConfigured(item.company)"
              >
                <v-icon>mdi-message</v-icon>
              </v-btn>
              <v-btn
                icon
                size="small"
                @click="viewMessageHistory(item)"
              >
                <v-icon>mdi-history</v-icon>
              </v-btn>
            </template>
          </v-data-table>

          <!-- Pagination -->
          <v-pagination
            v-model="currentPage"
            :length="parcels.last_page"
            @update:model-value="loadParcels"
            class="pa-4"
          ></v-pagination>
        </v-card>

        <!-- Message Dialog -->
        <v-dialog v-model="showMessageDialog" max-width="600px">
          <v-card>
            <v-card-title>
              <span class="text-h5">Send WhatsApp Message</span>
            </v-card-title>
            <v-card-text>
              <div v-if="selectedParcel">
                <v-row>
                  <v-col cols="12">
                    <v-text-field
                      label="Recipient"
                      :value="`${selectedParcel.recipient_name} (${selectedParcel.recipient_phone})`"
                      readonly
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12">
                    <v-textarea
                      v-model="messageText"
                      label="Message"
                      rows="4"
                      placeholder="Type your message here..."
                    ></v-textarea>
                  </v-col>
                </v-row>
              </div>
            </v-card-text>
            <v-card-actions>
              <v-spacer></v-spacer>
              <v-btn color="grey" text @click="closeMessageDialog">
                Cancel
              </v-btn>
              <v-btn color="primary" @click="sendMessage" :loading="sending">
                Send Message
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-dialog>

        <!-- Bulk Message Dialog -->
        <v-dialog v-model="showBulkMessageDialog" max-width="600px">
          <v-card>
            <v-card-title>
              <span class="text-h5">Send Bulk WhatsApp Messages</span>
            </v-card-title>
            <v-card-text>
              <v-alert type="info" class="mb-4">
                You are about to send messages to {{ selectedParcels.length }} parcels.
              </v-alert>
              <v-textarea
                v-model="bulkMessageText"
                label="Message"
                rows="4"
                placeholder="Type your message here..."
              ></v-textarea>
            </v-card-text>
            <v-card-actions>
              <v-spacer></v-spacer>
              <v-btn color="grey" text @click="showBulkMessageDialog = false">
                Cancel
              </v-btn>
              <v-btn color="primary" @click="sendBulkMessages" :loading="sending">
                Send to All ({{ selectedParcels.length }})
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-dialog>

        <!-- Bulk Phone Verification Dialog -->
        <v-dialog v-model="showBulkVerifyDialog" max-width="500px">
          <v-card>
            <v-card-title>
              <span class="text-h5">Verify WhatsApp Phone Numbers</span>
            </v-card-title>
            <v-card-text>
              <v-alert type="info" class="mb-4">
                You are about to verify WhatsApp phone numbers for {{ selectedParcels.length }} parcels.
                This will check if the recipient and secondary phone numbers are on WhatsApp.
              </v-alert>
              <v-alert type="warning" density="compact">
                This operation uses the WhatsApp API and may take some time to complete.
              </v-alert>
            </v-card-text>
            <v-card-actions>
              <v-spacer></v-spacer>
              <v-btn color="grey" text @click="showBulkVerifyDialog = false">
                Cancel
              </v-btn>
              <v-btn color="success" @click="bulkVerifyPhones" :loading="verifying">
                Verify All ({{ selectedParcels.length }})
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-dialog>

        <!-- Snackbar for notifications -->
        <v-snackbar
          v-model="snackbar.show"
          :color="snackbar.color"
          :timeout="snackbar.timeout"
        >
          {{ snackbar.message }}
        </v-snackbar>
      </v-container>
    </template>
  </AppLayout>
</template>

<script>
import { ref, reactive, onMounted, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'

export default {
  components: {
    AppLayout,
    Head
  },
  props: {
    parcels: Object,
    filters: Object,
    companies: Array
  },
  setup(props) {
    const loading = ref(false)
    const sending = ref(false)
    const verifying = ref(false)
    const selectedParcels = ref([])
    const showMessageDialog = ref(false)
    const showBulkMessageDialog = ref(false)
    const showBulkVerifyDialog = ref(false)
    const selectedParcel = ref(null)
    const messageText = ref('')
    const bulkMessageText = ref('')
    const currentPage = ref(1)

    const snackbar = reactive({
      show: false,
      message: '',
      color: 'success',
      timeout: 3000
    })

    const statusOptions = [
      { title: 'Pending', value: 'pending' },
      { title: 'Collected', value: 'collected' },
      { title: 'In Transit', value: 'in_transit' },
      { title: 'Delivered', value: 'delivered' },
      { title: 'Returned', value: 'returned' },
      { title: 'Cancelled', value: 'cancelled' }
    ]

    const headers = [
      { title: 'Tracking Number', key: 'tracking_number', sortable: true },
      { title: 'Company', key: 'company.name', sortable: true },
      { title: 'Recipient', key: 'recipient_name', sortable: true },
      { title: 'Phone', key: 'recipient_phone', sortable: true },
      { title: 'Status', key: 'status', sortable: true },
      { title: 'Delivery Type', key: 'delivery_type', sortable: true },
      { title: 'COD Amount', key: 'cod_amount', sortable: true },
      // { title: 'WhatsApp Tag', key: 'has_whatsapp_tag', sortable: true },
      { title: 'Phone Status', key: 'phone_status', sortable: true },
      { title: 'Actions', key: 'actions', sortable: false }
    ]

    const selectAll = computed({
      get: () => selectedParcels.value.length === props.parcels.data.length && props.parcels.data.length > 0,
      set: (value) => {
        if (value) {
          // Since item-key="id", we need to store just the IDs
          selectedParcels.value = props.parcels.data.map(p => p.id)
        } else {
          selectedParcels.value = []
        }
      }
    })


    const applyFilters = () => {
      router.get('/whatsapp', props.filters, {
        preserveState: true,
        preserveScroll: true
      })
    }

    const loadParcels = () => {
      router.get('/whatsapp', {
        ...props.filters,
        page: currentPage.value
      }, {
        preserveState: true,
        preserveScroll: true
      })
    }

    const getCompanyColor = (company) => {
      const colors = ['primary', 'secondary', 'success', 'warning', 'error', 'info']
      return colors[company.id % colors.length]
    }

    const getStatusColor = (status) => {
      const colors = {
        pending: 'orange',
        collected: 'blue',
        in_transit: 'purple',
        delivered: 'green',
        returned: 'red',
        cancelled: 'grey'
      }
      return colors[status] || 'grey'
    }

    const isCompanyConfigured = (company) => {
      return company.whatsapp_api_key && company.whatsapp_api_key.length > 0
    }

    const openMessageDialog = (parcel) => {
      selectedParcel.value = parcel
      messageText.value = ''
      showMessageDialog.value = true
    }

    const closeMessageDialog = () => {
      showMessageDialog.value = false
      selectedParcel.value = null
      messageText.value = ''
    }

    const sendMessage = async () => {
      if (!messageText.value.trim()) return

      sending.value = true
      try {
        const response = await fetch(`/whatsapp/parcels/${selectedParcel.value.id}/send-message`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          },
          body: JSON.stringify({
            message: messageText.value
          })
        })

        const result = await response.json()
        
        if (result.success) {
          showSnackbar('Message sent successfully!', 'success')
          closeMessageDialog()
        } else {
          showSnackbar(result.error || 'Failed to send message', 'error')
        }
      } catch (error) {
        showSnackbar('Error sending message', 'error')
      } finally {
        sending.value = false
      }
    }

    const sendBulkMessages = async () => {
      if (!bulkMessageText.value.trim()) return

      sending.value = true
      try {
        // selectedParcels.value contains just the IDs (because item-key="id")
        // So we can use them directly!
        const parcelIds = selectedParcels.value.filter(id => id !== null && id !== undefined)
        
        if (parcelIds.length === 0) {
          showSnackbar('No valid parcels selected for messaging', 'error')
          sending.value = false
          return
        }

        const response = await fetch('/whatsapp/send-bulk-messages', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          },
          body: JSON.stringify({
            parcel_ids: parcelIds,
            message: bulkMessageText.value
          })
        })

        const result = await response.json()
        
        showSnackbar(
          `Bulk messages sent! Success: ${result.success}, Failed: ${result.failed}`,
          result.failed === 0 ? 'success' : 'warning'
        )
        
        showBulkMessageDialog.value = false
        bulkMessageText.value = ''
        selectedParcels.value = []
      } catch (error) {
        showSnackbar('Error sending bulk messages', 'error')
      } finally {
        sending.value = false
      }
    }

    const toggleWhatsAppTag = async (parcel) => {
      try {
        const response = await fetch(`/whatsapp/parcels/${parcel.id}/toggle-tag`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          }
        })

        const result = await response.json()
        
        if (result.success) {
          parcel.has_whatsapp_tag = result.has_whatsapp_tag
          showSnackbar(
            `WhatsApp tag ${result.has_whatsapp_tag ? 'added' : 'removed'}`,
            'success'
          )
        }
      } catch (error) {
        showSnackbar('Error updating WhatsApp tag', 'error')
      }
    }

    const viewMessageHistory = (parcel) => {
      router.visit(`/whatsapp/parcels/${parcel.id}/messages`)
    }

    const sendDeskPickupNotification = async (parcel) => {
      if (!confirm(`Send desk pickup notification to ${parcel.recipient_name}?`)) {
        return
      }

      sending.value = true
      try {
        const response = await fetch(`/whatsapp/parcels/${parcel.id}/desk-pickup-notification`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          }
        })

        const result = await response.json()
        
        if (result.success) {
          showSnackbar('Desk pickup notification sent successfully!', 'success')
        } else {
          showSnackbar(`Failed to send notification: ${result.error}`, 'error')
        }
      } catch (error) {
        showSnackbar('Error sending desk pickup notification', 'error')
      } finally {
        sending.value = false
      }
    }

    const toggleSelectAll = () => {
      if (selectAll.value) {
        // Since item-key="id", we need to store just the IDs
        selectedParcels.value = props.parcels.data
          .filter(parcel => parcel.id !== null && parcel.id !== undefined)
          .map(parcel => parcel.id)
      } else {
        selectedParcels.value = []
      }
    }

    const showSnackbar = (message, color = 'success') => {
      snackbar.message = message
      snackbar.color = color
      snackbar.show = true
    }

    const bulkVerifyPhones = async () => {
      verifying.value = true
      try {
        // selectedParcels.value contains just the IDs (because item-key="id")
        // So we can use them directly!
        const parcelIds = selectedParcels.value.filter(id => id !== null && id !== undefined)
        
        if (parcelIds.length === 0) {
          showSnackbar('No valid parcels selected for verification', 'error')
          verifying.value = false
          return
        }

        const response = await fetch('/whatsapp/bulk-verify-phones', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          },
          body: JSON.stringify({
            parcel_ids: parcelIds
          })
        })

        const result = await response.json()
        
        showSnackbar(
          `Phone verification completed! Verified: ${result.data.verified}, Failed: ${result.data.failed}`,
          result.data.failed === 0 ? 'success' : 'warning'
        )
        
        showBulkVerifyDialog.value = false
        selectedParcels.value = []
        
        // Reload the page to show updated phone status
        router.reload()
      } catch (error) {
        showSnackbar('Error verifying phone numbers', 'error')
      } finally {
        verifying.value = false
      }
    }

    const getPhoneStatusColor = (parcel) => {
      if (!parcel.whatsapp_verified_at) return 'grey'
      if (parcel.recipient_phone_whatsapp || parcel.secondary_phone_whatsapp) return 'green'
      return 'red'
    }

    const getPhoneStatusIcon = (parcel) => {
      if (!parcel.whatsapp_verified_at) return 'mdi-help-circle'
      if (parcel.recipient_phone_whatsapp || parcel.secondary_phone_whatsapp) return 'mdi-check-circle'
      return 'mdi-close-circle'
    }

    const getPhoneStatusText = (parcel) => {
      if (!parcel.whatsapp_verified_at) return 'Not Verified'
      if (parcel.recipient_phone_whatsapp && parcel.secondary_phone_whatsapp) return 'Both on WA'
      if (parcel.recipient_phone_whatsapp) return 'Primary on WA'
      if (parcel.secondary_phone_whatsapp) return 'Secondary on WA'
      return 'Not on WA'
    }

    return {
      loading,
      sending,
      verifying,
      selectedParcels,
      showMessageDialog,
      showBulkMessageDialog,
      showBulkVerifyDialog,
      selectedParcel,
      messageText,
      bulkMessageText,
      currentPage,
      snackbar,
      statusOptions,
      headers,
      selectAll,
      applyFilters,
      loadParcels,
      getCompanyColor,
      getStatusColor,
      isCompanyConfigured,
      openMessageDialog,
      closeMessageDialog,
      sendMessage,
      sendBulkMessages,
      bulkVerifyPhones,
      getPhoneStatusColor,
      getPhoneStatusIcon,
      getPhoneStatusText,
      toggleWhatsAppTag,
      viewMessageHistory,
      sendDeskPickupNotification,
      toggleSelectAll,
      showSnackbar
    }
  }
}
</script>
