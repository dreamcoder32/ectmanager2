<template>
  <AppLayout>
    <Head title="Create Money Case" />
    
    <template #title>
      <div class="d-flex align-center">
        <v-btn
          icon
          @click="$inertia.visit('/money-cases')"
          class="mr-3"
          style="border-radius: 8px;"
        >
          <v-icon>mdi-arrow-left</v-icon>
        </v-btn>
        <span class="text-h4 font-weight-bold" 
              style="background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
                     -webkit-background-clip: text;
                     -webkit-text-fill-color: transparent;
                     background-clip: text;">
          Create Money Case
        </span>
      </div>
    </template>
    
    <template #content>
      <v-container>
        <v-row justify="center">
          <v-col cols="12" md="8" lg="6">
            <v-card elevation="2" style="border-radius: 12px;">
              <v-card-title class="pa-6" style="background: #f5f5f5; border-bottom: 1px solid #e0e0e0;">
                <v-icon left color="primary" size="28">mdi-wallet-plus</v-icon>
                <span class="text-h5 font-weight-medium">New Money Case</span>
              </v-card-title>
              
              <v-card-text class="pa-6">
                <v-form @submit.prevent="submit">
                  <v-row>
                    <!-- Name Field -->
                    <v-col cols="12">
                      <v-text-field
                        v-model="form.name"
                        label="Case Name *"
                        placeholder="Enter case name (e.g., Main Cash, Petty Cash)"
                        outlined
                        :error-messages="errors.name"
                        required
                      >
                        <template #prepend-inner>
                          <v-icon color="primary">mdi-wallet</v-icon>
                        </template>
                      </v-text-field>
                    </v-col>

                    <!-- Description Field -->
                    <v-col cols="12">
                      <v-textarea
                        v-model="form.description"
                        label="Description"
                        placeholder="Enter description for this money case..."
                        outlined
                        rows="3"
                        :error-messages="errors.description"
                      >
                        <template #prepend-inner>
                          <v-icon color="primary">mdi-text</v-icon>
                        </template>
                      </v-textarea>
                    </v-col>

                    <!-- Initial Balance Field -->
                    <v-col cols="12" md="6">
                      <v-text-field
                        v-model="form.balance"
                        label="Initial Balance"
                        placeholder="0.00"
                        outlined
                        type="number"
                        step="0.01"
                        :error-messages="errors.balance"
                      >
                        <template #prepend-inner>
                          <v-icon color="success">mdi-currency-usd</v-icon>
                        </template>
                        <template #append>
                          <span class="text-caption">DZD</span>
                        </template>
                      </v-text-field>
                    </v-col>

                    <!-- Status Field -->
                    <v-col cols="12" md="6">
                      <v-select
                        v-model="form.status"
                        :items="statusOptions"
                        label="Status *"
                        outlined
                        :error-messages="errors.status"
                        required
                      >
                        <template #prepend-inner>
                          <v-icon color="primary">mdi-check-circle</v-icon>
                        </template>
                      </v-select>
                    </v-col>
                  </v-row>

                  <!-- Action Buttons -->
                  <v-row class="mt-4">
                    <v-col cols="12">
                      <div class="d-flex justify-end gap-3">
                        <v-btn
                          text
                          @click="$inertia.visit('/money-cases')"
                          style="border-radius: 8px;"
                        >
                          Cancel
                        </v-btn>
                        <v-btn
                          color="primary"
                          type="submit"
                          :loading="form.processing"
                          style="border-radius: 8px; font-weight: 600;"
                          elevation="2"
                        >
                          <v-icon left>mdi-content-save</v-icon>
                          Create Case
                        </v-btn>
                      </div>
                    </v-col>
                  </v-row>
                </v-form>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </template>
  </AppLayout>
</template>

<script>
import { Head } from '@inertiajs/vue3'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

export default {
  name: 'MoneyCaseCreate',
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
  data() {
    return {
      statusOptions: [
        { title: 'Active', value: 'active' },
        { title: 'Inactive', value: 'inactive' }
      ]
    }
  },
  setup() {
    const form = useForm({
      name: '',
      description: '',
      balance: 0,
      status: 'active'
    })

    return { form }
  },
  methods: {
    submit() {
      this.form.post('/money-cases', {
        onSuccess: () => {
          // Redirect will be handled by the controller
        }
      })
    }
  }
}
</script>

<style scoped>
.gap-3 {
  gap: 12px;
}
</style>