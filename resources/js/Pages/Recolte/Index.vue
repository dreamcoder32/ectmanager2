<template>
  <div>
    <Head title="Collection Transfer" />
    
    <AppLayout>
 
      
      <template #content>
        <v-container fluid>
          <!-- Recoltes Data Table -->

          <div class="d-flex justify-space-between align-center mb-4">
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
            Create New Recolte
          </v-btn>
          <v-btn
            color="secondary"
            class="ml-2"
            @click="bulkExport"
            :disabled="selectedCount === 0"
            prepend-icon="mdi-file-pdf-box"
            style="font-weight: 600; border-radius: 12px;"
            elevation="2"
          >
            Resumé PDF
          </v-btn>
          <v-btn
            color="info"
            class="ml-2"
            @click="bulkExportDetailed"
            :disabled="selectedCount === 0"
            prepend-icon="mdi-file-document-multiple"
            style="font-weight: 600; border-radius: 12px;"
            elevation="2"
          >
            Bulk Detailed PDF
          </v-btn>
          <v-btn
            color="success"
            class="ml-2"
            @click="transferDialog = true"
            :disabled="selectedCount === 0"
            prepend-icon="mdi-bank-transfer"
            style="font-weight: 600; border-radius: 12px;"
            elevation="2"
          >
            Transfer
          </v-btn>
          <v-btn
            color="purple"
            class="ml-2"
            @click="$inertia.visit('/transfer-requests')"
            prepend-icon="mdi-history"
            style="font-weight: 600; border-radius: 12px;"
            elevation="2"
          >
            Transfer Requests
          </v-btn>
        </div>

        <!-- Company Filter -->
        <v-card class="mb-4" elevation="1">
          <v-card-text class="pa-4">
            <v-row>
              <!-- Company Filter -->
              <v-col cols="12" sm="6" md="3">
                <v-select
                  v-model="filters.company_id"
                  :items="companyOptions"
                  item-title="text"
                  item-value="value"
                  label="Company"
                  variant="outlined"
                  density="compact"
                  clearable
                  hide-details
                  @update:modelValue="applyFilters"
                  color="primary"
                ></v-select>
              </v-col>

              <!-- Type Filter -->
              <v-col cols="12" sm="6" md="3">
                <v-select
                  v-model="filters.type"
                  :items="[{text: 'Driver', value: 'driver'}, {text: 'Agent', value: 'agent'}]"
                  item-title="text"
                  item-value="value"
                  label="Type"
                  variant="outlined"
                  density="compact"
                  clearable
                  hide-details
                  @update:modelValue="applyFilters"
                  color="primary"
                ></v-select>
              </v-col>

              <!-- Created By Filter -->
              <v-col cols="12" sm="6" md="3">
                <v-select
                  v-model="filters.created_by"
                  :items="creatorOptions"
                  item-title="text"
                  item-value="value"
                  label="Created By"
                  variant="outlined"
                  density="compact"
                  clearable
                  hide-details
                  @update:modelValue="applyFilters"
                  color="primary"
                ></v-select>
              </v-col>

              <!-- Issue Filter -->
              <v-col cols="12" sm="6" md="3">
                <v-checkbox
                  v-model="filters.has_issue"
                  label="Has Discrepancy"
                  density="compact"
                  hide-details
                  @update:modelValue="applyFilters"
                  color="warning"
                ></v-checkbox>
              </v-col>

              <!-- Date Range Start -->
              <v-col cols="12" sm="6" md="3">
                <v-text-field
                  v-model="filters.date_start"
                  label="Start Date"
                  type="date"
                  variant="outlined"
                  density="compact"
                  hide-details
                  @change="applyFilters"
                ></v-text-field>
              </v-col>

              <!-- Date Range End -->
              <v-col cols="12" sm="6" md="3">
                <v-text-field
                  v-model="filters.date_end"
                  label="End Date"
                  type="date"
                  variant="outlined"
                  density="compact"
                  hide-details
                  @change="applyFilters"
                ></v-text-field>
              </v-col>

              <v-col cols="12" sm="6" md="2" class="d-flex align-center">
                <v-btn
                  color="grey"
                  variant="outlined"
                  @click="clearFilters"
                  class="filter-clear-btn"
                  block
                >
                  Clear Filters
                </v-btn>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>
          <v-card 
            elevation="1"
            style="border-radius: 8px; background: white;"
          >
            <v-card-title class="pa-4" style="background: #f5f5f5; border-bottom: 1px solid #e0e0e0;">
              <v-icon left color="#666" size="24">mdi-cash-multiple</v-icon>
              <span class="text-h6 font-weight-medium" style="color: #333;">Collection Transfers</span>
            </v-card-title>
            <v-card-text class="pa-0">
              <!-- Selection summary -->
              <div class="d-flex align-center px-4 py-3" style="border-bottom: 1px solid #e0e0e0;">
                <v-chip color="primary" class="mr-2" style="font-weight:600;">
                  Selected: {{ selectedCount }}
                </v-chip>
                <v-chip color="orange" class="mr-2" style="font-weight:600;" v-if="selectedCount > 0">
                  Total Net: {{ formatCurrency(selectedNetTotal) }} Da
                </v-chip>
                <span class="text-caption text-secondary">Use the checkboxes to select recoltes</span>
              </div>
              <v-data-table
                :headers="headers"
                :items="recoltes.data"
                :loading="loading"
                :server-items-length="recoltes.total"
                :items-per-page="options.itemsPerPage"
                :page="options.page"
                @update:options="updateOptions"
                :footer-props="{
                  'items-per-page-options': [10, 25, 50, 100]
                }"
                class="simple-table"
                item-key="id"
                show-select
                return-object
                v-model="selectedRecoltes"
                :selectable-key="(item) => !item.transfer_request_id"
              >
                <!-- Code Column -->
                 <template v-slot:[`item.code`]="{ item }">
                   <div class="d-flex flex-column align-start">
                     <v-chip
                       color="primary"
                       text-color="white"
                       size="small"
                       class="mb-1"
                       style="font-weight: 600;"
                     >
                                            <v-icon  v-if="hasAmountDiscrepancy(item)" color="warning" left size="12">mdi-alert</v-icon>

                       #RCT-{{ item.code }}
                     </v-chip>
                     
                     
                     <!-- Transfer Status -->
                     <div v-if="item.transfer_request">
                       <v-chip
                         :color="item.transfer_request.status === 'success' ? 'success' : 'warning'"
                         text-color="white"
                         size="x-small"
                         style="font-weight: 600; height: 20px;"
                       >
                         {{ item.transfer_request.status.toUpperCase() }}
                       </v-chip>
                     </div>
                     <div v-else>
                       <span class="text-caption text-grey" style="font-size: 10px;">Not Transferred</span>
                     </div>
                   </div>
                 </template>

                 <!-- Type / Name Column -->
                 <template v-slot:[`item.type_name`]="{ item }">
                   <div class="d-flex align-center">
                     <v-icon
                       :color="item.type === 'driver' ? 'primary' : 'secondary'"
                       size="20"
                       class="mr-2"
                     >
                       {{ item.type === 'driver' ? 'mdi-truck-delivery' : 'mdi-account-tie' }}
                     </v-icon>
                     <div class="d-flex flex-column">
                       <span class="text-body-2 font-weight-medium">
                         {{ item.related_name }}
                       </span>
                       <span class="text-caption text-grey">
                         {{ item.type === 'driver' ? 'Driver' : 'Agent' }}
                       </span>
                     </div>
                   </div>
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
                     {{ item.collections.length || 0 }} Colis
                   </v-chip>
                 </template>

                 <!-- Calculated Amount Column -->
                 <template v-slot:[`item.total_cod_amount`]="{ item }">
                   <v-chip
                     color="orange"
                     text-color="white"
                     small
                   >
                     {{ formatCurrency(item.total_cod_amount || 0) }} Da
                   </v-chip>
                 </template>

                 <!-- Manual Amount Column -->
            

                  <!-- Expenses Column -->
                  <template v-slot:[`item.expenses_count`]="{ item }">
                    <div class="d-flex flex-column">
                      <v-chip
                        v-if="item.expenses_count > 0"
                        color="error"
                        text-color="white"
                        small
                        class="mb-1"
                      >
                        {{ item.expenses_count }} Exp.
                      </v-chip>
                      <span v-if="item.total_expenses > 0" class="text-caption text-error font-weight-bold">
                        -{{ formatCurrency(item.total_expenses) }}
                      </span>
                      <span v-else class="text-caption text-grey">None</span>
                    </div>
                  </template>

                  <!-- Net Total Column -->
                  <template v-slot:[`item.net_total`]="{ item }">
                    <v-chip
                      color="success"
                      text-color="white"
                      small
                      style="font-weight: 700;"
                    >
                      {{ formatCurrency(item.net_total) }} Da
                    </v-chip>
                  </template>

                  <!-- Created By Column -->
                 <template v-slot:[`item.created_by`]="{ item }">
                   <div v-if="item.created_by">
                     <div class="text-body-2 font-weight-medium">{{ item.created_by.uid || 'Unknown User' }}</div>
                   </div>
                   <span v-else class="text-body-2 text--secondary">System</span>
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
                       <v-list-item @click="exportPdf(item.id)">
                         <template v-slot:prepend>
                           <v-icon color="error">mdi-file-pdf-box</v-icon>
                         </template>
                         <v-list-item-title>Export PDF</v-list-item-title>
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

    <!-- Snackbar for notifications -->
    <v-snackbar
      v-model="snackbar.show"
      :color="snackbar.color"
      :timeout="3000"
      top
    >
      {{ snackbar.text }}
      <template v-slot:actions>
        <v-btn
          variant="text"
          @click="snackbar.show = false"
        >
          Close
        </v-btn>
      </template>
    </v-snackbar>

    <!-- Transfer Dialog -->
    <v-dialog v-model="transferDialog" max-width="500px">
      <v-card style="border-radius: 12px;">
        <v-card-title class="text-h5 pa-6">
          <v-icon left color="primary" size="28">mdi-bank-transfer</v-icon>
          Transfer to Admin
        </v-card-title>
        <v-card-text class="pa-6 pt-0">
          <p class="mb-4">Select an admin to transfer <strong>{{ selectedCount }}</strong> recoltes (Total: {{ formatCurrency(selectedNetTotal) }} Da).</p>
          <v-select
            v-model="selectedAdmin"
            :items="adminOptions"
            item-title="text"
            item-value="value"
            label="Select Admin"
            variant="outlined"
            density="comfortable"
            color="primary"
          ></v-select>
        </v-card-text>
        <v-card-actions class="pa-6 pt-0">
          <v-spacer></v-spacer>
          <v-btn
            text
            @click="transferDialog = false"
            style="border-radius: 8px;"
          >
            Cancel
          </v-btn>
          <v-btn
            color="primary"
            @click="createTransfer"
            style="border-radius: 8px;"
            :loading="transferring"
            :disabled="!selectedAdmin"
          >
            Create Transfer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Verify Dialog -->
    <v-dialog v-model="verifyDialog" max-width="400px">
      <v-card style="border-radius: 12px;">
        <v-card-title class="text-h5 pa-6">
          Verify Transfer
        </v-card-title>
        <v-card-text class="pa-6 pt-0">
          <p class="mb-4">Transfer created! Enter the verification code provided by the admin to complete the transfer immediately.</p>
          <v-text-field
            v-model="verificationCode"
            label="Verification Code"
            variant="outlined"
            density="comfortable"
            autofocus
            @keyup.enter="submitVerify"
          ></v-text-field>
        </v-card-text>
        <v-card-actions class="pa-6 pt-0">
          <v-spacer></v-spacer>
          <v-btn text @click="verifyDialog = false">Close</v-btn>
          <v-btn
            color="primary"
            @click="submitVerify"
            :loading="verifying"
            :disabled="!verificationCode"
          >
            Verify
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
    },
    companies: {
      type: Array,
      default: () => []
    },
    admins: {
      type: Array,
      default: () => []
    },
    creators: {
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      loading: false,
      deleting: false,
      deleteDialog: false,
      transferDialog: false,
      verifyDialog: false,
      transferring: false,
      verifying: false,
      selectedAdmin: null,
      selectedRecolte: null,
      verificationCode: '',
      pendingTransferId: null,
      snackbar: {
        show: false,
        text: '',
        color: 'success'
      },
      selectedRecoltes: [],
      filters: {
        company_id: null,
        type: null,
        created_by: null,
        has_issue: false,
        date_start: null,
        date_end: null
      },
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
          title: 'Net Total',
          key: 'net_total',
          sortable: true,
          width: '150px'
        },
        {
          title: 'Collecté par',
          key: 'type_name',
          sortable: false,
          width: '200px'
        },
         {
          title: 'Recolté Par',
          key: 'created_by',
          sortable: false,
          width: '250px'
        },
        {
          title: 'Remarque',
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
          title: 'Depenses',
          key: 'expenses_count',
          sortable: false,
          width: '120px'
        },
      
        {
          title: 'Company',
          key: 'company.name',
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
  computed: {
    companyOptions() {
      return this.companies?.map(company => ({
        text: company.name,
        value: company.id
      })) || []
    },
    adminOptions() {
      return this.admins?.map(admin => ({
        text: (admin.first_name || admin.last_name) 
          ? `${admin.first_name || ''} ${admin.last_name || ''}`.trim() 
          : (admin.email || `Admin ${admin.id}`),
        value: admin.id
      })) || []
    },
    creatorOptions() {
      return this.creators?.map(user => ({
        text: (user.first_name || user.last_name) 
          ? `${user.first_name || ''} ${user.last_name || ''}`.trim() 
          : (user.email || `User ${user.id}`),
        value: user.id
      })) || []
    },
    selectedCount() {
      return Array.isArray(this.selectedRecoltes) ? this.selectedRecoltes.length : 0
    },
    selectedNetTotal() {
      const selected = Array.isArray(this.selectedRecoltes) ? this.selectedRecoltes : []
      const list = Array.isArray(this.recoltes?.data) ? this.recoltes.data : []
      const byId = new Map(list.map(it => [it.id, it]))

      return selected.reduce((sum, r) => {
        let amt = 0
        if (r && typeof r === 'object') {
          const raw = r.raw || r
          // Use net_total which is (manual_amount || total_cod_amount) - total_expenses
          amt = parseFloat(raw?.net_total ?? 0)
        } else {
          const found = byId.get(r)
          amt = parseFloat(found?.net_total ?? 0)
        }
        return sum + (isNaN(amt) ? 0 : amt)
      }, 0)
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
      return parseFloat(amount || 0).toFixed(2)
    },
    viewRecolte(id) {
      this.$inertia.visit(`/recoltes/${id}`)
    },
    editRecolte(id) {
      this.$inertia.visit(`/recoltes/${id}/edit`)
    },
    exportPdf(id) {
      window.open(`/recoltes/${id}/export?type=pdf`, '_blank')
    },
    bulkExport() {
      if (this.selectedCount === 0) return
      
      const ids = this.selectedRecoltes.map(r => r.id)
      
      // Create a form to submit post request in new tab
      const form = document.createElement('form')
      form.method = 'POST'
      form.action = '/recoltes/bulk-export'
      form.target = '_blank'
      
      // Add CSRF token
      const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      const csrfInput = document.createElement('input')
      csrfInput.type = 'hidden'
      csrfInput.name = '_token'
      csrfInput.value = csrfToken
      form.appendChild(csrfInput)
      
      // Add IDs
      ids.forEach(id => {
        const input = document.createElement('input')
        input.type = 'hidden'
        input.name = 'ids[]'
        input.value = id
        form.appendChild(input)
      })
      
      document.body.appendChild(form)
      form.submit()
      document.body.removeChild(form)
    },
    bulkExportDetailed() {
      if (this.selectedCount === 0) return
      
      const ids = this.selectedRecoltes.map(r => r.id)
      
      // Create a form to submit post request in new tab
      const form = document.createElement('form')
      form.method = 'POST'
      form.action = '/recoltes/bulk-export-detailed'
      form.target = '_blank'
      
      // Add CSRF token
      const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      const csrfInput = document.createElement('input')
      csrfInput.type = 'hidden'
      csrfInput.name = '_token'
      csrfInput.value = csrfToken
      form.appendChild(csrfInput)
      
      // Add IDs
      ids.forEach(id => {
        const input = document.createElement('input')
        input.type = 'hidden'
        input.name = 'ids[]'
        input.value = id
        form.appendChild(input)
      })
      
      document.body.appendChild(form)
      form.submit()
      document.body.removeChild(form)
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
        sort_desc: options.sortBy[0]?.order === 'desc',
        ...this.filters
      }, {
        preserveState: true,
        preserveScroll: true,
        onStart: () => { this.loading = true },
        onFinish: () => { this.loading = false }
      })
    },
    applyFilters() {
      this.options.page = 1
      router.get('/recoltes', {
        ...this.filters,
        page: 1
      }, {
        preserveState: true,
        preserveScroll: true,
        onStart: () => { this.loading = true },
        onFinish: () => { this.loading = false }
      })
    },
    clearFilters() {
      this.filters = {
        company_id: null,
        type: null,
        created_by: null,
        has_issue: false,
        date_start: null,
        date_end: null
      }
      this.options.page = 1
      this.applyFilters()
    },
    hasAmountDiscrepancy(item) {
      if (!item.manual_amount || item.manual_amount <= 0 || !item.total_cod_amount) return false
      return Math.abs(item.total_cod_amount - item.manual_amount) > 0.01
    },
    getAmountDiscrepancyColor(item) {
      if (this.hasAmountDiscrepancy(item)) {
        return 'warning'
      }
      return 'success'
    },
    createTransfer() {
      if (!this.selectedAdmin || this.selectedCount === 0) return

      this.transferring = true
      router.post('/transfer-requests', {
        admin_id: this.selectedAdmin,
        recolte_ids: this.selectedRecoltes.map(r => r.id)
      }, {
        onSuccess: (page) => {
          this.transferDialog = false
          this.selectedRecoltes = []
          this.selectedAdmin = null
          this.transferring = false
          this.snackbar = {
            show: true,
            text: 'Transfer request created successfully',
            color: 'success'
          }
          
          // Check for new_transfer_id in flash props
          if (page.props.flash?.new_transfer_id) {
            this.pendingTransferId = page.props.flash.new_transfer_id
            this.verificationCode = ''
            this.verifyDialog = true
          }
        },
        onError: (errors) => {
          this.transferring = false
          this.snackbar = {
            show: true,
            text: errors.message || 'Error creating transfer request',
            color: 'error'
          }
          console.error('Transfer Error:', errors)
        }
      })
    },
    submitVerify() {
      if (!this.pendingTransferId || !this.verificationCode) return

      this.verifying = true
      router.post(`/transfer-requests/${this.pendingTransferId}/verify`, {
        code: this.verificationCode
      }, {
        onSuccess: () => {
          this.verifyDialog = false
          this.pendingTransferId = null
          this.verificationCode = ''
          this.verifying = false
          this.snackbar = {
            show: true,
            text: 'Transfer verified successfully!',
            color: 'success'
          }
          // Refresh the list to show updated status
          this.applyFilters()
        },
        onError: (errors) => {
          this.verifying = false
          this.snackbar = {
            show: true,
            text: errors.code || errors.message || 'Verification failed',
            color: 'error'
          }
        }
      })
    }
  }
}
</script>

<style scoped>
/* Filter Section */
.filter-clear-btn {
  border-radius: 8px;
  text-transform: none;
  font-weight: 500;
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