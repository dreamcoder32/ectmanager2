<template>
  <AppLayout>
    <Head title="Parcel Messages" />
    
    <template #title>
      <div class="d-flex justify-space-between align-center">
        <span class="text-h4 font-weight-bold">
          Messages for {{ parcel.tracking_number }}
        </span>
        <v-btn
          color="primary"
          @click="showMessageDialog = true"
          :disabled="!isConfigured"
        >
          <v-icon left>mdi-message-plus</v-icon>
          Send Message
        </v-btn>
      </div>
    </template>
    
    <template #content>
      <v-container fluid>
        <!-- Parcel Info -->
        <v-card class="mb-4" elevation="2">
          <v-card-title>
            <v-icon left>mdi-package-variant</v-icon>
            Parcel Information
          </v-card-title>
          <v-card-text>
            <v-row>
              <v-col cols="12" md="3">
                <v-text-field
                  label="Tracking Number"
                  :value="parcel.tracking_number"
                  readonly
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field
                  label="Recipient"
                  :value="parcel.recipient_name"
                  readonly
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field
                  label="Phone"
                  :value="parcel.recipient_phone"
                  readonly
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field
                  label="Company"
                  :value="parcel.company.name"
                  readonly
                ></v-text-field>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>

        <!-- Messages History -->
        <v-card elevation="2">
          <v-card-title>
            <v-icon left>mdi-message-text</v-icon>
            Message History
            <v-spacer></v-spacer>
            <v-chip
              :color="isConfigured ? 'green' : 'red'"
              text-color="white"
              small
            >
              {{ isConfigured ? 'WhatsApp Configured' : 'WhatsApp Not Configured' }}
            </v-chip>
          </v-card-title>
          
          <v-card-text>
            <div v-if="messages.length === 0" class="text-center pa-8">
              <v-icon size="64" color="grey">mdi-message-text-outline</v-icon>
              <p class="text-h6 mt-4 text-grey">No messages sent yet</p>
              <p class="text-grey">Send your first message to start the conversation</p>
            </div>
            
            <div v-else>
              <v-timeline align="start" side="end">
                <v-timeline-item
                  v-for="message in messages"
                  :key="message.id"
                  :dot-color="getMessageColor(message)"
                  size="small"
                >
                  <template v-slot:icon>
                    <v-icon :color="getMessageColor(message)">
                      {{ message.message_type === 'outgoing' ? 'mdi-send' : 'mdi-receive' }}
                    </v-icon>
                  </template>
                  
                  <v-card class="mb-2" elevation="1">
                    <v-card-text>
                      <div class="d-flex justify-space-between align-center mb-2">
                        <v-chip
                          :color="message.message_type === 'outgoing' ? 'primary' : 'success'"
                          text-color="white"
                          small
                        >
                          {{ message.message_type === 'outgoing' ? 'Sent by Agent' : 'Received from Customer' }}
                        </v-chip>
                        <span class="text-caption text-grey">
                          {{ formatDate(message.sent_at) }}
                        </span>
                      </div>
                      
                      <p class="mb-2">{{ message.message_content }}</p>
                      
                      <div class="d-flex justify-space-between align-center">
                        <v-chip
                          :color="getStatusColor(message.message_status)"
                          text-color="white"
                          x-small
                        >
                          {{ message.message_status }}
                        </v-chip>
                        
                        <div class="text-caption text-grey">
                          {{ message.message_type === 'outgoing' && message.user ? 
                             `Sent by: ${message.user.display_name || message.user.email}` : 
                             `From: ${parcel.recipient_name}` }}
                        </div>
                      </div>
                      
                      <div v-if="message.error_message" class="mt-2">
                        <v-alert type="error" density="compact">
                          {{ message.error_message }}
                        </v-alert>
                      </div>
                    </v-card-text>
                  </v-card>
                </v-timeline-item>
              </v-timeline>
            </div>
          </v-card-text>
        </v-card>

        <!-- Send Message Dialog -->
        <v-dialog v-model="showMessageDialog" max-width="600px">
          <v-card>
            <v-card-title>
              <span class="text-h5">Send WhatsApp Message</span>
            </v-card-title>
            <v-card-text>
              <v-row>
                <v-col cols="12">
                  <v-text-field
                    label="Recipient"
                    :value="`${parcel.recipient_name} (${parcel.recipient_phone})`"
                    readonly
                  ></v-text-field>
                </v-col>
                <v-col cols="12">
                  <v-textarea
                    v-model="messageText"
                    label="Message"
                    rows="4"
                    placeholder="Type your message here..."
                    counter
                    maxlength="1000"
                  ></v-textarea>
                </v-col>
                <v-col cols="12">
                  <v-alert type="info" density="compact">
                    Messages are sent using the company's WhatsApp API integration.
                  </v-alert>
                </v-col>
              </v-row>
            </v-card-text>
            <v-card-actions>
              <v-spacer></v-spacer>
              <v-btn color="grey" text @click="closeMessageDialog">
                Cancel
              </v-btn>
              <v-btn color="primary" @click="sendMessage" :loading="sending" :disabled="!messageText.trim()">
                Send Message
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-dialog>

        <!-- Quick Actions -->
        <v-card class="mt-4" elevation="2">
          <v-card-title>
            <v-icon left>mdi-lightning-bolt</v-icon>
            Quick Actions
          </v-card-title>
          <v-card-text>
            <v-row>
              <v-col cols="12" md="4">
                <v-btn
                  color="success"
                  @click="sendDeliveryNotification"
                  :loading="sending"
                  :disabled="!isConfigured"
                  block
                >
                  <v-icon left>mdi-truck-delivery</v-icon>
                  Send Delivery Notification
                </v-btn>
              </v-col>
              <v-col cols="12" md="4">
                <v-btn
                  color="warning"
                  @click="sendCollectionNotification"
                  :loading="sending"
                  :disabled="!isConfigured"
                  block
                >
                  <v-icon left>mdi-cash-multiple</v-icon>
                  Send Collection Notification
                </v-btn>
              </v-col>
              <v-col cols="12" md="4">
                <v-btn
                  color="info"
                  @click="toggleWhatsAppTag"
                  block
                >
                  <v-icon left>mdi-whatsapp</v-icon>
                  {{ parcel.has_whatsapp_tag ? 'Remove' : 'Add' }} WhatsApp Tag
                </v-btn>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>

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
import { ref, reactive, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'

export default {
  components: {
    AppLayout,
    Head
  },
  props: {
    parcel: Object,
    messages: Array,
    isConfigured: Boolean
  },
  setup(props) {
    const sending = ref(false)
    const showMessageDialog = ref(false)
    const messageText = ref('')

    const snackbar = reactive({
      show: false,
      message: '',
      color: 'success',
      timeout: 3000
    })

    const formatDate = (dateString) => {
      return new Date(dateString).toLocaleString()
    }

    const getMessageColor = (message) => {
      if (message.message_status === 'failed') return 'red'
      if (message.message_type === 'outgoing') return 'primary'
      return 'secondary'
    }

    const getStatusColor = (status) => {
      const colors = {
        sent: 'blue',
        delivered: 'green',
        read: 'green',
        failed: 'red'
      }
      return colors[status] || 'grey'
    }

    const closeMessageDialog = () => {
      showMessageDialog.value = false
      messageText.value = ''
    }

    const sendMessage = async () => {
      if (!messageText.value.trim()) return

      sending.value = true
      try {
        const response = await fetch(`/whatsapp/parcels/${props.parcel.id}/send-message`, {
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
          // Reload the page to show new message
          router.reload()
        } else {
          showSnackbar(result.error || 'Failed to send message', 'error')
        }
      } catch (error) {
        showSnackbar('Error sending message', 'error')
      } finally {
        sending.value = false
      }
    }

    const sendDeliveryNotification = async () => {
      sending.value = true
      try {
        const response = await fetch(`/whatsapp/parcels/${props.parcel.id}/delivery-notification`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          }
        })

        const result = await response.json()
        
        if (result.success) {
          showSnackbar('Delivery notification sent!', 'success')
          router.reload()
        } else {
          showSnackbar(result.error || 'Failed to send delivery notification', 'error')
        }
      } catch (error) {
        showSnackbar('Error sending delivery notification', 'error')
      } finally {
        sending.value = false
      }
    }

    const sendCollectionNotification = async () => {
      sending.value = true
      try {
        const response = await fetch(`/whatsapp/parcels/${props.parcel.id}/collection-notification`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          }
        })

        const result = await response.json()
        
        if (result.success) {
          showSnackbar('Collection notification sent!', 'success')
          router.reload()
        } else {
          showSnackbar(result.error || 'Failed to send collection notification', 'error')
        }
      } catch (error) {
        showSnackbar('Error sending collection notification', 'error')
      } finally {
        sending.value = false
      }
    }

    const toggleWhatsAppTag = async () => {
      try {
        const response = await fetch(`/whatsapp/parcels/${props.parcel.id}/toggle-tag`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          }
        })

        const result = await response.json()
        
        if (result.success) {
          showSnackbar(
            `WhatsApp tag ${result.has_whatsapp_tag ? 'added' : 'removed'}`,
            'success'
          )
          // Update the parcel object
          props.parcel.has_whatsapp_tag = result.has_whatsapp_tag
        }
      } catch (error) {
        showSnackbar('Error updating WhatsApp tag', 'error')
      }
    }

    const showSnackbar = (message, color = 'success') => {
      snackbar.message = message
      snackbar.color = color
      snackbar.show = true
    }

    return {
      sending,
      showMessageDialog,
      messageText,
      snackbar,
      formatDate,
      getMessageColor,
      getStatusColor,
      closeMessageDialog,
      sendMessage,
      sendDeliveryNotification,
      sendCollectionNotification,
      toggleWhatsAppTag,
      showSnackbar
    }
  }
}
</script>
