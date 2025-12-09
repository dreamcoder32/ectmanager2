<template>
    <div>
        <AppLayout>
            <Head :title="`Recolte #${recolte.code}`" />

            <template #title>
                <span
                    style="
                        background: linear-gradient(135deg, #1976d2, #1565c0);
                        -webkit-background-clip: text;
                        -webkit-text-fill-color: transparent;
                        background-clip: text;
                        font-weight: 600;
                    "
                >
                    Collection Transfer #{{ recolte.code }}
                </span>
            </template>

            <template #content>
                <v-btn
                    v-if="!recolte.transfer_request_id"
                    color="primary"
                    @click="$inertia.visit(`/recoltes/${recolte.id}/edit`)"
                >
                    <v-icon left>mdi-pencil</v-icon>
                    Edit Recolte
                </v-btn>

                <v-btn
                    color="grey"
                    @click="$inertia.visit('/recoltes')"
                    class="ml-2"
                >
                    <v-icon left>mdi-arrow-left</v-icon>
                    Back to Recoltes
                </v-btn>

                <v-btn
                    color="error"
                    class="ml-2"
                    @click="exportPdf(recolte.id)"
                >
                    <v-icon left>mdi-file-pdf-box</v-icon>
                    Export PDF
                </v-btn>

                <v-menu offset-y>
                    <template v-slot:activator="{ on, attrs }">
                        <v-btn icon v-bind="attrs" v-on="on" class="ml-2">
                            <v-avatar size="32">
                                <v-icon>mdi-account-circle</v-icon>
                            </v-avatar>
                        </v-btn>
                    </template>
                    <v-list>
                        <v-list-item @click="$inertia.visit('/profile')">
                            <v-list-item-title>Profile</v-list-item-title>
                        </v-list-item>
                        <v-list-item @click="logout">
                            <v-list-item-title>Log Out</v-list-item-title>
                        </v-list-item>
                    </v-list>
                </v-menu>

                <!-- Main Content -->
                <v-main>
                    <v-container fluid>
                        <v-row>
                            <!-- Recolte Information -->
                            <v-col cols="12">
                                <v-card>
                                    <v-card-title class="primary white--text">
                                        <v-icon left color="white"
                                            >mdi-cash-multiple</v-icon
                                        >
                                        Collection Transfer Details
                                    </v-card-title>
                                    <v-card-text>
                                        <v-row>
                                            <v-col cols="12" md="3">
                                                <div
                                                    class="text-subtitle-2 grey--text"
                                                >
                                                    Code
                                                </div>
                                                <div class="text-h6">
                                                    RCT-{{ recolte.code }}
                                                </div>
                                            </v-col>
                                            <v-col cols="12" md="3">
                                                <div
                                                    class="text-subtitle-2 grey--text"
                                                >
                                                    Created By
                                                </div>
                                                <div class="text-h6">
                                                    {{
                                                        recolte.created_by
                                                            ?.name || "N/A"
                                                    }}
                                                </div>
                                            </v-col>
                                            <v-col cols="12" md="3">
                                                <div
                                                    class="text-subtitle-2 grey--text"
                                                >
                                                    Total Collections
                                                </div>
                                                <div class="text-h6">
                                                    {{
                                                        recolte.collections
                                                            ?.length || 0
                                                    }}
                                                </div>
                                            </v-col>
                                            <v-col cols="12" md="3">
                                                <div
                                                    class="text-subtitle-2 grey--text"
                                                >
                                                    Total COD
                                                </div>
                                                <div class="text-h6">
                                                    {{
                                                        formatAmount(
                                                            totalAmount,
                                                        )
                                                    }}
                                                    Da
                                                </div>
                                            </v-col>
                                            <v-col
                                                cols="12"
                                                v-if="recolte.note"
                                            >
                                                <div
                                                    class="text-subtitle-2 grey--text"
                                                >
                                                    Note
                                                </div>
                                                <div class="text-body-1">
                                                    {{ recolte.note }}
                                                </div>
                                            </v-col>
                                        </v-row>
                                    </v-card-text>
                                </v-card>
                            </v-col>

                            <!-- Collections Table -->
                            <v-col cols="12">
                                <v-card>
                                    <v-card-title class="success white--text">
                                        <v-icon left color="white"
                                            >mdi-package-variant-closed</v-icon
                                        >
                                        Collections ({{
                                            recolte.collections?.length || 0
                                        }})
                                    </v-card-title>
                                    <v-card-text class="pa-0">
                                        <div
                                            v-if="
                                                recolte.collections &&
                                                recolte.collections.length > 0
                                            "
                                        >
                                            <v-simple-table
                                                class="recolte-table"
                                            >
                                                <template v-slot:default>
                                                    <thead>
                                                        <tr>
                                                            <th
                                                                class="text-left tracking-col"
                                                            >
                                                                Tracking
                                                            </th>
                                                            <th
                                                                class="text-right amount-col"
                                                            >
                                                                Montant
                                                            </th>
                                                            <th
                                                                class="text-left phone-col"
                                                            >
                                                                Téléphone
                                                            </th>
                                                            <th
                                                                class="text-center by-col"
                                                            >
                                                                Recolté Par
                                                            </th>
                                                            <th
                                                                class="text-center date-col"
                                                            >
                                                                Recolté le
                                                            </th>
                                                            <th
                                                                class="text-center type-col"
                                                            >
                                                                Type
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr
                                                            v-for="(
                                                                collection,
                                                                index
                                                            ) in recolte.collections"
                                                            :key="collection.id"
                                                            :class="{
                                                                'zebra-row':
                                                                    index %
                                                                        2 ===
                                                                    1,
                                                            }"
                                                        >
                                                            <td
                                                                class="text-left nowrap"
                                                            >
                                                                {{
                                                                    collection
                                                                        .parcel
                                                                        ?.tracking_number ||
                                                                    "N/A"
                                                                }}
                                                            </td>
                                                            <td
                                                                class="text-right nowrap"
                                                            >
                                                                {{
                                                                    formatAmount(
                                                                        collection
                                                                            .parcel
                                                                            ?.cod_amount ||
                                                                            0,
                                                                    )
                                                                }}
                                                                Da
                                                            </td>
                                                            <td
                                                                class="text-left nowrap"
                                                            >
                                                                {{
                                                                    collection
                                                                        .parcel
                                                                        ?.recipient_phone ||
                                                                    "N/A"
                                                                }}
                                                            </td>
                                                            <td
                                                                class="text-center nowrap"
                                                            >
                                                                {{
                                                                    getCollectedBy(
                                                                        collection,
                                                                    )
                                                                }}
                                                            </td>
                                                            <td
                                                                class="text-center nowrap"
                                                            >
                                                                {{
                                                                    formatCollectionDate(
                                                                        collection.collected_at,
                                                                    )
                                                                }}
                                                            </td>
                                                            <td
                                                                class="text-center nowrap"
                                                            >
                                                                {{
                                                                    getParcelType(
                                                                        collection,
                                                                    )
                                                                }}
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </template>
                                            </v-simple-table>
                                        </div>
                                        <div v-else class="text-center py-8">
                                            <v-icon
                                                size="64"
                                                color="grey lighten-2"
                                                >mdi-package-variant-closed</v-icon
                                            >
                                            <p
                                                class="text-h6 text--secondary mt-2"
                                            >
                                                No collections
                                            </p>
                                            <p
                                                class="text-body-2 text--secondary"
                                            >
                                                This recolte has no collections
                                                associated with it.
                                            </p>
                                        </div>
                                    </v-card-text>
                                </v-card>
                            </v-col>

                            <!-- Expenses Table -->
                            <v-col cols="12" v-if="recolte.expenses && recolte.expenses.length > 0">
                                <v-card>
                                    <v-card-title class="error white--text">
                                        <v-icon left color="white">mdi-cash-minus</v-icon>
                                        Expenses ({{ recolte.expenses.length }})
                                    </v-card-title>
                                    <v-card-text class="pa-0">
                                        <v-simple-table class="recolte-table">
                                            <template v-slot:default>
                                                <thead>
                                                    <tr>
                                                        <th class="text-left" style="width: 70%;">Description</th>
                                                        <th class="text-right" style="width: 30%;">Montant</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="(expense, index) in recolte.expenses" :key="expense.id" :class="{'zebra-row': index % 2 === 1}">
                                                        <td class="text-left">{{ expense.title }} {{ expense.description ? ' - ' + expense.description : '' }}</td>
                                                        <td class="text-right">{{ formatAmount(expense.amount) }} Da</td>
                                                    </tr>
                                                </tbody>
                                            </template>
                                        </v-simple-table>
                                    </v-card-text>
                                </v-card>
                            </v-col>

                            <!-- Summary -->
                            <v-col cols="12" md="6" offset-md="6">
                                <v-card>
                                    <v-card-text>
                                        <v-row>
                                            <v-col cols="6" class="text-right font-weight-bold">Total Recolté:</v-col>
                                            <v-col cols="6" class="text-right">{{ formatAmount(totalAmount) }} Da</v-col>
                                        </v-row>
                                        <v-row v-if="totalExpenses > 0">
                                            <v-col cols="6" class="text-right font-weight-bold error--text">Total Dépenses:</v-col>
                                            <v-col cols="6" class="text-right error--text">- {{ formatAmount(totalExpenses) }} Da</v-col>
                                        </v-row>
                                        <v-divider class="my-2"></v-divider>
                                        <v-row>
                                            <v-col cols="6" class="text-right text-h6 font-weight-bold">Net à Verser:</v-col>
                                            <v-col cols="6" class="text-right text-h6 font-weight-bold success--text">{{ formatAmount(netTotal) }} Da</v-col>
                                        </v-row>
                                    </v-card-text>
                                </v-card>
                            </v-col>
                        </v-row>
                    </v-container>
                </v-main>
            </template>
        </AppLayout>
    </div>
