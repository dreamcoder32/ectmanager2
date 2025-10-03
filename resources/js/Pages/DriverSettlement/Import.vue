<template>
  <div>
    <Head title="Driver Settlement Import" />
    <AppLayout>
      <template #title>
        <span style="background: linear-gradient(135deg, #1976d2, #1565c0); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-weight: 600;">
          Driver Settlement Import
        </span>
      </template>
      <template #content>
        <v-container fluid>
          <v-row>
            <v-col cols="12">
              <v-btn color="primary" text @click="$inertia.visit('/recoltes')" class="mb-4">
                <v-icon left>mdi-arrow-left</v-icon>
                Back to Collection Transfers
              </v-btn>

              <v-card elevation="2" style="border-radius: 12px;">
                <v-card-title class="pa-6" style="background: #f8f9fa; border-bottom: 1px solid #e0e0e0;">
                  <v-icon left color="primary" size="28">mdi-file-pdf-box</v-icon>
                  <span class="text-h5 font-weight-medium">Import Driver Settlement PDF</span>
                </v-card-title>

                <v-card-text class="pa-6">
                  <v-form @submit.prevent="submitProcess">
                    <v-row>
                      <v-col cols="12" md="6">
                        <v-file-input
                          v-model="pdfFile"
                          label="Upload PDF"
                          accept=".pdf"
                          prepend-icon="mdi-file-pdf"
                          outlined
                          @change="onPdfChange"
                        />
                      </v-col>
                      <v-col cols="12" md="6">
                        <v-select
                          v-model="selectedDriverId"
                          :items="drivers"
                          item-value="id"
                          item-title="name"
                          label="Select Driver"
                          outlined
                          :error-messages="errors.driver_id"
                        />
                      </v-col>
                      <v-col cols="12" md="4">
                        <v-text-field
                          v-model.number="driverCommission"
                          type="number"
                          label="Driver Commission (DA)"
                          outlined
                          :error-messages="errors.driver_commission"
                        />
                      </v-col>
                      <v-col cols="12" md="8">
                        <v-select
                          v-model="caseId"
                          :items="activeCases"
                          item-value="id"
                          item-title="name"
                          label="Money Case (optional)"
                          outlined
                          clearable
                        >
                          <template #item="{ item }">
                            <v-list-item>
                              <v-list-item-title>{{ item.raw.name }}</v-list-item-title>
                              <v-list-item-subtitle>
                                Balance: {{ formatCurrency(item.raw.balance) }} - {{ item.raw.description }}
                              </v-list-item-subtitle>
                            </v-list-item>
                          </template>
                        </v-select>
                      </v-col>

                      <v-col cols="12">
                        <v-alert v-if="parseSummary" type="info" outlined>
                          Candidates: {{ parseSummary.total_candidates }} | Found: {{ parseSummary.total_found }} | Missing: {{ parseSummary.total_missing }}
                          <template #append v-if="parseSummary.guess_driver_name">
                            <v-chip color="primary" class="ml-2" text-color="white">
                              Guess Driver: {{ parseSummary.guess_driver_name }}
                            </v-chip>
                          </template>
                        </v-alert>
                      </v-col>

                      <v-col cols="12" v-if="foundParcels.length">
                        <v-card outlined>
                          <v-card-title>
                            <v-icon left color="success">mdi-package-variant</v-icon>
                            <span class="text-h6">Parsed Parcels</span>
                            <v-spacer></v-spacer>
                            <v-chip color="primary" text-color="white" small>
                              Total COD: {{ formatCurrency(totalCod) }}
                            </v-chip>
                          </v-card-title>
                          <v-card-text class="pa-0">
                            <v-data-table
                              v-model="selectedTrackingNumbers"
                              :headers="headers"
                              :items="foundParcels"
                              show-select
                              item-key="tracking_number"
                              class="elevation-0"
                              :items-per-page="10"
                            >
                              <template v-slot:[`item.tracking_number`]="{ item }">
                                <v-chip small color="info" text-color="white">{{ item.tracking_number }}</v-chip>
                              </template>
                              <template v-slot:[`item.cod_amount`]="{ item }">
                                <v-chip small color="success" text-color="white">{{ formatCurrency(item.cod_amount) }}</v-chip>
                              </template>
                              <template v-slot:[`item.company_name`]="{ item }">
                                <div>{{ item.company_name || 'N/A' }}</div>
                              </template>
                              <template v-slot:[`item.status`]="{ item }">
                                <v-chip small :color="item.status === 'delivered' ? 'green' : 'grey'" text-color="white">{{ item.status }}</v-chip>
                              </template>
                            </v-data-table>
                          </v-card-text>
                        </v-card>
                      </v-col>

                      <v-col cols="12" v-if="missingTrackingNumbers.length">
                        <v-alert type="warning" outlined>
                          Missing tracking numbers (not found in system):
                          <div class="mt-2">
                            <v-chip v-for="tn in missingTrackingNumbers" :key="tn" class="mr-2 mb-2" color="warning" text-color="white">{{ tn }}</v-chip>
                          </div>
                        </v-alert>
                      </v-col>

                    </v-row>

                    <v-row class="mt-4">
                      <v-col cols="12" class="text-right">
                        <v-btn color="grey" text @click="$inertia.visit('/recoltes')" class="mr-3">Cancel</v-btn>
                        <v-btn type="submit" color="primary" :loading="processing" :disabled="!canSubmit" elevation="2">
                          <v-icon left>mdi-check</v-icon>
                          Create Collections and Recolte
                        </v-btn>
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
  </div>
