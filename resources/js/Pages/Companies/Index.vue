<template>
  <AppLayout :title="$t('companies.title')">
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header Section -->
        <div class="mb-8">
          <div class="flex justify-between items-center">
            <div>
              <h1 class="text-3xl font-bold text-gray-900 mb-2">
                {{ $t('companies.title') }}
              </h1>
              <p class="text-gray-600">
                {{ $t('companies.subtitle') }}
              </p>
            </div>
            <v-btn
              color="primary"
              @click="showCreateDialog = true"
              prepend-icon="mdi-plus"
              elevation="2"
            >
              {{ $t('companies.create') }}
            </v-btn>
          </div>
        </div>

        <!-- Companies Table -->
        <v-card elevation="3" class="mb-6">
          <v-card-title class="d-flex align-center">
            <v-icon class="mr-3" color="primary">mdi-domain</v-icon>
            {{ $t('companies.list') }}
          </v-card-title>
          
          <v-card-text>
            <!-- Search and Filters -->
            <v-row class="mb-4">
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="search"
                  :label="$t('companies.search')"
                  prepend-inner-icon="mdi-magnify"
                  variant="outlined"
                  density="compact"
                  clearable
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-select
                  v-model="statusFilter"
                  :items="statusOptions"
                  :label="$t('companies.status')"
                  variant="outlined"
                  density="compact"
                  clearable
                ></v-select>
              </v-col>
              <v-col cols="12" md="3">
                <v-btn
                  color="secondary"
                  variant="outlined"
                  @click="resetFilters"
                  prepend-icon="mdi-refresh"
                >
                  {{ $t('common.reset') }}
                </v-btn>
              </v-col>
            </v-row>

            <!-- Data Table -->
            <v-data-table
              :headers="headers"
              :items="filteredCompanies"
              :search="search"
              :loading="loading"
              item-key="id"
              class="elevation-1"
              :items-per-page="10"
            >
              <!-- Company Name Column -->
              <template v-slot:[`item.name`]="{ item }">
                <div class="d-flex align-center">
                  <v-avatar size="32" class="mr-3" color="primary">
                    <span class="text-white font-weight-bold">
                      {{ item.name.charAt(0).toUpperCase() }}
                    </span>
                  </v-avatar>
                  <div>
                    <div class="font-weight-medium">{{ item.name }}</div>
                    <div class="text-caption text-grey">{{ item.email }}</div>
                  </div>
                </div>
              </template>

              <!-- Status Column -->
              <template v-slot:[`item.is_active`]="{ item }">
                <v-chip
                  :color="item.is_active ? 'success' : 'error'"
                  text-color="white"
                  small
                >
                  {{ item.is_active ? $t('common.active') : $t('common.inactive') }}
                </v-chip>
              </template>

              <!-- WhatsApp Status Column -->
              <template v-slot:[`item.whatsapp_status`]="{ item }">
                <v-chip
                  :color="item.whatsapp_api_key ? 'success' : 'warning'"
                  text-color="white"
                  small
                >
                  <v-icon left size="16">
                    {{ item.whatsapp_api_key ? 'mdi-check' : 'mdi-alert' }}
                  </v-icon>
                  {{ item.whatsapp_api_key ? $t('companies.whatsapp_configured') : $t('companies.whatsapp_not_configured') }}
                </v-chip>
              </template>

              <!-- Commission Column -->
              <template v-slot:[`item.commission`]="{ item }">
                <span class="font-weight-medium">
                  {{ item.commission }}%
                </span>
              </template>

              <!-- Actions Column -->
              <template v-slot:[`item.actions`]="{ item }">
                <div class="d-flex align-center">
                  <v-btn
                    icon="mdi-eye"
                    size="small"
                    variant="text"
                    @click="viewCompany(item)"
                    color="primary"
                  ></v-btn>
                  <v-btn
                    icon="mdi-pencil"
                    size="small"
                    variant="text"
                    @click="editCompany(item)"
                    color="secondary"
                  ></v-btn>
                  <v-btn
                    icon="mdi-whatsapp"
                    size="small"
                    variant="text"
                    @click="configureWhatsApp(item)"
                    :color="item.whatsapp_api_key ? 'success' : 'warning'"
                  ></v-btn>
                  <v-btn
                    icon="mdi-delete"
                    size="small"
                    variant="text"
                    @click="deleteCompany(item)"
                    color="error"
                  ></v-btn>
                </div>
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>

        <!-- Create/Edit Company Dialog -->
        <v-dialog v-model="showCreateDialog" max-width="600px" persistent>
          <v-card>
            <v-card-title>
              <span class="text-h5">
                {{ editingCompany ? $t('companies.edit') : $t('companies.create') }}
              </span>
            </v-card-title>
            
            <v-card-text>
              <v-form ref="form" v-model="formValid">
                <v-row>
                  <v-col cols="12">
                    <v-text-field
                      v-model="form.name"
                      :label="$t('companies.name')"
                      :rules="[rules.required]"
                      variant="outlined"
                      required
                    ></v-text-field>
                  </v-col>
                  
                  <v-col cols="12">
                    <v-text-field
                      v-model="form.code"
                      :label="$t('companies.code')"
                      :rules="[rules.required]"
                      variant="outlined"
                      required
                    ></v-text-field>
                  </v-col>
                  
                  <v-col cols="12">
                    <v-text-field
                      v-model="form.email"
                      :label="$t('companies.email')"
                      :rules="[rules.required, rules.email]"
                      variant="outlined"
                      type="email"
                      required
                    ></v-text-field>
                  </v-col>
                  
                  <v-col cols="12">
                    <v-text-field
                      v-model="form.phone"
                      :label="$t('companies.phone')"
                      variant="outlined"
                    ></v-text-field>
                  </v-col>
                  
                  <v-col cols="12">
                    <v-textarea
                      v-model="form.address"
                      :label="$t('companies.address')"
                      variant="outlined"
                      rows="3"
                    ></v-textarea>
                  </v-col>
                  
                  <v-col cols="6">
                    <v-text-field
                      v-model="form.commission"
                      :label="$t('companies.commission')"
                      :rules="[rules.required, rules.numeric]"
                      variant="outlined"
                      type="number"
                      suffix="%"
                      required
                    ></v-text-field>
                  </v-col>
                  
                  <v-col cols="6">
                    <v-switch
                      v-model="form.is_active"
                      :label="$t('companies.is_active')"
                      color="success"
                    ></v-switch>
                  </v-col>
                </v-row>
              </v-form>
            </v-card-text>
            
            <v-card-actions>
              <v-spacer></v-spacer>
              <v-btn color="grey" text @click="closeDialog">
                {{ $t('common.cancel') }}
              </v-btn>
              <v-btn
                color="primary"
                @click="saveCompany"
                :loading="saving"
                :disabled="!formValid"
              >
                {{ $t('common.save') }}
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-dialog>

        <!-- WhatsApp Configuration Dialog -->
        <v-dialog v-model="showWhatsAppDialog" max-width="500px" persistent>
          <v-card>
            <v-card-title>
              <span class="text-h5">
                {{ $t('companies.whatsapp_configuration') }}
              </span>
            </v-card-title>
            
            <v-card-text>
              <v-form ref="whatsappForm" v-model="whatsappFormValid">
                <v-text-field
                  v-model="whatsappForm.api_key"
                  :label="$t('companies.whatsapp_api_key')"
                  :rules="[rules.required]"
                  variant="outlined"
                  type="password"
                  required
                ></v-text-field>
                
                <v-alert type="info" density="compact" class="mt-3 mb-4">
                  {{ $t('companies.whatsapp_api_key_help') }}
                </v-alert>

                <v-divider class="my-4"></v-divider>

                <h3 class="text-h6 mb-3">{{ $t('companies.desk_pickup_template') }}</h3>
                
                <v-textarea
                  v-model="whatsappForm.desk_pickup_template"
                  :label="$t('companies.desk_pickup_template_label')"
                  variant="outlined"
                  rows="10"
                  :hint="$t('companies.desk_pickup_template_hint')"
                  persistent-hint
                ></v-textarea>

                <v-alert type="info" density="compact" class="mt-3">
                  <div class="text-caption">
                    <strong>{{ $t('companies.available_placeholders') }}:</strong><br>
                    <code>{company_name}</code> - {{ $t('companies.placeholder_company_name') }}<br>
                    <code>{recipient_name}</code> - {{ $t('companies.placeholder_recipient_name') }}<br>
                    <code>{parcel_designation}</code> - {{ $t('companies.placeholder_parcel_designation') }}<br>
                    <code>{parcel_amount}</code> - {{ $t('companies.placeholder_parcel_amount') }}<br>
                    <code>{tracking_number}</code> - {{ $t('companies.placeholder_tracking_number') }}<br>
                    <code>{recipient_phone}</code> - {{ $t('companies.placeholder_recipient_phone') }}<br>
                    <code>{recipient_address}</code> - {{ $t('companies.placeholder_recipient_address') }}
                  </div>
                </v-alert>
              </v-form>
            </v-card-text>
            
            <v-card-actions>
              <v-spacer></v-spacer>
              <v-btn color="grey" text @click="closeWhatsAppDialog">
                {{ $t('common.cancel') }}
              </v-btn>
              <v-btn
                color="primary"
                @click="testWhatsAppConnection"
                :loading="testingConnection"
              >
                {{ $t('companies.test_connection') }}
              </v-btn>
              <v-btn
                color="success"
                @click="saveWhatsAppConfig"
                :loading="saving"
                :disabled="!whatsappFormValid"
              >
                {{ $t('common.save') }}
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-dialog>

        <!-- View Company Dialog -->
        <v-dialog v-model="showViewDialog" max-width="600px">
          <v-card>
            <v-card-title>
              <span class="text-h5">{{ $t('companies.company_details') }}</span>
            </v-card-title>
            
            <v-card-text v-if="viewingCompany">
              <v-row>
                <v-col cols="12">
                  <h3 class="text-h6 mb-3">{{ viewingCompany.name }}</h3>
                </v-col>
                
                <v-col cols="6">
                  <strong>{{ $t('companies.email') }}:</strong>
                  <p>{{ viewingCompany.email }}</p>
                </v-col>
                
                <v-col cols="6">
                  <strong>{{ $t('companies.phone') }}:</strong>
                  <p>{{ viewingCompany.phone || $t('common.not_provided') }}</p>
                </v-col>
                
                <v-col cols="12">
                  <strong>{{ $t('companies.address') }}:</strong>
                  <p>{{ viewingCompany.address || $t('common.not_provided') }}</p>
                </v-col>
                
                <v-col cols="6">
                  <strong>{{ $t('companies.commission') }}:</strong>
                  <p>{{ viewingCompany.commission }}%</p>
                </v-col>
                
                <v-col cols="6">
                  <strong>{{ $t('companies.status') }}:</strong>
                  <v-chip
                    :color="viewingCompany.is_active ? 'success' : 'error'"
                    text-color="white"
                    small
                  >
                    {{ viewingCompany.is_active ? $t('common.active') : $t('common.inactive') }}
                  </v-chip>
                </v-col>
                
                <v-col cols="12">
                  <strong>{{ $t('companies.whatsapp_status') }}:</strong>
                  <v-chip
                    :color="viewingCompany.whatsapp_api_key ? 'success' : 'warning'"
                    text-color="white"
                    small
                  >
                    <v-icon left size="16">
                      {{ viewingCompany.whatsapp_api_key ? 'mdi-check' : 'mdi-alert' }}
                    </v-icon>
                    {{ viewingCompany.whatsapp_api_key ? $t('companies.whatsapp_configured') : $t('companies.whatsapp_not_configured') }}
                  </v-chip>
                </v-col>
              </v-row>
            </v-card-text>
            
            <v-card-actions>
              <v-spacer></v-spacer>
              <v-btn color="primary" text @click="showViewDialog = false">
                {{ $t('common.close') }}
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-dialog>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import { ref, onMounted, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

export default {
  name: 'CompaniesIndex',
  components: {
    AppLayout
  },
  props: {
    companies: {
      type: Object,
      default: () => ({ data: [] })
    },
    filters: {
      type: Object,
      default: () => ({})
    }
  },
  setup(props) {
    // Reactive data
    const loading = ref(false)
    const saving = ref(false)
    const testingConnection = ref(false)
    const search = ref('')
    const statusFilter = ref('')
    
    // Dialog states
    const showCreateDialog = ref(false)
    const showWhatsAppDialog = ref(false)
    const showViewDialog = ref(false)
    
    // Form states
    const formValid = ref(false)
    const whatsappFormValid = ref(false)
    
    // Data
    const editingCompany = ref(null)
    const viewingCompany = ref(null)
    const configuringCompany = ref(null)
    
    // Form data
    const form = ref({
      name: '',
      code: '',
      email: '',
      phone: '',
      address: '',
      commission: 10,
      is_active: true
    })
    
    const whatsappForm = ref({
      api_key: '',
      desk_pickup_template: ''
    })
    
    // Table headers
    const headers = [
      { title: 'Name', key: 'name', sortable: true },
      { title: 'Code', key: 'code', sortable: true },
      { title: 'Email', key: 'email', sortable: true },
      { title: 'Phone', key: 'phone', sortable: false },
      { title: 'Commission', key: 'commission', sortable: true },
      { title: 'Status', key: 'is_active', sortable: true },
      { title: 'WhatsApp', key: 'whatsapp_status', sortable: false },
      { title: 'Actions', key: 'actions', sortable: false, width: '200px' }
    ]
    
    // Status options
    const statusOptions = [
      { title: 'Active', value: true },
      { title: 'Inactive', value: false }
    ]
    
    // Validation rules
    const rules = {
      required: (value) => !!value || 'This field is required',
      email: (value) => {
        const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
        return pattern.test(value) || 'Invalid email format'
      },
      numeric: (value) => {
        const num = parseFloat(value)
        return (!isNaN(num) && num >= 0 && num <= 100) || 'Must be a number between 0 and 100'
      }
    }
    
    // Computed properties
    const filteredCompanies = computed(() => {
      let filtered = props.companies.data || []
      
      if (statusFilter.value !== '') {
        filtered = filtered.filter(company => company.is_active === statusFilter.value)
      }
      
      return filtered
    })
    
    // Methods
    const resetFilters = () => {
      search.value = ''
      statusFilter.value = ''
    }
    
    const viewCompany = (company) => {
      viewingCompany.value = company
      showViewDialog.value = true
    }
    
    const editCompany = (company) => {
      editingCompany.value = company
      form.value = {
        name: company.name,
        code: company.code,
        email: company.email,
        phone: company.phone || '',
        address: company.address || '',
        commission: company.commission,
        is_active: company.is_active
      }
      showCreateDialog.value = true
    }
    
    const configureWhatsApp = (company) => {
      configuringCompany.value = company
      whatsappForm.value.api_key = company.whatsapp_api_key || ''
      whatsappForm.value.desk_pickup_template = company.whatsapp_desk_pickup_template || ''
      showWhatsAppDialog.value = true
    }
    
    const deleteCompany = (company) => {
      if (confirm(`Are you sure you want to delete ${company.name}?`)) {
        router.delete(`/companies/${company.id}`, {
          onSuccess: () => {
            // Company deleted successfully
          }
        })
      }
    }
    
    const closeDialog = () => {
      showCreateDialog.value = false
      editingCompany.value = null
      form.value = {
        name: '',
        code: '',
        email: '',
        phone: '',
        address: '',
        commission: 10,
        is_active: true
      }
    }
    
    const closeWhatsAppDialog = () => {
      showWhatsAppDialog.value = false
      configuringCompany.value = null
      whatsappForm.value.api_key = ''
    }
    
    const saveCompany = () => {
      saving.value = true
      
      const method = editingCompany.value ? 'put' : 'post'
      const url = editingCompany.value ? `/companies/${editingCompany.value.id}` : '/companies'
      
      router[method](url, form.value, {
        onSuccess: () => {
          closeDialog()
        },
        onFinish: () => {
          saving.value = false
        }
      })
    }
    
    const saveWhatsAppConfig = () => {
      saving.value = true
      
      router.put(`/companies/${configuringCompany.value.id}/whatsapp-api-key`, {
        whatsapp_api_key: whatsappForm.value.api_key,
        whatsapp_desk_pickup_template: whatsappForm.value.desk_pickup_template
      }, {
        onSuccess: () => {
          closeWhatsAppDialog()
        },
        onFinish: () => {
          saving.value = false
        }
      })
    }
    
    const testWhatsAppConnection = () => {
      testingConnection.value = true
      
      router.post(`/companies/${configuringCompany.value.id}/test-whatsapp-connection`, {
        whatsapp_api_key: whatsappForm.value.api_key
      }, {
        onSuccess: (page) => {
          if (page.props.flash.success) {
            alert('WhatsApp connection test successful!')
          } else {
            alert('WhatsApp connection test failed. Please check your API key.')
          }
        },
        onFinish: () => {
          testingConnection.value = false
        }
      })
    }
    
    return {
      // Data
      loading,
      saving,
      testingConnection,
      search,
      statusFilter,
      
      // Dialogs
      showCreateDialog,
      showWhatsAppDialog,
      showViewDialog,
      
      // Forms
      formValid,
      whatsappFormValid,
      form,
      whatsappForm,
      
      // Data
      editingCompany,
      viewingCompany,
      configuringCompany,
      
      // Table
      headers,
      statusOptions,
      
      // Rules
      rules,
      
      // Computed
      filteredCompanies,
      
      // Methods
      resetFilters,
      viewCompany,
      editCompany,
      configureWhatsApp,
      deleteCompany,
      closeDialog,
      closeWhatsAppDialog,
      saveCompany,
      saveWhatsAppConfig,
      testWhatsAppConnection
    }
  }
}
</script>

<style scoped>
.v-data-table {
  border-radius: 8px;
}

.v-card {
  border-radius: 12px;
}

.v-btn {
  border-radius: 8px;
}
</style>
