<template>
  <div>
    <AppLayout>
      <Head :title="`Edit Recolte #${recolte.code}`" />
      
      <template #title>
        <span style="background: linear-gradient(135deg, #1976d2, #1565c0); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-weight: 600;">
          Edit Collection Transfer #{{ recolte.code }}
        </span>
      </template>
      
      <template #content>
        
        <v-btn color="info" @click="$inertia.visit(`/recoltes/${recolte.id}`)">
          <v-icon left>mdi-eye</v-icon>
          View Recolte
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
          <v-form @submit.prevent="submit">
            <v-row>
              <!-- Recolte Information -->
              <v-col cols="12" md="6">
                <v-card>
                  <v-card-title class="primary white--text">
                    <v-icon left color="white">mdi-cash-multiple</v-icon>
                    Collection Transfer Details
                  </v-card-title>
                  <v-card-text>
                    <v-text-field
                      v-model="form.code"
                      label="Code"
                      :error-messages="errors.code"
                      outlined
                      dense
                      readonly
                    ></v-text-field>

                    <v-textarea
                      v-model="form.note"
                      label="Note"
                      :error-messages="errors.note"
                      outlined
                      dense
                      rows="3"
                      placeholder="Add any notes about this collection transfer..."
                    ></v-textarea>

                    <div class="d-flex justify-end mt-4">
                      <v-btn
                        type="submit"
                        color="primary"
                        :loading="form.processing"
                        :disabled="form.processing"
                      >
                        <v-icon left>mdi-content-save</v-icon>
                        Update Recolte
                      </v-btn>
                    </div>
                  </v-card-text>
                </v-card>
              </v-col>

              <!-- Collections Management -->
              <v-col cols="12" md="6">
                <v-card>
                  <v-card-title class="success white--text">
                    <v-icon left color="white">mdi-package-variant-closed</v-icon>
                    Manage Collections
                  </v-card-title>
                  <v-card-text>
                    <!-- Current Collections -->
                    <div class="mb-4">
                      <h4 class="text-h6 mb-2">Current Collections ({{ currentCollections.length }})</h4>
                      <div v-if="currentCollections.length > 0" style="max-height: 200px; overflow-y: auto;">
                        <v-list dense>
                          <v-list-item
                            v-for="collection in currentCollections"
                            :key="collection.id"
                            class="mb-1"
                          >
                            <v-list-item-content>
                              <v-list-item-title class="text-body-2">
                                {{ collection.parcel?.tracking_number || 'N/A' }}
                              </v-list-item-title>
                              <v-list-item-subtitle class="text-caption">
                                {{ collection.parcel?.client_name || 'N/A' }} - {{ collection.amount || '0.00' }} Da
                              </v-list-item-subtitle>
                            </v-list-item-content>
                            <v-list-item-action>
                              <v-btn
                                icon
                                small
                                color="error"
                                @click="removeCollection(collection.id)"
                              >
                                <v-icon small>mdi-close</v-icon>
                              </v-btn>
                            </v-list-item-action>
                          </v-list-item>
                        </v-list>
                      </div>
                      <div v-else class="text-center py-4">
                        <v-icon color="grey lighten-2">mdi-package-variant-closed</v-icon>
                        <p class="text-caption text--secondary">No collections</p>
                      </div>
                    </div>

                    <!-- Add New Collections -->
                    <div>
                      <h4 class="text-h6 mb-2">Add Collections</h4>
                      <v-text-field
                        v-model="searchQuery"
                        label="Search available collections..."
                        prepend-inner-icon="mdi-magnify"
                        outlined
                        dense
                        clearable
                      ></v-text-field>

                      <div v-if="filteredAvailableCollections.length > 0" style="max-height: 200px; overflow-y: auto;">
                        <v-list dense>
                          <v-list-item
                            v-for="collection in filteredAvailableCollections"
                            :key="collection.id"
                            class="mb-1"
                          >
                            <v-list-item-content>
                              <v-list-item-title class="text-body-2">
                                {{ collection.parcel?.tracking_number || 'N/A' }}
                              </v-list-item-title>
                              <v-list-item-subtitle class="text-caption">
                                {{ collection.parcel?.client_name || 'N/A' }} - {{ collection.amount || '0.00' }} Da
                              </v-list-item-subtitle>
                            </v-list-item-content>
                            <v-list-item-action>
                              <v-btn
                                icon
                                small
                                color="success"
                                @click="addCollection(collection.id)"
                              >
                                <v-icon small>mdi-plus</v-icon>
                              </v-btn>
                            </v-list-item-action>
                          </v-list-item>
                        </v-list>
                      </div>
                      <div v-else class="text-center py-4">
                        <v-icon color="grey lighten-2">mdi-package-variant-closed</v-icon>
                        <p class="text-caption text--secondary">
                          {{ searchQuery ? 'No matching collections' : 'No available collections' }}
                        </p>
                      </div>
                    </div>

                    <!-- Total Amount -->
                    <v-divider class="my-4"></v-divider>
                    <div class="d-flex justify-between align-center">
                      <span class="text-h6">Total Amount:</span>
                      <span class="text-h6 primary--text">{{ totalAmount }} Da</span>
                    </div>
                  </v-card-text>
                </v-card>
              </v-col>
            </v-row>
          </v-form>
        </v-container>
      </v-main>

      </template>
    </AppLayout>
  </div>
