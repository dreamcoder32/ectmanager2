<template>
  <AppLayout>
    <Head title="Create Expense Category" />
    
    <template #title>
      <div class="d-flex justify-space-between align-center">
        <span class="text-h4 font-weight-bold">
          Create Expense Category
        </span>
      </div>
    </template>
    
    <template #content>
      <v-container fluid>
        <v-row justify="center">
          <v-col cols="12" md="8" lg="6">
            <v-card elevation="2" class="pa-4">
              <v-card-title class="text-h5 mb-4">
                <v-icon left color="primary">mdi-tag-plus</v-icon>
                New Expense Category
              </v-card-title>
              
              <v-form @submit.prevent="submit">
                <v-card-text>
                  <v-row>
                    <!-- Category Name -->
                    <v-col cols="12">
                      <v-text-field
                        v-model="form.name"
                        label="Category Name *"
                        prepend-inner-icon="mdi-tag"
                        variant="outlined"
                        :error-messages="errors.name"
                        required
                        placeholder="e.g., Office Supplies, Fuel, Marketing"
                      ></v-text-field>
                    </v-col>
                    
                    <!-- Description -->
                    <v-col cols="12">
                      <v-textarea
                        v-model="form.description"
                        label="Description"
                        prepend-inner-icon="mdi-text"
                        variant="outlined"
                        :error-messages="errors.description"
                        rows="3"
                        placeholder="Brief description of this expense category..."
                      ></v-textarea>
                    </v-col>
                    
                    <!-- Active Status -->
                    <v-col cols="12">
                      <v-switch
                        v-model="form.is_active"
                        label="Active Category"
                        color="primary"
                        :true-value="true"
                        :false-value="false"
                        hide-details
                      >
                        <template v-slot:prepend>
                          <v-icon color="primary">mdi-check-circle</v-icon>
                        </template>
                      </v-switch>
                      <v-card-subtitle class="pa-0 mt-2">
                        Active categories are available for expense creation
                      </v-card-subtitle>
                    </v-col>
                  </v-row>
                </v-card-text>
                
                <v-card-actions class="px-6 pb-6">
                  <v-btn
                    :to="{ name: 'expense-categories.index' }"
                    variant="outlined"
                    prepend-icon="mdi-arrow-left"
                  >
                    Cancel
                  </v-btn>
                  
                  <v-spacer></v-spacer>
                  
                  <v-btn
                    type="submit"
                    :loading="form.processing"
                    color="primary"
                    variant="elevated"
                    prepend-icon="mdi-check"
                  >
                    Create Category
                  </v-btn>
                </v-card-actions>
              </v-form>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </template>
  </AppLayout>
</template>

<script>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

export default {
  name: 'ExpenseCategoryCreate',
  components: {
    Head,
    AppLayout
  },
  props: {
    errors: {
      type: Object,
      default: () => ({})
    }
  },
  setup() {
    const form = useForm({
      name: '',
      description: '',
      is_active: true
    })

    const submit = () => {
      form.post(route('expense-categories.store'), {
        onSuccess: () => {
          // Redirect handled by controller
        },
        onError: (errors) => {
          console.error('Validation errors:', errors)
        }
      })
    }

    return { 
      form,
      submit
    }
  }
}
</script>

<style scoped>
.v-card {
  border-radius: 12px;
}

.v-card-title {
  background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
  color: white;
  margin: -16px -16px 16px -16px;
  padding: 20px 24px;
  border-radius: 12px 12px 0 0;
}
</style>