</template>

<script>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

export default {
  name: 'DriverSettlementImport',
  components: { Head, AppLayout },
  props: {
    drivers: { type: Array, default: () => [] },
    activeCases: { type: Array, default: () => [] }
  },
  data() {
    return {
      pdfFile: null,
      selectedDriverId: null,
      driverCommission: 0,
      caseId: null,
      foundParcels: [],
      missingTrackingNumbers: [],
      selectedTrackingNumbers: [],
      parseSummary: null,
      processing: false,
      errors: {},
      headers: [
        { text: 'Tracking Number', value: 'tracking_number', sortable: false },
        { text: 'COD Amount', value: 'cod_amount', sortable: true },
        { text: 'Company', value: 'company_name', sortable: false },
        { text: 'Status', value: 'status', sortable: false }
      ]
    }
  },
  computed: {
    canSubmit() {
      return this.selectedDriverId && this.driverCommission >= 0 && this.selectedTrackingNumbers.length > 0 && !this.processing;
    },
    totalCod() {
      return this.foundParcels.reduce((sum, p) => sum + (Number(p.cod_amount) || 0), 0);
    }
  },
  methods: {
    formatCurrency(amount) {
      return new Intl.NumberFormat('fr-DZ', { style: 'currency', currency: 'DZD', minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(amount || 0);
    },
    async onPdfChange() {
      this.foundParcels = [];
      this.missingTrackingNumbers = [];
      this.selectedTrackingNumbers = [];
      this.parseSummary = null;

      if (!this.pdfFile) return;
      const formData = new FormData();
      formData.append('pdf_file', this.pdfFile);

      try {
        const resp = await fetch('/driver-settlement/parse', { method: 'POST', body: formData, headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content') } });
        const data = await resp.json();
        if (data.success) {
          this.foundParcels = data.found || [];
          this.missingTrackingNumbers = data.missing || [];
          this.parseSummary = {
            total_candidates: data.total_candidates,
            total_found: data.total_found,
            total_missing: data.total_missing,
            guess_driver_name: data.guess_driver_name
          };
          // Preselect found tracking numbers
          this.selectedTrackingNumbers = this.foundParcels.map(p => p.tracking_number);
        } else {
          this.$toast.error('Failed to parse PDF');
        }
      } catch (e) {
        console.error('Parse error', e);
        this.$toast.error('Failed to parse PDF');
      }
    },
    async submitProcess() {
      if (!this.canSubmit) return;
      this.processing = true;
      this.errors = {};
      try {
        const resp = await fetch('/driver-settlement/process', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
          },
          body: JSON.stringify({
            tracking_numbers: this.selectedTrackingNumbers,
            driver_id: this.selectedDriverId,
            driver_commission: Number(this.driverCommission),
            case_id: this.caseId,
            note: `Imported from driver settlement on ${new Date().toLocaleString('fr-FR')}`
          })
        });
        if (resp.redirected) {
          // Inertia: follow redirect
          window.location.href = resp.url;
          return;
        }
        const data = await resp.json();
        if (data.errors) {
          this.errors = data.errors;
        }
        if (data.success) {
          this.$toast.success('Processed successfully');
        }
      } catch (e) {
        console.error('Process error', e);
        this.$toast.error('Processing failed');
      } finally {
        this.processing = false;
      }
    }
  }
}
</script>

<style scoped>
.v-card { transition: all 0.3s ease; }
.v-card:hover { transform: translateY(-2px); }
.v-chip { font-weight: 500; }
</style>