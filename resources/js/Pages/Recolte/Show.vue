<template>
  <div>
    <AppLayout>
      <Head :title="`Recolte #${recolte.code}`" />
      
      <template #title>
        <span style="background: linear-gradient(135deg, #1976d2, #1565c0); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-weight: 600;">
          Collection Transfer #{{ recolte.code }}
        </span>
      </template>
      
      <template #content>
        
        <v-btn color="primary" @click="$inertia.visit(`/recoltes/${recolte.id}/edit`)">
          <v-icon left>mdi-pencil</v-icon>
          Edit Recolte
        </v-btn>

        <v-btn color="grey" @click="$inertia.visit('/recoltes')" class="ml-2">
          <v-icon left>mdi-arrow-left</v-icon>
          Back to Recoltes
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
          <v-row>
            <!-- Recolte Information -->
            <v-col cols="12" md="6">
              <v-card>
                <v-card-title class="primary white--text">
                  <v-icon left color="white">mdi-cash-multiple</v-icon>
                  Collection Transfer Details
                </v-card-title>
                <v-card-text>
                  <v-list>
                    <v-list-item>
                      <v-list-item-content>
                        <v-list-item-title>Code</v-list-item-title>
                        <v-list-item-subtitle>{{ recolte.code }}</v-list-item-subtitle>
                      </v-list-item-content>
                    </v-list-item>
                    <v-list-item v-if="recolte.note">
                      <v-list-item-content>
                        <v-list-item-title>Note</v-list-item-title>
                        <v-list-item-subtitle>{{ recolte.note }}</v-list-item-subtitle>
                      </v-list-item-content>
                    </v-list-item>
                    <v-list-item>
                      <v-list-item-content>
                        <v-list-item-title>Created At</v-list-item-title>
                        <v-list-item-subtitle>{{ formatDate(recolte.created_at) }}</v-list-item-subtitle>
                      </v-list-item-content>
                    </v-list-item>
                    <v-list-item>
                      <v-list-item-content>
                        <v-list-item-title>Total Collections</v-list-item-title>
                        <v-list-item-subtitle>{{ recolte.collections?.length || 0 }}</v-list-item-subtitle>
                      </v-list-item-content>
                    </v-list-item>
                    <v-list-item>
                      <v-list-item-content>
                        <v-list-item-title>Total Amount</v-list-item-title>
                        <v-list-item-subtitle>{{ totalAmount }} Da</v-list-item-subtitle>
                      </v-list-item-content>
                    </v-list-item>
                  </v-list>
                </v-card-text>
              </v-card>
            </v-col>

            <!-- Collections List -->
            <v-col cols="12" md="6">
              <v-card>
                <v-card-title class="success white--text">
                  <v-icon left color="white">mdi-package-variant-closed</v-icon>
                  Collections ({{ recolte.collections?.length || 0 }})
                </v-card-title>
                <v-card-text>
                  <div v-if="recolte.collections && recolte.collections.length > 0" style="max-height: 400px; overflow-y: auto;">
                    <v-list>
                      <v-list-item
                        v-for="collection in recolte.collections"
                        :key="collection.id"
                        class="mb-2"
                      >
                        <v-list-item-content>
                          <v-list-item-title>
                            {{ collection.parcel?.tracking_number || 'N/A' }}
                          </v-list-item-title>
                          <v-list-item-subtitle>
                            Client: {{ collection.parcel?.client_name || 'N/A' }}
                          </v-list-item-subtitle>
                          <v-list-item-subtitle>
                            Amount: {{ collection.amount || '0.00' }} Da
                          </v-list-item-subtitle>
                          <v-list-item-subtitle>
                            Collected: {{ formatDate(collection.collected_at) }}
                          </v-list-item-subtitle>
                        </v-list-item-content>
                        <v-list-item-action>
                          <v-chip color="success" small>
                            {{ collection.amount || '0.00' }} Da
                          </v-chip>
                        </v-list-item-action>

                      </v-list-item>
                    </v-list>
                  </div>
                  <div v-else class="text-center py-8">
                    <v-icon size="64" color="grey lighten-2">mdi-package-variant-closed</v-icon>
                    <p class="text-h6 text--secondary mt-2">No collections</p>
                    <p class="text-body-2 text--secondary">
                      This recolte has no collections associated with it.
                    </p>
                  </div>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>
        </v-container>
      </v-main>

      </template>
    </AppLayout>
  </div>
</template>

<script>
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

export default {
  name: 'RecolteShow',
  components: {
    Head,
    AppLayout
  },
  props: {
    recolte: {
      type: Object,
      required: true
    },
    errors: {
      type: Object,
      default: () => ({})
    }
  },
  computed: {
    totalAmount() {
      if (!this.recolte.collections) return '0.00'
      
      const total = this.recolte.collections.reduce((sum, collection) => {
        return sum + parseFloat(collection.amount || 0)
      }, 0)
      
      return total.toFixed(2)
    }
  },
  methods: {
    formatDate(date) {
      if (!date) return 'N/A'
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    },
    logout() {
      this.$inertia.post('/logout')
    }
  }
}
</script>

<style scoped>
.collection-card {
  border: 2px solid transparent;
  transition: all 0.2s ease;
}

.collection-card:hover {
  border-color: #1976d2;
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.collection-card.selected {
  border-color: #1976d2;
  background-color: #e3f2fd;
}
</style>