<template>
    <CentralLayout title="Tenants">
        <v-card class="elevation-1 rounded-lg">
            <v-card-title class="d-flex align-center py-4 px-6">
                <span class="text-h5 font-weight-bold">Tenants</span>
                <v-spacer></v-spacer>
                <v-btn
                    color="primary"
                    prepend-icon="mdi-plus"
                    @click="$inertia.visit('/tenants/create')"
                >
                    Create Tenant
                </v-btn>
            </v-card-title>
            <v-divider></v-divider>
            <v-data-table
                :headers="headers"
                :items="tenants"
                class="pa-4"
                hover
            >
                <template v-slot:item.domains="{ item }">
                    <v-chip
                        v-for="domain in item.domains"
                        :key="domain.id"
                        size="small"
                        color="primary"
                        class="mr-1"
                    >
                        {{ domain.domain }}
                    </v-chip>
                </template>
                <template v-slot:item.billing_status="{ item }">
                    <v-chip
                        size="small"
                        :color="getStatusColor(item.billing_status)"
                        class="text-capitalize"
                    >
                        {{ item.billing_status }}
                    </v-chip>
                </template>
                <template v-slot:item.billing_expires_at="{ item }">
                    <div v-if="item.billing_expires_at">
                        <div>{{ formatDate(item.billing_expires_at) }}</div>
                        <div class="text-caption text-grey">
                            {{ getRelativeTime(item.billing_expires_at) }}
                        </div>
                    </div>
                    <span v-else class="text-grey">N/A</span>
                </template>
                <template v-slot:item.actions="{ item }">
                    <v-tooltip location="top" text="Renew Subscription">
                        <template v-slot:activator="{ props }">
                            <v-btn
                                icon
                                size="small"
                                variant="text"
                                color="info"
                                v-bind="props"
                                @click="openRenewDialog(item)"
                            >
                                <v-icon>mdi-calendar-plus</v-icon>
                            </v-btn>
                        </template>
                    </v-tooltip>
                    <v-tooltip location="top" text="Payment History">
                        <template v-slot:activator="{ props }">
                            <v-btn
                                icon
                                size="small"
                                variant="text"
                                color="purple"
                                v-bind="props"
                                @click="openHistoryDialog(item)"
                            >
                                <v-icon>mdi-history</v-icon>
                            </v-btn>
                        </template>
                    </v-tooltip>
                    <v-btn
                        icon
                        size="small"
                        variant="text"
                        color="primary"
                        @click="$inertia.visit(`/tenants/${item.id}/edit`)"
                    >
                        <v-icon>mdi-pencil</v-icon>
                    </v-btn>
                    <v-btn
                        icon
                        size="small"
                        variant="text"
                        color="success"
                        :href="`http://${item.domains[0]?.domain}`"
                        target="_blank"
                    >
                        <v-icon>mdi-open-in-new</v-icon>
                    </v-btn>
                </template>
            </v-data-table>
        </v-card>

        <!-- Renew Dialog -->
        <v-dialog v-model="renewDialog" max-width="400">
            <v-card>
                <v-card-title>Renew Subscription</v-card-title>
                <v-card-text>
                    <p class="mb-4">
                        Extend subscription for <strong>{{ selectedTenant?.id }}</strong>.
                    </p>
                    <v-text-field
                        v-model.number="renewForm.months"
                        label="Months to Add"
                        type="number"
                        min="1"
                        variant="outlined"
                        :error-messages="renewForm.errors.months"
                    ></v-text-field>

                    <v-text-field
                        v-model.number="renewForm.amount"
                        label="Amount Paid"
                        type="number"
                        min="0"
                        variant="outlined"
                        class="mb-2"
                        :error-messages="renewForm.errors.amount"
                    ></v-text-field>

                    <v-select
                        v-model="renewForm.method"
                        label="Payment Method"
                        :items="['cash', 'transfer', 'check', 'online']"
                        variant="outlined"
                        class="mb-2"
                        :error-messages="renewForm.errors.method"
                    ></v-select>

                    <v-text-field
                        v-model="renewForm.reference"
                        label="Reference / Transaction ID"
                        variant="outlined"
                        class="mb-2"
                        :error-messages="renewForm.errors.reference"
                    ></v-text-field>

                    <v-textarea
                        v-model="renewForm.notes"
                        label="Notes"
                        variant="outlined"
                        rows="2"
                        :error-messages="renewForm.errors.notes"
                    ></v-textarea>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="grey" variant="text" @click="renewDialog = false">Cancel</v-btn>
                    <v-btn
                        color="primary"
                        :loading="renewForm.processing"
                        @click="submitRenew"
                    >
                        Confirm Renewal
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- History Dialog -->
        <v-dialog v-model="historyDialog" max-width="600">
            <v-card>
                <v-card-title>Payment History</v-card-title>
                <v-card-text>
                    <p class="mb-4">
                        History for <strong>{{ selectedTenant?.id }}</strong>.
                    </p>
                    <v-table v-if="payments.length > 0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Ref</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="payment in payments" :key="payment.id">
                                <td>{{ formatDate(payment.paid_at) }}</td>
                                <td>{{ payment.amount }} {{ payment.currency }}</td>
                                <td>{{ payment.method }}</td>
                                <td>{{ payment.reference || '-' }}</td>
                            </tr>
                        </tbody>
                    </v-table>
                    <div v-else class="text-center text-grey py-4">
                        No payment history found.
                    </div>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="primary" variant="text" @click="historyDialog = false">Close</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </CentralLayout>