</template>

<script>
import { Head } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";

export default {
    name: "RecolteShow",
    components: {
        Head,
        AppLayout,
    },
    props: {
        recolte: {
            type: Object,
            required: true,
        },
        errors: {
            type: Object,
            default: () => ({}),
        },
        totalExpenses: {
            type: Number,
            default: 0,
        },
        netTotal: {
            type: Number,
            default: 0,
        },
    },
    computed: {
        totalAmount() {
            if (!this.recolte.collections) return 0;

            const total = this.recolte.collections.reduce((sum, collection) => {
                return sum + parseFloat(collection.parcel?.cod_amount || 0);
            }, 0);

            return total;
        },
    },
    methods: {
        formatDate(date) {
            if (!date) return "N/A";
            return new Date(date).toLocaleDateString("en-US", {
                year: "numeric",
                month: "short",
                day: "numeric",
                hour: "2-digit",
                minute: "2-digit",
            });
        },
        formatCollectionDate(date) {
            if (!date) return "";
            const d = new Date(date);
            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, "0");
            const day = String(d.getDate()).padStart(2, "0");
            const hours = String(d.getHours()).padStart(2, "0");
            const minutes = String(d.getMinutes()).padStart(2, "0");
            return `${year}-${month}-${day} ${hours}:${minutes}`;
        },
        formatAmount(amount) {
            const num = Math.round(parseFloat(amount || 0));
            return num.toLocaleString("fr-FR");
        },
        getCollectedBy(collection) {
            if (!collection.created_by) return "N/A";
            return (
                collection.created_by.first_name ||
                collection.created_by.name ||
                "N/A"
            );
        },
        getParcelType(collection) {
            const rawType =
                collection.parcel_type || collection.parcel?.delivery_type;

            if (!rawType) {
                return collection.isHomeDelivery ? "home" : "stopdesk";
            }

            if (["home_delivery", "home", "homeDelivery"].includes(rawType)) {
                return "a domicile";
            } else if (["stopdesk", "stop_desk"].includes(rawType)) {
                return "stopdesk";
            }

            return rawType;
        },
        exportPdf(id) {
            window.location.href = `/recoltes/${id}/export?type=pdf`;
        },
        logout() {
            this.$inertia.post("/logout");
        },
    },
};
</script>

<style scoped>
.collection-card {
    border: 2px solid transparent;
    transition: all 0.2s ease;
}

.collection-card:hover {
    border-color: #1976d2;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.collection-card.selected {
    border-color: #1976d2;
    background-color: #e3f2fd;
}

/* Table styling to match blade template */
.recolte-table {
    border-collapse: collapse;
}

.recolte-table thead th {
    background-color: #f0f0f0 !important;
    font-weight: 700 !important;
    border: 1px solid #bfbfbf !important;
    padding: 6px 8px !important;
    font-size: 12px !important;
}

.recolte-table tbody td {
    border: 1px solid #bfbfbf !important;
    padding: 6px 8px !important;
    font-size: 12px !important;
    vertical-align: middle !important;
}

.recolte-table .zebra-row {
    background-color: #f7f7f7 !important;
}

.recolte-table .nowrap {
    white-space: nowrap;
}

/* Column widths matching blade template */
.recolte-table .tracking-col {
    width: 32%;
}

.recolte-table .amount-col {
    width: 14%;
}

.recolte-table .phone-col {
    width: 11%;
}

.recolte-table .by-col {
    width: 20%;
}

.recolte-table .date-col {
    width: 15%;
}

.recolte-table .type-col {
    width: 8%;
}
</style>
