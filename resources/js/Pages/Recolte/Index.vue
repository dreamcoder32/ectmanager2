<template>
  <div>
    <Head title="Collection Transfer" />
    
    <AppLayout>
      <template #title>
        <div class="d-flex justify-space-between align-center">
          <span class="text-h4 font-weight-bold" 
                style="background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
                       -webkit-background-clip: text;
                       -webkit-text-fill-color: transparent;
                       background-clip: text;">
            Collection Transfer
          </span>
          <v-btn
            color="primary"
            @click="$inertia.visit('/recoltes/create')"
            prepend-icon="mdi-plus"
            style="font-weight: 600; border-radius: 12px;"
            elevation="2"
          >
            <v-icon left>mdi-plus</v-icon>
            Create New Recolte
          </v-btn>
        </div>
      </template>
      
      <template #content>
        <v-container fluid>
          <!-- Recoltes Data Table -->
          <v-card 
            elevation="1"
            style="border-radius: 8px; background: white;"
          >
            <v-card-title class="pa-4" style="background: #f5f5f5; border-bottom: 1px solid #e0e0e0;">
              <v-icon left color="#666" size="24">mdi-cash-multiple</v-icon>
              <span class="text-h6 font-weight-medium" style="color: #333;">Collection Transfers</span>
            </v-card-title>
            <v-card-text class="pa-0">
              <v-data-table
                :headers="headers"
                :items="recoltes.data"
                :loading="loading"
                :server-items-length="recoltes.total"
                :options.sync="options"
                :footer-props="{
                  'items-per-page-options': [10, 25, 50, 100]
                }"
                class="simple-table"
                item-key="id"
              >
                <!-- Code Column -->
                 <template v-slot:[`item.code`]="{ item }">
                   <v-chip
                     color="primary"
                     text-color="white"
                     small
                     style="font-weight: 600;"
                   >
                     {{ item.code }}
                   </v-chip>
                 </template>

                 <!-- Note Column -->
                 <template v-slot:[`item.note`]="{ item }">
                   <span v-if="item.note" class="text-body-2">{{ item.note }}</span>
                   <span v-else class="text-body-2 text--secondary">No note</span>
                 </template>

                 <!-- Collections Count Column -->
                 <template v-slot:[`item.collections_count`]="{ item }">
                   <v-chip
                     color="success"
                     text-color="white"
                     small
                   >
                     {{ item.collections.length || 0 }} Collections
                   </v-chip>
                 </template>

                 <!-- Created At Column -->
                 <template v-slot:[`item.created_at`]="{ item }">
                   <span class="text-body-2">{{ formatDate(item.created_at) }}</span>
                 </template>

                 <!-- Actions Column -->
                 <template v-slot:[`item.actions`]="{ item }">
                   <v-menu>
                     <template v-slot:activator="{ props }">
                       <v-btn
                         icon
                         size="small"
                         v-bind="props"
                         style="border-radius: 8px;"
                       >
                         <v-icon size="20">mdi-dots-vertical</v-icon>
                       </v-btn>
                     </template>
                     <v-list style="border-radius: 8px;">
                       <v-list-item @click="viewRecolte(item.id)">
                         <template v-slot:prepend>
                           <v-icon color="primary">mdi-eye</v-icon>
                         </template>
                         <v-list-item-title>View</v-list-item-title>
                       </v-list-item>
                       <v-list-item @click="editRecolte(item.id)">
                         <template v-slot:prepend>
                           <v-icon color="warning">mdi-pencil</v-icon>
                         </template>
                         <v-list-item-title>Edit</v-list-item-title>
                       </v-list-item>
                       <v-divider></v-divider>
                       <v-list-item @click="deleteRecolte(item)" class="text-error">
                         <template v-slot:prepend>
                           <v-icon color="error">mdi-delete</v-icon>
                         </template>
                         <v-list-item-title>Delete</v-list-item-title>
                       </v-list-item>
                     </v-list>
                   </v-menu>
                 </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-container>
      </template>
    </AppLayout>

    <!-- Delete Confirmation Dialog -->
    <v-dialog v-model="deleteDialog" max-width="500px">
      <v-card style="border-radius: 12px;">
        <v-card-title class="text-h5 pa-6">
          <v-icon left color="error" size="28">mdi-alert-circle</v-icon>
          Confirm Delete
        </v-card-title>
        <v-card-text class="pa-6 pt-0">
          Are you sure you want to delete recolte <strong>{{ selectedRecolte?.code }}</strong>? This action cannot be undone.
        </v-card-text>
        <v-card-actions class="pa-6 pt-0">
          <v-spacer></v-spacer>
          <v-btn
            text
            @click="deleteDialog = false"
            style="border-radius: 8px;"
          >
            Cancel
          </v-btn>
          <v-btn
            color="error"
            @click="confirmDelete"
            style="border-radius: 8px;"
            :loading="deleting"
          >
            Delete
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script>
import { Head } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

export default {
  name: 'RecolteIndex',
  components: {
    Head,
    AppLayout
  },
  props: {
    recoltes: {
      type: Object,
      required: true
    }
  },
  data() {
    return {
      loading: false,
      deleting: false,
      deleteDialog: false,
      selectedRecolte: null,
      options: {
        page: 1,
        itemsPerPage: 25,
        sortBy: [{ key: 'created_at', order: 'desc' }]
      },
      headers: [
        {
          title: 'Code',
          key: 'code',
          sortable: true,
          width: '120px'
        },
        {
          title: 'Note',
          key: 'note',
          sortable: false,
          width: '300px'
        },
        {
          title: 'Collections',
          key: 'collections_count',
          sortable: true,
          width: '150px'
        },
        {
          title: 'Created At',
          key: 'created_at',
          sortable: true,
          width: '180px'
        },
        {
          title: 'Actions',
          key: 'actions',
          sortable: false,
          width: '100px',
          align: 'center'
        }
      ]
    }
  },
  watch: {
    options: {
      handler() {
        this.updateOptions(this.options)
      },
      deep: true
    }
  },
  methods: {
    formatDate(date) {
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    },
    viewRecolte(id) {
      this.$inertia.visit(`/recoltes/${id}`)
    },
    editRecolte(id) {
      this.$inertia.visit(`/recoltes/${id}/edit`)
    },
    deleteRecolte(recolte) {
      this.selectedRecolte = recolte
      this.deleteDialog = true
    },
    confirmDelete() {
      if (!this.selectedRecolte) return
      
      this.deleting = true
      router.delete(`/recoltes/${this.selectedRecolte.id}`, {
        onSuccess: () => {
          this.deleteDialog = false
          this.selectedRecolte = null
          this.deleting = false
        },
        onError: () => {
          this.deleting = false
        }
      })
    },
    updateOptions(options) {
      this.options = options
      router.get('/recoltes', {
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
}

.v-list-item:hover {
  background: #f5f5f5 !important;
}
</style>