</template>

<script>
import CentralLayout from "@/Layouts/CentralLayout.vue";
import { useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import { formatDistanceToNow, format } from "date-fns";

export default {
    components: {
        CentralLayout,
    },
    props: {
        tenants: Array,
    },
    setup() {
        const renewDialog = ref(false);
        const selectedTenant = ref(null);
        const historyDialog = ref(false);
        const payments = ref([]);
        const renewForm = useForm({
            months: 1,
            amount: 0,
            method: 'cash',
            reference: '',
            notes: '',
        });

        const openRenewDialog = (tenant) => {
            selectedTenant.value = tenant;
            renewForm.months = 1;
            renewForm.clearErrors();
            renewForm.months = 1;
            renewForm.amount = 0;
            renewForm.method = 'cash';
            renewForm.reference = '';
            renewForm.notes = '';
            renewForm.clearErrors();
            renewDialog.value = true;
        };

        const openHistoryDialog = async (tenant) => {
            selectedTenant.value = tenant;
            payments.value = [];
            historyDialog.value = true;
            try {
                const response = await window.axios.get(`/tenants/${tenant.id}/payments`);
                payments.value = response.data;
            } catch (e) {
                console.error("Failed to load history", e);
            }
        };

        const submitRenew = () => {
            if (!selectedTenant.value) return;
            renewForm.post(`/tenants/${selectedTenant.value.id}/renew`, {
                onSuccess: () => {
                    renewDialog.value = false;
                    selectedTenant.value = null;
                },
            });
        };

        const getStatusColor = (status) => {
            switch (status) {
                case "active":
                    return "success";
                case "trial":
                    return "info";
                case "suspended":
                case "expired":
                    return "error";
                default:
                    return "grey";
            }
        };

        const formatDate = (date) => {
            if (!date) return "";
            return format(new Date(date), "MMM dd, yyyy");
        };

        const getRelativeTime = (date) => {
            if (!date) return "";
            const d = new Date(date);
            return d < new Date()
                ? `Expired ${formatDistanceToNow(d)} ago`
                : `Expires in ${formatDistanceToNow(d)}`;
        };

        return {
            renewDialog,
            historyDialog,
            payments,
            selectedTenant,
            renewForm,
            openRenewDialog,
            openHistoryDialog,
            submitRenew,
            getStatusColor,
            formatDate,
            getRelativeTime,
        };
    },
    data() {
        return {
            headers: [
                { title: "ID", key: "id" },
                { title: "Domains", key: "domains" },
                { title: "Plan", key: "plan" },
                { title: "Billing Status", key: "billing_status" },
                { title: "Expires At", key: "billing_expires_at" },
                { title: "Actions", key: "actions", sortable: false },
            ],
        };
    },
};
</script>
