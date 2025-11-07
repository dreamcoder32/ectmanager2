<template>
    <AppLayout>
        <Head title="WhatsApp Management" />

        <template #title>
            <div class="d-flex justify-space-between align-center">
                <span class="text-h4 font-weight-bold">
                    WhatsApp Management
                </span>
                <v-btn
                    color="primary"
                    @click="showBulkMessageDialog = true"
                    :disabled="selectedParcels.length === 0"
                >
                    <v-icon left>mdi-message-multiple</v-icon>
                    Send Bulk Messages ({{ selectedParcels.length }})
                </v-btn>
            </div>
        </template>

        <template #content>
            <v-container fluid>
                <!-- Session Status -->
                <v-card class="mb-4" elevation="2">
                    <v-card-title
                        class="d-flex justify-space-between align-center"
                    >
                        <span>WhatsApp Session Status</span>
                        <v-btn
                            icon="mdi-refresh"
                            variant="text"
                            color="primary"
                            @click="fetchSessionInfo"
                            :loading="sessionInfo.loading"
                            :disabled="
                                !selectedCompany ||
                                !selectedCompany.whatsapp_api_key
                            "
                        ></v-btn>
                    </v-card-title>
                    <v-card-text>
                        <div v-if="!selectedCompany">
                            <v-alert type="info" variant="tonal">
                                Select a company to view WhatsApp session
                                status.
                            </v-alert>
                        </div>
                        <div v-else-if="!selectedCompany.whatsapp_api_key">
                            <v-alert type="warning" variant="tonal">
                                WhatsApp API key is not configured for
                                {{ selectedCompany.name }}.
                            </v-alert>
                        </div>
                        <div v-else>
                            <v-alert
                                v-if="sessionInfo.loading"
                                type="info"
                                variant="tonal"
                            >
                                Checking session status...
                            </v-alert>
                            <v-alert
                                v-else-if="sessionInfo.error"
                                type="error"
                                variant="tonal"
                            >
                                {{ sessionInfo.error }}
                            </v-alert>
                            <div v-else>
                                <div class="d-flex align-center mb-3">
                                    <v-chip
                                        :color="
                                            sessionInfo.status === 'CONNECTED'
                                                ? 'success'
                                                : sessionInfo.needsQr
                                                  ? 'error'
                                                  : 'warning'
                                        "
                                        text-color="white"
                                        class="mr-3"
                                    >
                                        {{ sessionInfo.status || "UNKNOWN" }}
                                    </v-chip>
                                    <span
                                        v-if="formattedLastChecked"
                                        class="text-caption text-medium-emphasis"
                                    >
                                        Last checked: {{ formattedLastChecked }}
                                    </span>
                                </div>
                                <v-alert
                                    v-if="sessionInfo.needsQr"
                                    type="warning"
                                    variant="tonal"
                                    class="mb-3"
                                >
                                    <div
                                        class="d-flex justify-space-between align-center"
                                    >
                                        <span
                                            >Session disconnected. Please scan
                                            the QR code to reconnect.</span
                                        >
                                        <v-btn
                                            v-if="sessionInfo.qrCodeImage"
                                            color="warning"
                                            variant="tonal"
                                            @click="openQrDialog"
                                            prepend-icon="mdi-qrcode"
                                        >
                                            Show QR Code
                                        </v-btn>
                                    </div>
                                </v-alert>
                                <v-alert
                                    v-else-if="sessionInfo.error"
                                    type="error"
                                    variant="tonal"
                                >
                                    {{ sessionInfo.error }}
                                </v-alert>
                                <v-alert v-else type="success" variant="tonal">
                                    <div class="d-flex align-center">
                                        <v-icon color="success" class="mr-2"
                                            >mdi-check-circle</v-icon
                                        >
                                        <span
                                            >WhatsApp session is connected and
                                            ready to send messages.</span
                                        >
                                    </div>
                                </v-alert>
                            </div>
                        </div>
                    </v-card-text>
                </v-card>

                <!-- Filters -->
                <v-card class="mb-4" elevation="2">
                    <v-card-text>
                        <v-row>
                            <v-col cols="12" md="3">
                                <v-select
                                    v-model="filters.status"
                                    :items="statusOptions"
                                    label="Status"
                                    clearable
                                    @update:model-value="applyFilters"
                                ></v-select>
                            </v-col>
                            <v-col cols="12" md="3">
                                <v-select
                                    v-model="filters.company_id"
                                    :items="companies"
                                    item-title="name"
                                    item-value="id"
                                    label="Company"
                                    clearable
                                    @update:model-value="applyFilters"
                                ></v-select>
                            </v-col>
                            <v-col cols="12" md="4">
                                <v-text-field
                                    v-model="filters.search"
                                    label="Search by tracking number, name, or phone"
                                    prepend-inner-icon="mdi-magnify"
                                    clearable
                                    @keyup.enter="applyFilters"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" md="2">
                                <v-btn
                                    color="primary"
                                    @click="applyFilters"
                                    block
                                >
                                    <v-icon left>mdi-filter</v-icon>
                                    Filter
                                </v-btn>
                            </v-col>
                            <v-col cols="12" md="2">
                                <v-btn
                                    color="success"
                                    @click="showBulkVerifyDialog = true"
                                    :disabled="selectedParcels.length === 0"
                                    block
                                >
                                    <v-icon left>mdi-phone-check</v-icon>
                                    Verify Phones ({{ selectedParcels.length }})
                                </v-btn>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>

                <!-- Parcels Table -->
                <v-card elevation="2">
                    <v-card-title>
                        <span>Parcels</span>
                        <v-spacer></v-spacer>
                        <v-text-field
                            v-model="selectAll"
                            type="checkbox"
                            label="Select All"
                            @change="toggleSelectAll"
                        ></v-text-field>
                    </v-card-title>

                    <v-data-table
                        :headers="headers"
                        :items="parcels.data"
                        :loading="loading"
                        :items-per-page="15"
                        class="elevation-1"
                        show-select
                        v-model="selectedParcels"
                        item-key="id"
                    >
                        <template v-slot:item.company.name="{ item }">
                            <v-chip
                                :color="getCompanyColor(item.company)"
                                text-color="white"
                                small
                            >
                                {{ item.company.name }}
                            </v-chip>
                        </template>

                        <template v-slot:item.status="{ item }">
                            <v-chip
                                :color="getStatusColor(item.status)"
                                text-color="white"
                                small
                            >
                                {{ item.status }}
                            </v-chip>
                        </template>

                        <template v-slot:item.delivery_type="{ item }">
                            <v-chip
                                :color="
                                    item.delivery_type === 'stopdesk'
                                        ? 'orange'
                                        : 'green'
                                "
                                text-color="white"
                                small
                            >
                                {{
                                    item.delivery_type === "stopdesk"
                                        ? "Stop Desk"
                                        : "Home Delivery"
                                }}
                            </v-chip>
                        </template>

                        <template v-slot:item.has_whatsapp_tag="{ item }">
                            <v-icon
                                :color="
                                    item.has_whatsapp_tag ? 'green' : 'grey'
                                "
                                @click="toggleWhatsAppTag(item)"
                                style="cursor: pointer"
                            >
                                {{
                                    item.has_whatsapp_tag
                                        ? "mdi-whatsapp"
                                        : "mdi-whatsapp"
                                }}
                            </v-icon>
                        </template>

                        <template v-slot:item.phone_status="{ item }">
                            <div class="d-flex align-center">
                                <v-icon
                                    :color="getPhoneStatusColor(item)"
                                    size="small"
                                    class="mr-1"
                                >
                                    {{ getPhoneStatusIcon(item) }}
                                </v-icon>
                                <span class="text-caption">{{
                                    getPhoneStatusText(item)
                                }}</span>
                            </div>
                        </template>

                        <template v-slot:item.actions="{ item }">
                            <v-btn
                                icon
                                size="small"
                                @click="sendDeskPickupNotification(item)"
                                :disabled="!isCompanyConfigured(item.company)"
                                color="success"
                                title="Send Desk Pickup Notification"
                            >
                                <v-icon>mdi-bell-ring</v-icon>
                            </v-btn>
                            <v-btn
                                icon
                                size="small"
                                @click="openMessageDialog(item)"
                                :disabled="!isCompanyConfigured(item.company)"
                            >
                                <v-icon>mdi-message</v-icon>
                            </v-btn>
                            <v-btn
                                icon
                                size="small"
                                @click="viewMessageHistory(item)"
                            >
                                <v-icon>mdi-history</v-icon>
                            </v-btn>
                        </template>
                    </v-data-table>

                    <!-- Pagination -->
                    <v-pagination
                        v-model="currentPage"
                        :length="parcels.last_page"
                        @update:model-value="loadParcels"
                        class="pa-4"
                    ></v-pagination>
                </v-card>

                <!-- Message Dialog -->
                <v-dialog v-model="showMessageDialog" max-width="600px">
                    <v-card>
                        <v-card-title>
                            <span class="text-h5">Send WhatsApp Message</span>
                        </v-card-title>
                        <v-card-text>
                            <div v-if="selectedParcel">
                                <v-row>
                                    <v-col cols="12">
                                        <v-text-field
                                            label="Recipient"
                                            :value="`${selectedParcel.recipient_name} (${selectedParcel.recipient_phone})`"
                                            readonly
                                        ></v-text-field>
                                    </v-col>
                                    <v-col cols="12">
                                        <v-textarea
                                            v-model="messageText"
                                            label="Message"
                                            rows="4"
                                            placeholder="Type your message here..."
                                        ></v-textarea>
                                    </v-col>
                                </v-row>
                            </div>
                        </v-card-text>
                        <v-card-actions>
                            <v-spacer></v-spacer>
                            <v-btn
                                color="grey"
                                text
                                @click="closeMessageDialog"
                            >
                                Cancel
                            </v-btn>
                            <v-btn
                                color="primary"
                                @click="sendMessage"
                                :loading="sending"
                            >
                                Send Message
                            </v-btn>
                        </v-card-actions>
                    </v-card>
                </v-dialog>

                <!-- Bulk Message Dialog -->
                <v-dialog v-model="showBulkMessageDialog" max-width="600px">
                    <v-card>
                        <v-card-title>
                            <span class="text-h5"
                                >Send Bulk WhatsApp Messages</span
                            >
                        </v-card-title>
                        <v-card-text>
                            <v-alert type="info" class="mb-4">
                                You are about to send messages to
                                {{ selectedParcels.length }} parcels.
                            </v-alert>
                            <v-textarea
                                v-model="bulkMessageText"
                                label="Message"
                                rows="4"
                                placeholder="Type your message here..."
                            ></v-textarea>
                        </v-card-text>
                        <v-card-actions>
                            <v-spacer></v-spacer>
                            <v-btn
                                color="grey"
                                text
                                @click="showBulkMessageDialog = false"
                            >
                                Cancel
                            </v-btn>
                            <v-btn
                                color="primary"
                                @click="sendBulkMessages"
                                :loading="sending"
                            >
                                Send to All ({{ selectedParcels.length }})
                            </v-btn>
                        </v-card-actions>
                    </v-card>
                </v-dialog>

                <!-- Bulk Phone Verification Dialog -->
                <v-dialog v-model="showBulkVerifyDialog" max-width="500px">
                    <v-card>
                        <v-card-title>
                            <span class="text-h5"
                                >Verify WhatsApp Phone Numbers</span
                            >
                        </v-card-title>
                        <v-card-text>
                            <v-alert type="info" class="mb-4">
                                You are about to verify WhatsApp phone numbers
                                for {{ selectedParcels.length }} parcels. This
                                will check if the recipient and secondary phone
                                numbers are on WhatsApp.
                            </v-alert>
                            <v-alert type="warning" density="compact">
                                This operation uses the WhatsApp API and may
                                take some time to complete.
                            </v-alert>
                        </v-card-text>
                        <v-card-actions>
                            <v-spacer></v-spacer>
                            <v-btn
                                color="grey"
                                text
                                @click="showBulkVerifyDialog = false"
                            >
                                Cancel
                            </v-btn>
                            <v-btn
                                color="success"
                                @click="bulkVerifyPhones"
                                :loading="verifying"
                            >
                                Verify All ({{ selectedParcels.length }})
                            </v-btn>
                        </v-card-actions>
                    </v-card>
                </v-dialog>

                <!-- QR Code Dialog -->
                <v-dialog v-model="showQrDialog" max-width="500px" persistent>
                    <v-card>
                        <v-card-title
                            class="d-flex justify-space-between align-center bg-warning"
                        >
                            <span class="text-h5">
                                <v-icon class="mr-2">mdi-qrcode-scan</v-icon>
                                WhatsApp Session Disconnected
                            </span>
                            <v-btn
                                icon="mdi-close"
                                variant="text"
                                @click="closeQrDialog"
                            ></v-btn>
                        </v-card-title>
                        <v-card-text class="pa-6">
                            <!-- Debug: Show which condition is active -->
                            <div v-if="false" class="text-caption mb-2">
                                Loading: {{ sessionInfo.loading }} | QR Image:
                                {{ !!sessionInfo.qrCodeImage }} | QR Error:
                                {{ !!sessionInfo.qrError }}
                            </div>

                            <div
                                v-if="sessionInfo.loading"
                                class="text-center py-8"
                            >
                                <v-progress-circular
                                    indeterminate
                                    color="primary"
                                    size="64"
                                ></v-progress-circular>
                                <p class="mt-4 text-medium-emphasis">
                                    Loading QR code...
                                </p>
                            </div>
                            <div
                                v-else-if="sessionInfo.qrCodeImage"
                                class="d-flex flex-column align-center"
                            >
                                <v-alert
                                    type="info"
                                    variant="tonal"
                                    class="mb-4 w-100"
                                >
                                    <div class="text-body-2">
                                        <strong>Follow these steps:</strong>
                                        <ol class="mt-2 pl-4">
                                            <li>Open WhatsApp on your phone</li>
                                            <li>Tap Menu or Settings</li>
                                            <li>Tap Linked Devices</li>
                                            <li>Tap Link a Device</li>
                                            <li>Scan this QR code</li>
                                        </ol>
                                    </div>
                                </v-alert>
                                <v-card class="elevation-8 pa-4 mb-4">
                                    <v-img
                                        :src="sessionInfo.qrCodeImage"
                                        width="300"
                                        height="300"
                                        alt="WhatsApp Session QR Code"
                                    ></v-img>
                                </v-card>
                                <v-chip
                                    color="info"
                                    variant="tonal"
                                    prepend-icon="mdi-refresh"
                                >
                                    Auto-refreshing every 5 seconds...
                                </v-chip>
                                <p
                                    class="text-caption text-medium-emphasis mt-2 text-center"
                                >
                                    This dialog will close automatically once
                                    connected
                                </p>
                            </div>
                            <div v-else class="text-center py-8">
                                <v-alert type="error" variant="tonal">
                                    {{
                                        sessionInfo.qrError ||
                                        "Unable to retrieve QR code. Please try refreshing."
                                    }}
                                </v-alert>

                                <!-- Debug Information -->
                                <v-expansion-panels class="mt-4 mb-4">
                                    <v-expansion-panel>
                                        <v-expansion-panel-title>
                                            <v-icon left>mdi-bug</v-icon>
                                            Debug Information
                                        </v-expansion-panel-title>
                                        <v-expansion-panel-text>
                                            <div class="text-left">
                                                <p>
                                                    <strong>Status:</strong>
                                                    {{ sessionInfo.status }}
                                                </p>
                                                <p>
                                                    <strong>Needs QR:</strong>
                                                    {{ sessionInfo.needsQr }}
                                                </p>
                                                <p>
                                                    <strong
                                                        >QR Code Raw:</strong
                                                    >
                                                    {{
                                                        sessionInfo.qrCodeRaw
                                                            ? "Present (" +
                                                              sessionInfo
                                                                  .qrCodeRaw
                                                                  .length +
                                                              " chars)"
                                                            : "Not present"
                                                    }}
                                                </p>
                                                <p>
                                                    <strong
                                                        >QR Code Image:</strong
                                                    >
                                                    {{
                                                        sessionInfo.qrCodeImage
                                                            ? "Generated"
                                                            : "Not generated"
                                                    }}
                                                </p>
                                                <p>
                                                    <strong>Error:</strong>
                                                    {{
                                                        sessionInfo.error ||
                                                        "None"
                                                    }}
                                                </p>
                                                <p>
                                                    <strong>QR Error:</strong>
                                                    {{
                                                        sessionInfo.qrError ||
                                                        "None"
                                                    }}
                                                </p>
                                                <p class="mt-2">
                                                    <small
                                                        >Check browser console
                                                        (F12) for detailed
                                                        logs</small
                                                    >
                                                </p>
                                            </div>
                                        </v-expansion-panel-text>
                                    </v-expansion-panel>
                                </v-expansion-panels>

                                <v-btn
                                    color="primary"
                                    @click="fetchSessionInfo"
                                    :loading="sessionInfo.loading"
                                    class="mt-4"
                                >
                                    <v-icon left>mdi-refresh</v-icon>
                                    Retry
                                </v-btn>
                            </div>
                        </v-card-text>
                        <v-card-actions v-if="!sessionInfo.loading">
                            <v-spacer></v-spacer>
                            <v-btn
                                color="primary"
                                variant="text"
                                @click="fetchSessionInfo"
                                prepend-icon="mdi-refresh"
                            >
                                Refresh
                            </v-btn>
                            <v-btn
                                color="grey"
                                variant="text"
                                @click="closeQrDialog"
                            >
                                Close
                            </v-btn>
                        </v-card-actions>
                    </v-card>
                </v-dialog>

                <!-- Snackbar for notifications -->
                <v-snackbar
                    v-model="snackbar.show"
                    :color="snackbar.color"
                    :timeout="snackbar.timeout"
                >
                    {{ snackbar.message }}
                </v-snackbar>
            </v-container>
        </template>
    </AppLayout>
