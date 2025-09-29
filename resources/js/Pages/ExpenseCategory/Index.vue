<template>
  <AppLayout title="Expense Categories">
    <Head title="Expense Categories" />
    
    <v-container fluid>
      <!-- Header -->
      <v-row class="mb-4">
        <v-col>
          <div class="d-flex justify-space-between align-center">
            <div>
              <h1 class="text-h4 font-weight-bold">Expense Categories</h1>
              <p class="text-subtitle-1 text-grey-darken-1">Manage expense categories for the system</p>
            </div>
            <v-btn
              color="primary"
              prepend-icon="mdi-plus"
              @click="$inertia.visit('/expense-categories/create')"
            >
              Add Category
            </v-btn>
          </div>
        </v-col>
      </v-row>

      <!-- Categories Table -->
      <v-card>
        <v-card-title>
          <v-row>
            <v-col cols="12" md="6">
              <v-text-field
                v-model="search"
                prepend-inner-icon="mdi-magnify"
                label="Search categories..."
                variant="outlined"
                density="compact"
                clearable
                hide-details
              />
            </v-col>
          </v-row>
        </v-card-title>

        <v-data-table
          :headers="headers"
          :items="categories"
          :search="search"
          :loading="loading"
          item-value="id"
          class="elevation-0"
        >
          <template v-slot:[`item.is_active`]="{ item }">
            <v-chip
              :color="item.is_active ? 'success' : 'error'"
              size="small"
              variant="flat"
            >
              {{ item.is_active ? 'Active' : 'Inactive' }}
            </v-chip>
          </template>

          <template v-slot:[`item.created_at`]="{ item }">
            {{ formatDate(item.created_at) }}
          </template>

          <template v-slot:[`item.actions`]="{ item }">
            <div class="d-flex gap-2">
              <v-btn
                icon="mdi-eye"
                size="small"
                variant="text"
                color="info"
                @click="$inertia.visit(`/expense-categories/${item.id}`)"
              />
              <v-btn
                icon="mdi-pencil"
                size="small"
                variant="text"
                color="primary"
                @click="$inertia.visit(`/expense-categories/${item.id}/edit`)"
              />
              <v-btn
                icon="mdi-delete"
                size="small"
                variant="text"
                color="error"
                @click="confirmDelete(item)"
              />
            </div>
          </template>

          <template #no-data>
            <div class="text-center py-8">
              <v-icon size="64" color="grey-lighten-2">mdi-folder-outline</v-icon>
              <p class="text-h6 mt-4">No categories found</p>
              <p class="text-body-2 text-grey-darken-1">Create your first expense category to get started</p>
            </div>
          </template>
        </v-data-table>
      </v-card>

      <!-- Create/Edit Dialog -->
      <v-dialog v-model="showCreateDialog" max-width="500px" persistent>
        <v-card>
          <v-card-title>
            <span class="text-h5">{{ editingCategory ? 'Edit Category' : 'Create Category' }}</span>
          </v-card-title>

          <v-card-text>
            <v-form ref="categoryForm" v-model="formValid">
              <v-text-field
                v-model="categoryForm.name"
                label="Category Name"
                :rules="nameRules"
                variant="outlined"
                required
              />

              <v-textarea
                v-model="categoryForm.description"
                label="Description"
                variant="outlined"
                rows="3"
                auto-grow
              />

              <v-switch
                v-model="categoryForm.is_active"
                label="Active"
                color="primary"
                hide-details
              />
            </v-form>
          </v-card-text>

          <v-card-actions>
            <v-spacer />
            <v-btn
              text
              @click="closeDialog"
            >
              Cancel
            </v-btn>
            <v-btn
              color="primary"
              :loading="submitting"
              :disabled="!formValid"
              @click="saveCategory"
            >
              {{ editingCategory ? 'Update' : 'Create' }}
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <!-- Delete Confirmation Dialog -->
      <v-dialog v-model="showDeleteDialog" max-width="400px">
        <v-card>
          <v-card-title class="text-h5">
            Confirm Delete
          </v-card-title>

          <v-card-text>
            Are you sure you want to delete the category "{{ selectedCategory?.name }}"?
            This action cannot be undone.
          </v-card-text>

          <v-card-actions>
            <v-spacer />
            <v-btn
              text
              @click="showDeleteDialog = false"
            >
              Cancel
            </v-btn>
            <v-btn
              color="error"
              :loading="deleting"
              @click="deleteCategory"
            >
              Delete
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-container>
  </AppLayout>
</template>

<script>
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { router } from '@inertiajs/vue3'

export default {
  name: 'ExpenseCategoryIndex',
  components: {
    Head,
    AppLayout,
  },
  props: {
    categories: {
      type: Array,
      default: () => []
    },
    auth: {
      type: Object,
      required: true
    }
  },
  data() {
    return {
      loading: false,
      search: '',
      showCreateDialog: false,
      showDeleteDialog: false,
      editingCategory: null,
      selectedCategory: null,
      submitting: false,
      deleting: false,
      formValid: false,
      categoryForm: {
        name: '',
        description: '',
        is_active: true
      },
      headers: [
        { title: 'Name', key: 'name', sortable: true },
        { title: 'Description', key: 'description', sortable: false },
        { title: 'Status', key: 'is_active', sortable: true },
        { title: 'Created', key: 'created_at', sortable: true },
        { title: 'Actions', key: 'actions', sortable: false, align: 'center' }
      ],
      nameRules: [
        v => !!v || 'Category name is required',
        v => (v && v.length >= 2) || 'Name must be at least 2 characters',
        v => (v && v.length <= 50) || 'Name must be less than 50 characters'
      ]
    }
  },
  methods: {
    editCategory(category) {
      this.editingCategory = category
      this.categoryForm = {
        name: category.name,
        description: category.description || '',
        is_active: category.is_active
      }
      this.showCreateDialog = true
    },
    confirmDelete(category) {
      this.selectedCategory = category
      this.showDeleteDialog = true
    },
    closeDialog() {
      this.showCreateDialog = false
      this.editingCategory = null
      this.resetForm()
    },
    resetForm() {
      this.categoryForm = {
        name: '',
        description: '',
        is_active: true
      }
      if (this.$refs.categoryForm) {
        this.$refs.categoryForm.resetValidation()
      }
    },
    async saveCategory() {
      if (!this.formValid) return

      this.submitting = true
      
      try {
        if (this.editingCategory) {
          await router.put(`/expense-categories/${this.editingCategory.id}`, this.categoryForm)
        } else {
          await router.post('/expense-categories', this.categoryForm)
        }
        this.closeDialog()
      } catch (error) {
        console.error('Error saving category:', error)
      } finally {
        this.submitting = false
      }
    },
    async deleteCategory() {
      if (!this.selectedCategory) return

      this.deleting = true
      
      try {
        await router.delete(`/expense-categories/${this.selectedCategory.id}`)
        this.showDeleteDialog = false
        this.selectedCategory = null
      } catch (error) {
        console.error('Error deleting category:', error)
      } finally {
        this.deleting = false
      }
    },
    formatDate(dateString) {
      if (!dateString) return ''
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    }
  }
}
</script>