<template>
  <div>
    <Head :title="$t('driverSettlement.title')" />
    <AppLayout>
      <template #title>
        <span style="background: linear-gradient(135deg, #1976d2, #1565c0); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-weight: 600;">
          {{ $t('driverSettlement.title') }}
        </span>
      </template>
      <template #content>
        <v-container fluid>
          <v-row>
            <v-col cols="12">
              <v-btn color="primary" text @click="goBack" class="mb-4">
                <v-icon left>mdi-arrow-left</v-icon>
                {{ $t('driverSettlement.back_to_transfers') }}
              </v-btn>

              <v-card elevation="2" style="border-radius: 12px;">
                <v-card-title class="pa-6" style="background: #f8f9fa; border-bottom: 1px solid #e0e0e0;">
                  <v-icon left color="primary" size="28">mdi-file-excel-box</v-icon>
                  <span class="text-h5 font-weight-medium">{{ $t('driverSettlement.import_xlsx_title') }}</span>
                </v-card-title>

                <v-card-text class="">
                  <v-form @submit.prevent="submitProcess">
                    <v-row>
                     
                      <v-col cols="12" md="6" class="mt-4">
                        <v-select
                          v-model="selectedDriverId"
                          :items="drivers"
                          item-value="id"
                          item-title="name"
                          :label="$t('driverSettlement.select_driver')"
                          outlined
                          :error-messages="errors.driver_id"
                        />
                      </v-col>
                      <v-col cols="12" md="4" class="mt-4">
                        <v-text-field
                          v-model.number="driverCommission"
                          type="number"
                          :label="$t('driverSettlement.driver_commission_da')"
                          outlined
                          :error-messages="errors.driver_commission"
                        />
                      </v-col>
                      <v-col cols="12" md="12">
                        <v-file-input
                          v-model="xlsxFile"
                          :label="$t('driverSettlement.upload_xlsx')"
                          accept=".xlsx,.xls"
                          prepend-icon="mdi-file-excel"
                          outlined
                          @change="onXlsxChange"
                        />
                      </v-col>
                      <!-- <v-col cols="12" md="8">
                        <v-select
                          v-model="caseId"
                          :items="activeCases"
                          item-value="id"
                          item-title="name"
                          :label="$t('driverSettlement.money_case_optional')"
                          outlined
                          clearable
                        >
                          <template #item="{ item }">
                            <v-list-item>
                              <v-list-item-title>{{ item.raw.name }}</v-list-item-title>
                              <v-list-item-subtitle>
                                {{ $t('stopdesk_payment.money_case') }}: {{ formatCurrency(item.raw.balance) }} - {{ item.raw.description }}
                              </v-list-item-subtitle>
                            </v-list-item>
                          </template>
                        </v-select>
                      </v-col> -->

                      <v-col cols="12" v-if="parseSummary">
                        <v-alert type="info" outlined>
                          {{ $t('parcels.tracking_number') }}: {{ parseSummary.total_candidates }} | {{ $t('common.import') }}: {{ parseSummary.total_found }} | {{ $t('common.clear') }}: {{ parseSummary.total_missing }}
                          <template #append v-if="parseSummary.guess_driver_name">
                            <v-chip color="primary" class="ml-2" text-color="white">
                              {{ $t('driverSettlement.select_driver') }}: {{ parseSummary.guess_driver_name }}
                            </v-chip>
                          </template>
                        </v-alert>
                      </v-col>

                      <v-col cols="12" v-if="foundParcels.length">
                        <v-card outlined>
                          <v-card-title>
                            <v-icon left color="success">mdi-package-variant</v-icon>
                            <span class="text-h6">{{ $t('parcels.title') }}</span>
                            <v-spacer></v-spacer>
                            <v-chip color="primary" text-color="white" small>
                              {{ $t('financial_dashboard.total_revenue') }}: {{ formatCurrency(totalCod) }}
                            </v-chip>
                            <v-chip color="deep-purple" text-color="white" small class="ml-2">
                              {{ $t('driverSettlement.total_net') }}: {{ formatCurrency(totalNet) }}
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
                              <template v-slot:[`item.net_amount`]="{ item }">
                                <v-chip small color="deep-purple" text-color="white">{{ formatCurrency(netAmountForParcel(item)) }}</v-chip>
                              </template>
                              <template v-slot:[`item.company_name`]="{ item }">
                                <div>{{ item.company_name || 'N/A' }}</div>
                              </template>
                              <template v-slot:[`item.status`]="{ item }">
                                <v-chip small :color="item.status === 'delivered' ? 'green' : 'grey'" text-color="white">{{ item.status }}</v-chip>
                              </template>
                              <!-- Per-parcel commission editable field -->
                              <template v-slot:[`item.commission`]="{ item }">
                                <v-text-field
                                  v-model.number="parcelCommissionMap[item.tracking_number]"
                                  type="number"
                                  density="compact"
                                  hide-details
                                  class="ma-0 pa-0"
                                  style="max-width: 120px;"
                                />
                              </template>
                            </v-data-table>
                          </v-card-text>
                        </v-card>
                      </v-col>

                      <v-col cols="12" v-if="missingTrackingNumbers.length">
                        <v-alert type="warning" outlined>
                          {{ $t('common.clear') }}: 
                          <div class="mt-2">
                            <v-chip v-for="tn in missingTrackingNumbers" :key="tn" class="mr-2 mb-2" color="warning" text-color="white">{{ tn }}</v-chip>
                          </div>
                        </v-alert>
                      </v-col>

                    </v-row>

                    <v-row class="mt-4">
                      <v-col cols="12" class="text-right">
                        <v-btn color="grey" text @click="goBack" class="mr-3">{{ $t('common.cancel') }}</v-btn>
                        <v-btn type="submit" color="primary" :loading="processing" :disabled="!canSubmit" elevation="2">
                          <v-icon left>mdi-check</v-icon>
                          {{ $t('common.import') }}
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
import { Head, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';

export default {
  name: 'DriverSettlementImport',
  components: { Head, AppLayout },
  setup() {
    const { t } = useI18n();
    const headers = [
      { text: t('parcels.tracking_number'), value: 'tracking_number', sortable: false },
      { text: t('parcels.cod_amount') || 'COD Amount', value: 'cod_amount', sortable: true },
      { text: t('driverSettlement.net_amount') || 'Net Amount', value: 'net_amount', sortable: false },
      { text: t('parcels.company') || 'Company', value: 'company_name', sortable: false },
      { text: t('drivers.headers.commission'), value: 'commission', sortable: false },
      { text: t('parcels.status'), value: 'status', sortable: false }
    ];
    const goBack = () => router.visit('/recoltes')
    return { t, headers, goBack };
  },
  props: {
    drivers: { type: Array, default: () => [] },
    activeCases: { type: Array, default: () => [] }
  },
  data() {
    return {
      xlsxFile: null,
      selectedDriverId: null,
      driverCommission: 0,
      caseId: null,
      foundParcels: [],
      missingTrackingNumbers: [],
      selectedTrackingNumbers: [],
      parseSummary: null,
      processing: false,
      errors: {},
      parcelCommissionMap: {}
    }
  },
  watch: {
    // When driver changes, auto-fill the commission from driver config
    selectedDriverId(newId) {
      const driver = this.drivers.find(d => d.id === newId);
      if (driver) {
        // Use commission_rate if active; for now assume fixed amount per parcel used in settlement
        if (driver.commission_is_active && driver.commission_rate != null) {
          this.driverCommission = Number(driver.commission_rate) || 0;
        }
        // Propagate to per-parcel map
        this.applyCommissionToAllParcels();
      }
    },
    // If global commission changes manually, propagate defaults to unedited parcels
    driverCommission() {
      this.applyCommissionToAllParcels();
    },
    // When found parcels list updates, initialize commission map
    foundParcels() {
      this.applyCommissionToAllParcels(true);
    }
  },
  computed: {
    canSubmit() {
      return this.selectedDriverId && this.driverCommission >= 0 && this.selectedTrackingNumbers.length > 0 && !this.processing;
    },
    totalCod() {
      return this.foundParcels.reduce((sum, p) => sum + (Number(p.cod_amount) || 0), 0);
    },
    totalNet() {
      return this.foundParcels.reduce((sum, p) => sum + this.netAmountForParcel(p), 0);
    }
  },
  methods: {
    formatCurrency(amount) {
      return new Intl.NumberFormat('fr-DZ', { style: 'currency', currency: 'DZD', minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(amount || 0);
    },
    showError(message) {
      if (this.$toast && typeof this.$toast.error === 'function') {
        this.$toast.error(message);
      } else {
        console.error(message);
        try { alert(message); } catch (_) {}
      }
    },
    showSuccess(message) {
      if (this.$toast && typeof this.$toast.success === 'function') {
        this.$toast.success(message);
      } else {
        console.log(message);
      }
    },
    applyCommissionToAllParcels(force = false) {
      if (!Array.isArray(this.foundParcels)) return;
      const defaultCommission = Number(this.driverCommission) || 0;
      this.foundParcels.forEach(p => {
        const tn = p.tracking_number;
        if (force || this.parcelCommissionMap[tn] == null || this.parcelCommissionMap[tn] === '') {
          this.parcelCommissionMap[tn] = defaultCommission;
        }
      });
    },
    netAmountForParcel(p) {
      const commission = Number(this.parcelCommissionMap[p.tracking_number] ?? this.driverCommission ?? 0) || 0;
      const cod = Number(p.cod_amount) || 0;
      const net = cod - commission;
      return net > 0 ? net : 0;
    },
    async onXlsxChange() {
      this.foundParcels = [];
      this.missingTrackingNumbers = [];
      this.selectedTrackingNumbers = [];
      this.parseSummary = null;
      this.parcelCommissionMap = {};

      if (!this.xlsxFile) return;
      const formData = new FormData();
      formData.append('xlsx_file', this.xlsxFile);

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
          // Preselect found rows (v-data-table selection expects item objects)
          this.selectedTrackingNumbers = [...this.foundParcels];
          // Initialize commission map defaults
          this.applyCommissionToAllParcels(true);
        } else {
          this.showError('Failed to parse XLSX');
        }
      } catch (e) {
        console.error('Parse error', e);
        this.showError('Failed to parse XLSX');
      }
    },
    async submitProcess() {
      if (!this.canSubmit) return;
      this.processing = true;
      this.errors = {};
      try {
        // Ensure driver commission is a valid non-negative number
        const safeDriverCommission = Number(this.driverCommission);
        const payload = {
          driver_id: this.selectedDriverId,
          driver_commission: isFinite(safeDriverCommission) && safeDriverCommission >= 0 ? safeDriverCommission : 0,
          case_id: this.caseId ?? null,
          tracking_numbers: (this.selectedTrackingNumbers || [])
            .map(p => String((p && p.tracking_number) ? p.tracking_number : '').trim())
            .filter(tn => tn.length > 0),
          parcel_commissions: Object.fromEntries(
            Object.entries(this.parcelCommissionMap || {}).map(([tn, val]) => {
              const key = String(tn).trim();
              const num = Number(val);
              return [key, isFinite(num) && num >= 0 ? num : 0];
            })
          )
        };
        const resp = await fetch('/driver-settlement/process', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
          },
          body: JSON.stringify(payload)
        });
        const data = await resp.json();
        if (resp.status === 422) {
          // Laravel validation error structure
          this.errors = data.errors || {};
          const firstError = Object.values(this.errors)[0]?.[0] || 'Validation error';
          this.showError(firstError);
          return;
        }
        if (data.success) {
          this.showSuccess('Processing completed');
          const redirectUrl = data.redirect || '/recoltes';
          router.visit(redirectUrl);
        } else {
          this.errors = data.errors || {};
          this.showError(data.error || 'Failed to process');
        }
      } catch (e) {
        console.error('Process error', e);
        this.showError('Failed to process');
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