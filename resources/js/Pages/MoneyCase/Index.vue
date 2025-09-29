<template>
  <AppLayout>
    <Head title="Money Cases" />
    
    <template #title>
      <div class="d-flex justify-space-between align-center">
        <span class="text-h4 font-weight-bold" 
              style="background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
                     -webkit-background-clip: text;
                     -webkit-text-fill-color: transparent;
                     background-clip: text;">
          Money Cases
        </span>
        <v-btn
          color="primary"
          href="/money-cases/create"
          prepend-icon="mdi-plus"
          style="font-weight: 600; border-radius: 12px;"
          elevation="2"
        >
          <v-icon left>mdi-plus</v-icon>
          Create New Case
        </v-btn>
      </div>
    </template>
    
    <template #content>
      <v-container fluid>
        <!-- Data Table -->
        <v-card elevation="1" style="border-radius: 8px; background: white;">
          <v-card-title class="pa-4" style="background: #f5f5f5; border-bottom: 1px solid #e0e0e0;">
            <v-icon left color="#666" size="24">mdi-wallet</v-icon>
            <span class="text-h6 font-weight-medium" style="color: #333;">Money Cases Management</span>
          </v-card-title>
          
          <v-data-table
            :headers="headers"
            :items="moneyCases.data"
            :loading="loading"
            :server-items-length="moneyCases.total"
            :options.sync="options"
            :footer-props="{
              'items-per-page-options': [10, 25, 50, 100]
            }"
            class="simple-table"
            item-key="id"
          >
            <!-- Name Column -->
            <template v-slot:[`item.name`]="{ item }">
              <div class="d-flex align-center">
                <v-icon color="primary" class="mr-2">mdi-wallet</v-icon>
                <span class="font-weight-medium">{{ item.name }}</span>
              </div>
            </template>

            <!-- Description Column -->
            <template v-slot:[`item.description`]="{ item }">
              <span v-if="item.description" class="text-body-2">{{ item.description }}</span>
              <span v-else class="text-body-2 text--secondary">No description</span>
            </template>

            <!-- Balance Column -->
            <template v-slot:[`item.calculated_balance`]="{ item }">
              <v-chip
                :color="item.calculated_balance >= 0 ? 'success' : 'error'"
                text-color="white"
                small
              >
                {{ formatCurrency(item.calculated_balance) }}
              </v-chip>
            </template>

            <!-- Status Column -->
            <template v-slot:[`item.status`]="{ item }">
              <v-chip
                :color="item.status === 'active' ? 'success' : 'warning'"
                text-color="white"
                small
              >
                {{ item.status }}
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
                  <v-list-item @click="viewCase(item.id)">
                    <template v-slot:prepend>
                      <v-icon color="primary">mdi-eye</v-icon>
                    </template>
                    <v-list-item-title>View</v-list-item-title>
                  </v-list-item>
                  <v-list-item @click="editCase(item.id)">
                    <template v-slot:prepend>
                      <v-icon color="warning">mdi-pencil</v-icon>
                    </template>
                    <v-list-item-title>Edit</v-list-item-title>
                  </v-list-item>
                  <v-divider></v-divider>
                  <v-list-item @click="deleteCase(item)" class="text-error">
                    <template v-slot:prepend>
                      <v-icon color="error">mdi-delete</v-icon>
                    </template>
                    <v-list-item-title>Delete</v-list-item-title>
                  </v-list-item>
                </v-list>
              </v-menu>
            </template>
          </v-data-table>
        </v-card>
      </v-container>

      <!-- Delete Confirmation Dialog -->
      <v-dialog v-model="deleteDialog" max-width="400">
        <v-card style="border-radius: 12px;">
          <v-card-title class="text-h6 font-weight-bold">
            <v-icon color="error" class="mr-2">mdi-alert-circle</v-icon>
            Confirm Delete
          </v-card-title>
          <v-card-text>
            Are you sure you want to delete the case "{{ selectedCase?.name }}"? This action cannot be undone.
          </v-card-text>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn text @click="deleteDialog = false">Cancel</v-btn>
            <v-btn color="error" @click="confirmDelete" :loading="deleting">Delete</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </template>
  </AppLayout>
</template>

<script>
import { Head } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

export default {
  name: 'MoneyCaseIndex',
  components: {
    Head,
    AppLayout
  },
  props: {
    moneyCases: {
      type: Object,
      required: true
    }
  },
  data() {
    return {
      loading: false,
      deleting: false,
      deleteDialog: false,
      selectedCase: null,
      options: {
        page: 1,
        itemsPerPage: 25,
        sortBy: [{ key: 'created_at', order: 'desc' }]
      },
      headers: [
        {
          title: 'Name',
          key: 'name',
          sortable: true,
          width: '200px'
        },
        {
          title: 'Description',
          key: 'description',
          sortable: false,
          width: '300px'
        },
        {
          title: 'Balance',
          key: 'calculated_balance',
          sortable: true,
          width: '150px'
        },
        {
          title: 'Status',
          key: 'status',
          sortable: true,
          width: '120px'
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
    formatCurrency(amount) {
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'DZD',
        minimumFractionDigits: 2
      }).format(amount || 0)
    },
    viewCase(id) {
      this.$inertia.visit(`/money-cases/${id}`)
    },
    editCase(id) {
      this.$inertia.visit(`/money-cases/${id}/edit`)
    },
    deleteCase(moneyCase) {
      this.selectedCase = moneyCase
      this.deleteDialog = true
    },
    confirmDelete() {
      if (!this.selectedCase) return
      
      this.deleting = true
      router.delete(`/money-cases/${this.selectedCase.id}`, {
        onSuccess: () => {
          this.deleteDialog = false
          this.selectedCase = null
          this.deleting = false
        },
        onError: () => {
          this.deleting = false
        }
      })
    },
    updateOptions(options) {
      this.options = options
      router.get('/money-cases', {
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