</template>

<script>
import { Head } from '@inertiajs/vue3'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

export default {
  name: 'RecolteEdit',
  components: {
    Head,
    AppLayout
  },
  props: {
    recolte: {
      type: Object,
      required: true
    },
    availableCollections: {
      type: Array,
      required: true
    },
    errors: {
      type: Object,
      default: () => ({})
    }
  },
  data() {
    return {
      searchQuery: '',
      selectedCollectionIds: this.recolte.collections?.map(c => c.id) || []
    }
  },
  setup(props) {
    const form = useForm({
      code: props.recolte.code,
      note: props.recolte.note || '',
      collection_ids: props.recolte.collections?.map(c => c.id) || []
    })

    return { form }
  },
  computed: {
    currentCollections() {
      return this.recolte.collections?.filter(collection => 
        this.selectedCollectionIds.includes(collection.id)
      ) || []
    },
    filteredAvailableCollections() {
      let available = this.availableCollections.filter(collection => 
        !this.selectedCollectionIds.includes(collection.id)
      )

      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase()
        available = available.filter(collection => 
          collection.parcel?.tracking_number?.toLowerCase().includes(query) ||
          collection.parcel?.client_name?.toLowerCase().includes(query) ||
          collection.amount?.toString().includes(query)
        )
      }

      return available
    },
    totalAmount() {
      const total = this.currentCollections.reduce((sum, collection) => {
        return sum + parseFloat(collection.amount || 0)
      }, 0)
      
      return total.toFixed(2)
    }
  },
  methods: {
    addCollection(collectionId) {
      if (!this.selectedCollectionIds.includes(collectionId)) {
        this.selectedCollectionIds.push(collectionId)
        this.form.collection_ids = [...this.selectedCollectionIds]
      }
    },
    removeCollection(collectionId) {
      const index = this.selectedCollectionIds.indexOf(collectionId)
      if (index > -1) {
        this.selectedCollectionIds.splice(index, 1)
        this.form.collection_ids = [...this.selectedCollectionIds]
      }
    },
    submit() {
      this.form.put(`/recoltes/${this.recolte.id}`, {
        preserveScroll: true,
        onSuccess: () => {
          // Handle success
        },
        onError: (errors) => {
          // Handle errors
          console.error('Update failed:', errors)
        }
      })
    },
    logout() {
      this.$inertia.post('/logout')
    }
  }
}
</script>

<style scoped>
.collection-item {
  border: 1px solid #e0e0e0;
  border-radius: 4px;
  transition: all 0.2s ease;
}

.collection-item:hover {
  border-color: #1976d2;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
</style>