</template>

<script>
import { ref, reactive, computed, watch, onUnmounted } from "vue";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import { Head } from "@inertiajs/vue3";
import QRCode from "qrcode";

export default {
    components: {
        AppLayout,
        Head,
    },
    props: {
        parcels: Object,
        filters: Object,
        companies: Array,
    },
    setup(props) {
        const loading = ref(false);
        const sending = ref(false);
        const verifying = ref(false);
        const filters = reactive({
            status: props.filters?.status ?? null,
            company_id: props.filters?.company_id ?? null,
            search: props.filters?.search ?? "",
        });
        const sessionInfo = reactive({
            loading: false,
            status: "UNKNOWN",
            needsQr: false,
            qrCodeRaw: null,
            qrCodeImage: null,
            error: null,
            qrError: null,
            lastCheckedAt: null,
        });

        // Watch sessionInfo changes for debugging
        watch(
            () => sessionInfo.qrCodeImage,
            (newVal, oldVal) => {
                console.log("👀 sessionInfo.qrCodeImage changed:", {
                    old: oldVal ? "Set" : "Not set",
                    new: newVal ? "Set" : "Not set",
                    newLength: newVal?.length,
                });
            },
        );

        watch(
            () => sessionInfo.loading,
            (newVal) => {
                console.log("👀 sessionInfo.loading changed:", newVal);
            },
        );

        // Watch for Show QR Code button visibility
        watch(
            () => ({
                needsQr: sessionInfo.needsQr,
                hasImage: !!sessionInfo.qrCodeImage,
            }),
            (newVal) => {
                console.log(
                    "🔘 Show QR Code button should be visible:",
                    newVal.needsQr && newVal.hasImage,
                );
                console.log("   - needsQr:", newVal.needsQr);
                console.log("   - hasImage:", newVal.hasImage);
            },
            { deep: true },
        );
        const showQrDialog = ref(false);
        const qrRefreshInterval = ref(null);
        const selectedParcels = ref([]);
        const showMessageDialog = ref(false);
        const showBulkMessageDialog = ref(false);
        const showBulkVerifyDialog = ref(false);
        const selectedParcel = ref(null);
        const messageText = ref("");
        const bulkMessageText = ref("");
        const currentPage = ref(1);

        const snackbar = reactive({
            show: false,
            message: "",
            color: "success",
            timeout: 3000,
        });

        watch(
            () => ({ ...props.filters }),
            (newFilters) => {
                filters.status = newFilters?.status ?? null;
                filters.company_id = newFilters?.company_id ?? null;
                filters.search = newFilters?.search ?? "";
            },
        );

        const resetSessionInfo = () => {
            sessionInfo.loading = false;
            sessionInfo.status = null;
            sessionInfo.needsQr = false;
            sessionInfo.qrCodeRaw = null;
            sessionInfo.qrCodeImage = null;
            sessionInfo.error = null;
            sessionInfo.qrError = null;
            sessionInfo.lastCheckedAt = null;
            clearQrRefreshInterval();
        };

        const clearQrRefreshInterval = () => {
            if (qrRefreshInterval.value) {
                clearInterval(qrRefreshInterval.value);
                qrRefreshInterval.value = null;
            }
        };

        const startQrRefreshInterval = () => {
            clearQrRefreshInterval();
            // Check session status every 5 seconds when QR is displayed
            qrRefreshInterval.value = setInterval(() => {
                if (sessionInfo.needsQr && selectedCompany.value) {
                    fetchSessionInfo();
                }
            }, 5000);
        };

        const selectedCompany = computed(() => {
            if (
                filters.company_id === null ||
                filters.company_id === undefined ||
                filters.company_id === ""
            ) {
                return null;
            }

            const companyId = Number(filters.company_id);
            if (Number.isNaN(companyId)) {
                return null;
            }

            return (
                props.companies.find((company) => company.id === companyId) ||
                null
            );
        });

        const fetchSessionInfo = async () => {
            const company = selectedCompany.value;

            if (!company || !company.whatsapp_api_key) {
                console.log("❌ No company or API key");
                return;
            }

            console.log("🔄 Fetching session info for company:", company.name);
            sessionInfo.loading = true;
            sessionInfo.error = null;
            sessionInfo.qrError = null;

            try {
                const url = `/whatsapp/companies/${company.id}/session-status`;
                console.log("📡 Fetching from:", url);

                const response = await fetch(url, {
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                });

                console.log("📥 Response status:", response.status);
                const result = await response.json();
                console.log("📦 Response data:", result);

                if (!response.ok || !result.success) {
                    throw new Error(
                        result.error ||
                            "Unable to fetch WhatsApp session status",
                    );
                }

                sessionInfo.status = result.data?.status || "UNKNOWN";
                sessionInfo.needsQr = !!result.data?.needs_qr;
                sessionInfo.qrError = result.data?.qr_error || null;
                sessionInfo.lastCheckedAt =
                    result.data?.checked_at || new Date().toISOString();

                const qrCodeValue = result.data?.qr_code || null;
                console.log(
                    "🔍 QR Code value received:",
                    qrCodeValue ? `Yes (${qrCodeValue.length} chars)` : "No",
                );
                console.log("🔍 Needs QR:", sessionInfo.needsQr);
                console.log("🔍 Status:", sessionInfo.status);

                sessionInfo.qrCodeRaw = qrCodeValue;

                if (qrCodeValue) {
                    if (
                        typeof qrCodeValue === "string" &&
                        qrCodeValue.startsWith("data:")
                    ) {
                        console.log("✅ QR code is already a data URL");
                        sessionInfo.qrCodeImage = qrCodeValue;
                    } else {
                        try {
                            console.log("🎨 Converting QR code to data URL...");
                            sessionInfo.qrCodeImage = await QRCode.toDataURL(
                                qrCodeValue,
                                {
                                    width: 300,
                                    margin: 2,
                                    color: {
                                        dark: "#000000",
                                        light: "#FFFFFF",
                                    },
                                },
                            );
                            console.log(
                                "✅ QR code image generated successfully",
                            );
                        } catch (qrRenderError) {
                            console.error(
                                "❌ QR code render error:",
                                qrRenderError,
                            );
                            sessionInfo.qrCodeImage = null;
                            sessionInfo.qrError =
                                sessionInfo.qrError ||
                                "Unable to render QR code";
                        }
                    }
                } else {
                    console.log("⚠️ No QR code value in response");
                    sessionInfo.qrCodeImage = null;
                }

                console.log(
                    "🖼️ Final qrCodeImage:",
                    sessionInfo.qrCodeImage ? "Set" : "Not set",
                );
                console.log(
                    "🖼️ qrCodeImage type:",
                    typeof sessionInfo.qrCodeImage,
                );
                console.log(
                    "🖼️ qrCodeImage length:",
                    sessionInfo.qrCodeImage?.length,
                );
                console.log(
                    "🖼️ qrCodeImage preview:",
                    sessionInfo.qrCodeImage?.substring(0, 100),
                );

                // Start auto-refresh if QR code is needed
                if (sessionInfo.needsQr && sessionInfo.qrCodeImage) {
                    console.log("🔄 Starting auto-refresh interval");
                    startQrRefreshInterval();
                } else {
                    console.log("⏹️ Not starting auto-refresh");
                    clearQrRefreshInterval();
                }
            } catch (error) {
                console.error("❌ Error fetching session info:", error);
                sessionInfo.error =
                    error.message || "Failed to load WhatsApp session status";
            } finally {
                sessionInfo.loading = false;
            }
        };

        watch(
            selectedCompany,
            (company, oldCompany) => {
                resetSessionInfo();

                if (!company) {
                    sessionInfo.loading = false;
                    return;
                }

                if (!company.whatsapp_api_key) {
                    sessionInfo.status = "NOT_CONFIGURED";
                    return;
                }

                fetchSessionInfo();
            },
            { immediate: true },
        );

        // Watch for session status changes to close QR dialog when connected
        watch(
            () => sessionInfo.status,
            (newStatus) => {
                if (newStatus === "CONNECTED" || newStatus === "WORKING") {
                    if (showQrDialog.value) {
                        showQrDialog.value = false;
                        showSnackbar(
                            "WhatsApp session connected successfully!",
                            "success",
                        );
                    }
                    clearQrRefreshInterval();
                }
            },
        );

        // Cleanup interval on unmount
        onUnmounted(() => {
            clearQrRefreshInterval();
        });

        const formattedLastChecked = computed(() => {
            if (!sessionInfo.lastCheckedAt) return null;

            try {
                return new Date(sessionInfo.lastCheckedAt).toLocaleString();
            } catch (error) {
                return sessionInfo.lastCheckedAt;
            }
        });

        const statusOptions = [
            { title: "Pending", value: "pending" },
            { title: "Collected", value: "collected" },
            { title: "In Transit", value: "in_transit" },
            { title: "Delivered", value: "delivered" },
            { title: "Returned", value: "returned" },
            { title: "Cancelled", value: "cancelled" },
        ];

        const headers = [
            {
                title: "Tracking Number",
                key: "tracking_number",
                sortable: true,
            },
            { title: "Company", key: "company.name", sortable: true },
            { title: "Recipient", key: "recipient_name", sortable: true },
            { title: "Phone", key: "recipient_phone", sortable: true },
            { title: "Status", key: "status", sortable: true },
            { title: "Delivery Type", key: "delivery_type", sortable: true },
            { title: "COD Amount", key: "cod_amount", sortable: true },
            // { title: 'WhatsApp Tag', key: 'has_whatsapp_tag', sortable: true },
            { title: "Phone Status", key: "phone_status", sortable: true },
            { title: "Actions", key: "actions", sortable: false },
        ];

        const selectAll = computed({
            get: () =>
                selectedParcels.value.length === props.parcels.data.length &&
                props.parcels.data.length > 0,
            set: (value) => {
                if (value) {
                    // Since item-key="id", we need to store just the IDs
                    selectedParcels.value = props.parcels.data.map((p) => p.id);
                } else {
                    selectedParcels.value = [];
                }
            },
        });

        const applyFilters = () => {
            router.get(
                "/whatsapp",
                {
                    status: filters.status,
                    company_id: filters.company_id,
                    search: filters.search,
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                },
            );
        };

        const loadParcels = () => {
            router.get(
                "/whatsapp",
                {
                    status: filters.status,
                    company_id: filters.company_id,
                    search: filters.search,
                    page: currentPage.value,
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                },
            );
        };

        const getCompanyColor = (company) => {
            const colors = [
                "primary",
                "secondary",
                "success",
                "warning",
                "error",
                "info",
            ];
            return colors[company.id % colors.length];
        };

        const getStatusColor = (status) => {
            const colors = {
                pending: "orange",
                collected: "blue",
                in_transit: "purple",
                delivered: "green",
                returned: "red",
                cancelled: "grey",
            };
            return colors[status] || "grey";
        };

        const isCompanyConfigured = (company) => {
            return (
                company.whatsapp_api_key && company.whatsapp_api_key.length > 0
            );
        };

        const openMessageDialog = (parcel) => {
            selectedParcel.value = parcel;
            messageText.value = "";
            showMessageDialog.value = true;
        };

        const closeMessageDialog = () => {
            showMessageDialog.value = false;
            selectedParcel.value = null;
            messageText.value = "";
        };

        const sendMessage = async () => {
            if (!messageText.value.trim()) return;

            sending.value = true;
            try {
                const response = await fetch(
                    `/whatsapp/parcels/${selectedParcel.value.id}/send-message`,
                    {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document
                                .querySelector('meta[name="csrf-token"]')
                                .getAttribute("content"),
                        },
                        body: JSON.stringify({
                            message: messageText.value,
                        }),
                    },
                );

                const result = await response.json();

                if (result.success) {
                    showSnackbar("Message sent successfully!", "success");
                    closeMessageDialog();
                } else if (result.session_disconnected) {
                    handleDisconnectedSession();
                } else {
                    showSnackbar(
                        result.error || "Failed to send message",
                        "error",
                    );
                }
            } catch (error) {
                showSnackbar("Error sending message", "error");
            } finally {
                sending.value = false;
            }
        };

        const handleDisconnectedSession = () => {
            sessionInfo.needsQr = true;
            showQrDialog.value = true;
            showSnackbar(
                "WhatsApp session is disconnected. Please scan the QR code to reconnect.",
                "warning",
            );
            fetchSessionInfo();
        };

        const openQrDialog = () => {
            if (sessionInfo.needsQr && sessionInfo.qrCodeImage) {
                showQrDialog.value = true;
                startQrRefreshInterval();
            }
        };

        const closeQrDialog = () => {
            showQrDialog.value = false;
            clearQrRefreshInterval();
        };

        const fetchQrCode = async () => {
            const company = selectedCompany.value;
            if (!company) {
                showSnackbar("Please select a company first.", "error");
                return;
            }

            sessionInfo.qrError = null;
            try {
                const response = await fetch(
                    `/whatsapp/companies/${company.id}/qrcode`,
                );
                const result = await response.json();

                if (result.success && result.qr_code) {
                    sessionInfo.qrCodeImage = await QRCode.toDataURL(
                        result.qr_code,
                        {
                            width: 300,
                            margin: 2,
                            color: {
                                dark: "#000000",
                                light: "#FFFFFF",
                            },
                        },
                    );
                    sessionInfo.needsQr = true;
                    showQrDialog.value = true;
                    startQrRefreshInterval();
                } else {
                    throw new Error(result.error || "Failed to fetch QR code.");
                }
            } catch (error) {
                sessionInfo.qrError = error.message;
                showSnackbar(error.message, "error");
            }
        };

        const sendBulkMessages = async () => {
            if (!bulkMessageText.value.trim()) return;

            sending.value = true;
            try {
                // selectedParcels.value contains just the IDs (because item-key="id")
                // So we can use them directly!
                const parcelIds = selectedParcels.value.filter(
                    (id) => id !== null && id !== undefined,
                );

                if (parcelIds.length === 0) {
                    showSnackbar(
                        "No valid parcels selected for messaging",
                        "error",
                    );
                    sending.value = false;
                    return;
                }

                const response = await fetch("/whatsapp/send-bulk-messages", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                    body: JSON.stringify({
                        parcel_ids: parcelIds,
                        message: bulkMessageText.value,
                    }),
                });

                const result = await response.json();

                if (result.session_disconnected) {
                    handleDisconnectedSession();
                } else {
                    showSnackbar(
                        `Bulk messages sent! Success: ${result.success}, Failed: ${result.failed}`,
                        result.failed === 0 ? "success" : "warning",
                    );
                }

                showBulkMessageDialog.value = false;
                bulkMessageText.value = "";
                selectedParcels.value = [];
            } catch (error) {
                showSnackbar("Error sending bulk messages", "error");
            } finally {
                sending.value = false;
            }
        };

        const toggleWhatsAppTag = async (parcel) => {
            try {
                const response = await fetch(
                    `/whatsapp/parcels/${parcel.id}/toggle-tag`,
                    {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document
                                .querySelector('meta[name="csrf-token"]')
                                .getAttribute("content"),
                        },
                    },
                );

                const result = await response.json();

                if (result.success) {
                    parcel.has_whatsapp_tag = result.has_whatsapp_tag;
                    showSnackbar(
                        `WhatsApp tag ${result.has_whatsapp_tag ? "added" : "removed"}`,
                        "success",
                    );
                }
            } catch (error) {
                showSnackbar("Error updating WhatsApp tag", "error");
            }
        };

        const viewMessageHistory = (parcel) => {
            router.visit(`/whatsapp/parcels/${parcel.id}/messages`);
        };

        const sendDeskPickupNotification = async (parcel) => {
            if (
                !confirm(
                    `Send desk pickup notification to ${parcel.recipient_name}?`,
                )
            ) {
                return;
            }

            sending.value = true;
            try {
                const response = await fetch(
                    `/whatsapp/parcels/${parcel.id}/desk-pickup-notification`,
                    {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document
                                .querySelector('meta[name="csrf-token"]')
                                .getAttribute("content"),
                        },
                    },
                );

                const result = await response.json();

                if (result.success) {
                    showSnackbar(
                        "Desk pickup notification sent successfully!",
                        "success",
                    );
                } else if (result.session_disconnected) {
                    handleDisconnectedSession();
                } else {
                    showSnackbar(
                        `Failed to send notification: ${result.error}`,
                        "error",
                    );
                }
            } catch (error) {
                showSnackbar("Error sending desk pickup notification", "error");
            } finally {
                sending.value = false;
            }
        };

        const toggleSelectAll = () => {
            if (selectAll.value) {
                // Since item-key="id", we need to store just the IDs
                selectedParcels.value = props.parcels.data
                    .filter(
                        (parcel) =>
                            parcel.id !== null && parcel.id !== undefined,
                    )
                    .map((parcel) => parcel.id);
            } else {
                selectedParcels.value = [];
            }
        };

        const showSnackbar = (message, color = "success") => {
            snackbar.message = message;
            snackbar.color = color;
            snackbar.show = true;
        };

        const bulkVerifyPhones = async () => {
            verifying.value = true;
            try {
                // selectedParcels.value contains just the IDs (because item-key="id")
                // So we can use them directly!
                const parcelIds = selectedParcels.value.filter(
                    (id) => id !== null && id !== undefined,
                );

                if (parcelIds.length === 0) {
                    showSnackbar(
                        "No valid parcels selected for verification",
                        "error",
                    );
                    verifying.value = false;
                    return;
                }

                const response = await fetch("/whatsapp/bulk-verify-phones", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                    body: JSON.stringify({
                        parcel_ids: parcelIds,
                    }),
                });

                const result = await response.json();

                showSnackbar(
                    `Phone verification completed! Verified: ${result.data.verified}, Failed: ${result.data.failed}`,
                    result.data.failed === 0 ? "success" : "warning",
                );

                showBulkVerifyDialog.value = false;
                selectedParcels.value = [];

                // Reload the page to show updated phone status
                router.reload();
            } catch (error) {
                showSnackbar("Error verifying phone numbers", "error");
            } finally {
                verifying.value = false;
            }
        };

        const getPhoneStatusColor = (parcel) => {
            if (!parcel.whatsapp_verified_at) return "grey";
            if (
                parcel.recipient_phone_whatsapp ||
                parcel.secondary_phone_whatsapp
            )
                return "green";
            return "red";
        };

        const getPhoneStatusIcon = (parcel) => {
            if (!parcel.whatsapp_verified_at) return "mdi-help-circle";
            if (
                parcel.recipient_phone_whatsapp ||
                parcel.secondary_phone_whatsapp
            )
                return "mdi-check-circle";
            return "mdi-close-circle";
        };

        const getPhoneStatusText = (parcel) => {
            if (!parcel.whatsapp_verified_at) return "Not Verified";
            if (
                parcel.recipient_phone_whatsapp &&
                parcel.secondary_phone_whatsapp
            )
                return "Both on WA";
            if (parcel.recipient_phone_whatsapp) return "Primary on WA";
            if (parcel.secondary_phone_whatsapp) return "Secondary on WA";
            return "Not on WA";
        };

        return {
            loading,
            sending,
            verifying,
            filters,
            sessionInfo,
            selectedCompany,
            selectedParcels,
            showMessageDialog,
            showBulkMessageDialog,
            showBulkVerifyDialog,
            showQrDialog,
            selectedParcel,
            messageText,
            bulkMessageText,
            currentPage,
            snackbar,
            formattedLastChecked,
            statusOptions,
            headers,
            selectAll,
            applyFilters,
            loadParcels,
            fetchSessionInfo,
            getCompanyColor,
            getStatusColor,
            isCompanyConfigured,
            openMessageDialog,
            closeMessageDialog,
            sendMessage,
            sendBulkMessages,
            bulkVerifyPhones,
            getPhoneStatusColor,
            getPhoneStatusIcon,
            getPhoneStatusText,
            toggleWhatsAppTag,
            viewMessageHistory,
            sendDeskPickupNotification,
            toggleSelectAll,
            showSnackbar,
            handleDisconnectedSession,
            fetchQrCode,
        };
    },
};
</script>
