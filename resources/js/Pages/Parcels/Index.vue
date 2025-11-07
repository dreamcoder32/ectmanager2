<template>
    <AppLayout>
        <Head title="Parcels" />

        <template #title>
            <div class="d-flex justify-space-between align-center">
                <span
                    class="text-h4 font-weight-bold"
                    style="
                        background: linear-gradient(
                            135deg,
                            #1976d2 0%,
                            #1565c0 100%
                        );
                        -webkit-background-clip: text;
                        -webkit-text-fill-color: transparent;
                        background-clip: text;
                    "
                >
                    {{ $t("parcels.title") }}
                </span>
                <v-btn
                    color="primary"
                    href="/parcels/create"
                    prepend-icon="mdi-plus"
                    style="font-weight: 600; border-radius: 12px"
                    elevation="2"
                >
                    <v-icon left>mdi-plus</v-icon>
                    {{ $t("parcels.create_new") }}
                </v-btn>
            </div>
        </template>

        <template #content>
            <v-container fluid>
                <!-- Filters Section - Outside Cards -->
                <div class="filters-section mb-6">
                    <v-row>
                        <v-col cols="12" md="3">
                            <v-text-field
                                v-model="filters.search"
                                :label="$t('parcels.search')"
                                prepend-inner-icon="mdi-magnify"
                                variant="outlined"
                                density="compact"
                                @input="debounceSearch"
                                clearable
                                color="primary"
                                class="filter-field"
                            ></v-text-field>
                        </v-col>

                        <v-col cols="12" md="2">
                            <v-select
                                v-model="filters.status"
                                :items="statusOptions"
                                :label="$t('parcels.status')"
                                item-title="text"
                                item-value="value"
                                variant="outlined"
                                density="compact"
                                clearable
                                @change="applyFilters"
                                color="primary"
                                class="filter-field"
                            ></v-select>
                        </v-col>

                        <v-col cols="12" md="2">
                            <v-select
                                v-model="filters.company_id"
                                :items="companyOptions"
                                :label="$t('parcels.company')"
                                item-title="text"
                                item-value="value"
                                variant="outlined"
                                density="compact"
                                clearable
                                @change="applyFilters"
                                color="primary"
                                class="filter-field"
                            ></v-select>
                        </v-col>

                        <v-col cols="12" md="2">
                            <v-select
                                v-model="filters.state_id"
                                :items="stateOptions"
                                :label="$t('parcels.state')"
                                item-title="text"
                                item-value="value"
                                variant="outlined"
                                density="compact"
                                clearable
                                @change="onStateChange"
                                color="primary"
                                class="filter-field"
                            ></v-select>
                        </v-col>

                        <v-col cols="12" md="2">
                            <v-select
                                v-model="filters.city_id"
                                :items="cityOptions"
                                :label="$t('parcels.city')"
                                item-title="text"
                                item-value="value"
                                variant="outlined"
                                density="compact"
                                clearable
                                :disabled="!filters.state_id"
                                @change="applyFilters"
                                color="primary"
                                class="filter-field"
                            ></v-select>
                        </v-col>

                        <v-col cols="12" md="1" class="d-flex align-center">
                            <v-btn
                                color="primary"
                                @click="clearFilters"
                                variant="outlined"
                                size="small"
                                class="filter-clear-btn"
                            >
                                <v-icon left>mdi-refresh</v-icon>
                                {{ $t("common.clear") }}
                            </v-btn>
                        </v-col>
                    </v-row>
                </div>

                <!-- Parcels List - Card Layout -->
                <v-card
                    elevation="2"
                    style="
                        border-radius: 16px;
                        background: white;
                        overflow: hidden;
                    "
                >
                    <v-card-title
                        class="pa-6"
                        style="
                            background: linear-gradient(
                                135deg,
                                #f8f9fa 0%,
                                #e9ecef 100%
                            );
                            border-bottom: 1px solid #dee2e6;
                        "
                    >
                        <div class="d-flex align-center">
                            <v-avatar
                                size="40"
                                class="mr-3"
                                style="
                                    background: linear-gradient(
                                        135deg,
                                        #1976d2 0%,
                                        #1565c0 100%
                                    );
                                "
                            >
                                <v-icon color="white" size="20"
                                    >mdi-package-variant</v-icon
                                >
                            </v-avatar>
                            <div>
                                <h2
                                    class="text-h5 font-weight-bold mb-1"
                                    style="color: #1a1d29"
                                >
                                    {{ $t("parcels.title") }}
                                </h2>
                                <p
                                    class="text-body-2 mb-0"
                                    style="color: #6b7280"
                                >
                                    {{ parcels.total }} total parcels
                                </p>
                            </div>
                        </div>
                        <v-spacer></v-spacer>
                        <v-chip
                            v-if="filters.status"
                            color="primary"
                            variant="outlined"
                            size="small"
                            class="mr-2"
                        >
                            {{
                                statusOptions.find(
                                    (s) => s.value === filters.status,
                                )?.text
                            }}
                        </v-chip>
                        <v-chip
                            v-if="filters.company_id"
                            color="secondary"
                            variant="outlined"
                            size="small"
                        >
                            {{
                                companyOptions.find(
                                    (c) => c.value === filters.company_id,
                                )?.text
                            }}
                        </v-chip>
                    </v-card-title>

                    <v-card-text class="pa-0">
                        <div v-if="loading" class="text-center pa-8">
                            <v-progress-circular
                                indeterminate
                                color="primary"
                                size="48"
                            ></v-progress-circular>
                            <p class="text-body-1 mt-4" style="color: #6b7280">
                                Loading parcels...
                            </p>
                        </div>

                        <div
                            v-else-if="parcels.data.length === 0"
                            class="text-center pa-8"
                        >
                            <v-icon size="64" color="grey-lighten-2"
                                >mdi-package-variant-closed</v-icon
                            >
                            <h3
                                class="text-h6 font-weight-medium mt-4 mb-2"
                                style="color: #374151"
                            >
                                No parcels found
                            </h3>
                            <p class="text-body-2" style="color: #6b7280">
                                Try adjusting your filters or create a new
                                parcel
                            </p>
                        </div>

                        <div v-else>
                            <!-- Parcel Cards -->
                            <div class="parcel-cards-container">
                                <v-card
                                    v-for="parcel in parcels.data"
                                    :key="parcel.id"
                                    class="parcel-card mb-4"
                                    elevation="1"
                                    style="
                                        border-radius: 12px;
                                        border: 1px solid #e5e7eb;
                                        transition: all 0.3s ease;
                                        cursor: pointer;
                                    "
                                    @click="
                                        openTrackingPage(
                                            parcel.tracking_number,
                                            parcel.id,
                                        )
                                    "
                                >
                                    <v-card-text class="pa-4">
                                        <v-row>
                                            <!-- Tracking Number & Status -->
                                            <v-col cols="12" md="3">
                                                <div
                                                    class="d-flex align-center mb-3"
                                                >
                                                    <v-avatar
                                                        size="32"
                                                        class="mr-3"
                                                        style="
                                                            background: linear-gradient(
                                                                135deg,
                                                                #667eea 0%,
                                                                #764ba2 100%
                                                            );
                                                        "
                                                    >
                                                        <v-icon
                                                            color="white"
                                                            size="16"
                                                            >mdi-barcode</v-icon
                                                        >
                                                    </v-avatar>
                                                    <div class="flex-grow-1">
                                                        <div
                                                            class="d-flex align-center"
                                                        >
                                                            <v-icon
                                                                v-if="
                                                                    lastClickedParcelId ===
                                                                    parcel.id
                                                                "
                                                                color="success"
                                                                size="20"
                                                                class="ml-2 last-clicked-icon"
                                                            >
                                                                mdi-check-circle
                                                            </v-icon>
                                                            <div
                                                                class="text-h6 font-weight-bold"
                                                                style="
                                                                    color: #1a1d29;
                                                                "
                                                            >
                                                                {{
                                                                    parcel.tracking_number
                                                                }}
                                                            </div>
                                                            <!-- Last clicked indicator -->
                                                        </div>
                                                        <div
                                                            class="d-flex flex-column"
                                                        >
                                                            <v-chip
                                                                :color="
                                                                    getStatusColor(
                                                                        parcel.status,
                                                                    )
                                                                "
                                                                size="small"
                                                                variant="flat"
                                                                class="mt-1 w-fit"
                                                            >
                                                                {{
                                                                    $t(
                                                                        `parcels.status_${parcel.status}`,
                                                                    )
                                                                }}
                                                            </v-chip>

                                                            <!-- Pending days count for urgency -->
                                                            <div
                                                                v-if="
                                                                    parcel.status ===
                                                                        'pending' &&
                                                                    getPendingDays(
                                                                        parcel,
                                                                    ) > 0
                                                                "
                                                                class="mt-1"
                                                            >
                                                                <v-chip
                                                                    :color="
                                                                        getUrgencyColor(
                                                                            parcel,
                                                                        )
                                                                    "
                                                                    size="x-small"
                                                                    variant="outlined"
                                                                    class="urgency-chip"
                                                                >
                                                                    <v-icon
                                                                        left
                                                                        size="12"
                                                                        >mdi-clock-alert</v-icon
                                                                    >
                                                                    {{
                                                                        getPendingDays(
                                                                            parcel,
                                                                        )
                                                                    }}
                                                                    {{
                                                                        getPendingDays(
                                                                            parcel,
                                                                        ) === 1
                                                                            ? "day"
                                                                            : "days"
                                                                    }}
                                                                    pending
                                                                </v-chip>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </v-col>

                                            <!-- Recipient Information -->
                                            <v-col cols="12" md="3">
                                                <div class="recipient-info">
                                                    <h4
                                                        class="text-subtitle-1 font-weight-bold mb-2"
                                                        style="color: #1a1d29"
                                                    >
                                                        <v-icon
                                                            size="18"
                                                            class="mr-1"
                                                            style="
                                                                color: #667eea;
                                                            "
                                                            >mdi-account</v-icon
                                                        >
                                                        {{
                                                            parcel.recipient_name
                                                        }}
                                                    </h4>
                                                    <div class="mb-2">
                                                        <v-btn
                                                            :href="`tel:${parcel.recipient_phone}`"
                                                            variant="text"
                                                            size="small"
                                                            color="primary"
                                                            class="pa-0 text-left justify-start px-2"
                                                            style="
                                                                text-transform: none;
                                                                min-width: auto;
                                                            "
                                                        >
                                                            <v-icon
                                                                size="16"
                                                                class="mr-1"
                                                                >mdi-phone</v-icon
                                                            >
                                                            {{
                                                                parcel.recipient_phone
                                                            }}
                                                        </v-btn>
                                                    </div>
                                                    <div
                                                        v-if="parcel.state"
                                                        class="mb-2"
                                                    >
                                                        <v-chip
                                                            color="info"
                                                            variant="outlined"
                                                            size="small"
                                                            class="mr-2"
                                                        >
                                                            <v-icon
                                                                left
                                                                size="14"
                                                                >mdi-map</v-icon
                                                            >
                                                            {{
                                                                parcel.state
                                                                    .name
                                                            }}
                                                        </v-chip>
                                                    </div>
                                                    <div
                                                        class="text-body-2"
                                                        style="color: #6b7280"
                                                    >
                                                        <v-icon
                                                            size="14"
                                                            class="mr-1"
                                                            >mdi-map-marker</v-icon
                                                        >
                                                        {{
                                                            parcel.recipient_address
                                                        }}
                                                    </div>
                                                </div>
                                            </v-col>

                                            <!-- COD Amount & Company -->
                                            <v-col cols="12" md="3">
                                                <div class="financial-info">
                                                    <div
                                                        v-if="parcel.cod_amount"
                                                        class="mb-3"
                                                    >
                                                        <div
                                                            class="text-caption"
                                                            style="
                                                                color: #6b7280;
                                                            "
                                                        >
                                                            COD Amount
                                                        </div>
                                                        <div
                                                            class="text-h6 font-weight-bold d-flex align-center"
                                                            style="
                                                                color: #059669;
                                                            "
                                                        >
                                                            {{
                                                                formatCurrency(
                                                                    parcel.cod_amount,
                                                                )
                                                            }}
                                                            <v-tooltip
                                                                v-if="
                                                                    parcel.price_modified
                                                                "
                                                                location="top"
                                                            >
                                                                <template
                                                                    v-slot:activator="{
                                                                        props,
                                                                    }"
                                                                >
                                                                    <v-icon
                                                                        v-bind="
                                                                            props
                                                                        "
                                                                        size="small"
                                                                        color="warning"
                                                                        class="ml-2"
                                                                    >
                                                                        mdi-pencil
                                                                    </v-icon>
                                                                </template>
                                                                <span
                                                                    >Price has
                                                                    been
                                                                    modified</span
                                                                >
                                                            </v-tooltip>
                                                        </div>
                                                    </div>

                                                    <!-- Description -->
                                                    <div
                                                        v-if="
                                                            parcel.description
                                                        "
                                                        class="mb-2"
                                                    >
                                                        <div
                                                            class="text-caption"
                                                            style="
                                                                color: #6b7280;
                                                            "
                                                        >
                                                            <v-icon
                                                                size="12"
                                                                class="mr-1"
                                                                >mdi-text</v-icon
                                                            >
                                                            Description
                                                        </div>
                                                        <div
                                                            class="text-body-2"
                                                            style="
                                                                color: #374151;
                                                                line-height: 1.4;
                                                            "
                                                        >
                                                            {{
                                                                parcel.description
                                                            }}
                                                        </div>
                                                    </div>

                                                    <!-- Notes -->
                                                    <div
                                                        v-if="parcel.notes"
                                                        class="mb-2"
                                                    >
                                                        <div
                                                            class="text-caption"
                                                            style="
                                                                color: #6b7280;
                                                            "
                                                        >
                                                            <v-icon
                                                                size="12"
                                                                class="mr-1"
                                                                >mdi-note-text</v-icon
                                                            >
                                                            Notes
                                                        </div>
                                                        <div
                                                            class="text-body-2"
                                                            style="
                                                                color: #374151;
                                                                line-height: 1.4;
                                                            "
                                                        >
                                                            {{ parcel.notes }}
                                                        </div>
                                                    </div>

                                                    <div
                                                        v-if="parcel.company"
                                                        class="mb-2"
                                                    >
                                                        <v-chip
                                                            color="primary"
                                                            variant="outlined"
                                                            size="small"
                                                        >
                                                            <v-icon
                                                                left
                                                                size="14"
                                                                >mdi-domain</v-icon
                                                            >
                                                            {{
                                                                parcel.company
                                                                    .name
                                                            }}
                                                        </v-chip>
                                                    </div>
                                                </div>
                                            </v-col>

                                            <!-- WhatsApp Messages Count -->
                                            <v-col
                                                cols="12"
                                                md="3"
                                                class="d-flex align-center justify-center"
                                            >
                                                <div
                                                    class="whatsapp-count-section text-center"
                                                >
                                                    <v-tooltip location="top">
                                                        <template
                                                            v-slot:activator="{
                                                                props,
                                                            }"
                                                        >
                                                            <v-btn
                                                                v-bind="props"
                                                                icon
                                                                variant="flat"
                                                                :color="
                                                                    parcel.messages_count >
                                                                    0
                                                                        ? 'success'
                                                                        : 'grey-lighten-2'
                                                                "
                                                                size="large"
                                                                @click.stop
                                                            >
                                                                <v-badge
                                                                    :content="
                                                                        parcel.messages_count ||
                                                                        0
                                                                    "
                                                                    :color="
                                                                        parcel.messages_count >
                                                                        0
                                                                            ? 'red'
                                                                            : 'grey'
                                                                    "
                                                                    overlap
                                                                >
                                                                    <v-icon
                                                                        size="28"
                                                                        >mdi-whatsapp</v-icon
                                                                    >
                                                                </v-badge>
                                                            </v-btn>
                                                        </template>
                                                        <span
                                                            >{{
                                                                parcel.messages_count ||
                                                                0
                                                            }}
                                                            WhatsApp
                                                            message(s)</span
                                                        >
                                                    </v-tooltip>
                                                    <div
                                                        class="text-caption mt-1"
                                                        style="
                                                            color: #6b7280;
                                                            font-weight: 500;
                                                        "
                                                    >
                                                        {{
                                                            parcel.messages_count ||
                                                            0
                                                        }}
                                                        {{
                                                            parcel.messages_count ===
                                                            1
                                                                ? "Message"
                                                                : "Messages"
                                                        }}
                                                    </div>
                                                </div>
                                            </v-col>

                                            <!-- Actions -->
                                            <v-col
                                                cols="12"
                                                md="1"
                                                class="d-flex align-center justify-center"
                                            >
                                                <v-tooltip location="top">
                                                    <template
                                                        v-slot:activator="{
                                                            props,
                                                        }"
                                                    >
                                                        <v-btn
                                                            v-bind="props"
                                                            icon
                                                            variant="flat"
                                                            color="primary"
                                                            size="small"
                                                            @click.stop="
                                                                openPriceChangeDialog(
                                                                    parcel,
                                                                )
                                                            "
                                                        >
                                                            <v-icon size="20"
                                                                >mdi-currency-usd</v-icon
                                                            >
                                                        </v-btn>
                                                    </template>
                                                    <span>Change Price</span>
                                                </v-tooltip>
                                            </v-col>

                                            <!-- Old Actions (commented) -->
                                            <!-- <v-col cols="12" md="2" class="d-flex align-center justify-end">
                        <div class="d-flex flex-column">
                          <v-btn
                            color="primary"
                            variant="outlined"
                            size="small"
                            @click.stop="$inertia.visit(`/parcels/${parcel.id}`)"
                            class="mb-2"
                          >
                            <v-icon left size="16">mdi-eye</v-icon>
                            View
                          </v-btn>
                          <v-btn
                            color="secondary"
                            variant="outlined"
                            size="small"
                            @click.stop="$inertia.visit(`/parcels/${parcel.id}/edit`)"
                          >
                            <v-icon left size="16">mdi-pencil</v-icon>
                            Edit
                          </v-btn>
                        </div>
                      </v-col> -->
                                        </v-row>
                                    </v-card-text>
                                </v-card>
                            </div>

                            <!-- Pagination -->
                            <div
                                class="pa-4"
                                style="
                                    border-top: 1px solid #e5e7eb;
                                    background: #f8f9fa;
                                "
                            >
                                <v-pagination
                                    v-model="currentPage"
                                    :length="totalPages"
                                    :total-visible="7"
                                    @update:model-value="onPageChange"
                                    color="primary"
                                    class="mt-2"
                                ></v-pagination>
                            </div>
                        </div>
                    </v-card-text>
                </v-card>

                <!-- Price Change Dialog -->
                <v-dialog v-model="showPriceChangeDialog" max-width="600px">
                    <v-card>
                        <v-card-title>
                            <span class="text-h5">Change Parcel Price</span>
                        </v-card-title>
                        <v-card-text>
                            <div v-if="selectedParcel">
                                <v-row>
                                    <v-col cols="12">
                                        <v-text-field
                                            label="Tracking Number"
                                            :value="
                                                selectedParcel.tracking_number
                                            "
                                            readonly
                                            variant="outlined"
                                        ></v-text-field>
                                    </v-col>
                                    <v-col cols="12">
                                        <v-text-field
                                            label="Current Price (DZD)"
                                            :value="selectedParcel.cod_amount"
                                            readonly
                                            variant="outlined"
                                        ></v-text-field>
                                    </v-col>
                                    <v-col cols="12">
                                        <v-text-field
                                            v-model.number="newPrice"
                                            label="New Price (DZD)"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            placeholder="Enter new price..."
                                            required
                                            variant="outlined"
                                        ></v-text-field>
                                    </v-col>
                                    <v-col cols="12">
                                        <v-textarea
                                            v-model="priceChangeReason"
                                            label="Reason (Optional)"
                                            rows="3"
                                            placeholder="Enter reason for price change..."
                                            variant="outlined"
                                        ></v-textarea>
                                    </v-col>
                                    <v-col cols="12">
                                        <v-btn
                                            variant="text"
                                            color="info"
                                            @click="viewPriceHistory"
                                            prepend-icon="mdi-history"
                                            size="small"
                                        >
                                            View Price History
                                        </v-btn>
                                    </v-col>
                                </v-row>
                            </div>
                        </v-card-text>
                        <v-card-actions>
                            <v-spacer></v-spacer>
                            <v-btn
                                color="grey"
                                variant="text"
                                @click="closePriceChangeDialog"
                            >
                                Cancel
                            </v-btn>
                            <v-btn
                                color="primary"
                                @click="updatePrice"
                                :loading="updatingPrice"
                            >
                                Update Price
                            </v-btn>
                        </v-card-actions>
                    </v-card>
                </v-dialog>

                <!-- Price History Dialog -->
                <v-dialog v-model="showPriceHistoryDialog" max-width="700px">
                    <v-card>
                        <v-card-title>
                            <span class="text-h5">Price Change History</span>
                        </v-card-title>
                        <v-card-text>
                            <div v-if="selectedParcel">
                                <v-alert
                                    type="info"
                                    variant="tonal"
                                    class="mb-4"
                                >
                                    Tracking Number:
                                    {{ selectedParcel.tracking_number }}
                                </v-alert>
                                <div
                                    v-if="loadingPriceHistory"
                                    class="text-center py-4"
                                >
                                    <v-progress-circular
                                        indeterminate
                                        color="primary"
                                    ></v-progress-circular>
                                </div>
                                <div v-else-if="priceHistory.length === 0">
                                    <v-alert type="info" variant="tonal">
                                        No price changes recorded for this
                                        parcel.
                                    </v-alert>
                                </div>
                                <v-timeline v-else side="end" align="start">
                                    <v-timeline-item
                                        v-for="change in priceHistory"
                                        :key="change.id"
                                        dot-color="primary"
                                        size="small"
                                    >
                                        <v-card>
                                            <v-card-text>
                                                <div
                                                    class="d-flex justify-space-between mb-2"
                                                >
                                                    <span
                                                        class="text-subtitle-2"
                                                    >
                                                        {{ change.changed_by }}
                                                    </span>
                                                    <span
                                                        class="text-caption text-medium-emphasis"
                                                    >
                                                        {{ change.changed_at }}
                                                    </span>
                                                </div>
                                                <div
                                                    class="d-flex align-center mb-2"
                                                >
                                                    <v-chip
                                                        size="small"
                                                        color="error"
                                                        class="mr-2"
                                                    >
                                                        {{ change.old_price }}
                                                        DZD
                                                    </v-chip>
                                                    <v-icon size="small"
                                                        >mdi-arrow-right</v-icon
                                                    >
                                                    <v-chip
                                                        size="small"
                                                        color="success"
                                                        class="ml-2"
                                                    >
                                                        {{ change.new_price }}
                                                        DZD
                                                    </v-chip>
                                                </div>
                                                <div
                                                    v-if="change.reason"
                                                    class="text-caption"
                                                >
                                                    <strong>Reason:</strong>
                                                    {{ change.reason }}
                                                </div>
                                            </v-card-text>
                                        </v-card>
                                    </v-timeline-item>
                                </v-timeline>
                            </div>
                        </v-card-text>
                        <v-card-actions>
                            <v-spacer></v-spacer>
                            <v-btn
                                color="grey"
                                variant="text"
                                @click="showPriceHistoryDialog = false"
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
import { router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";

export default {
    name: "ParcelIndex",
    components: {
        AppLayout,
    },
    props: {
        parcels: Object,
        states: Array,
        cities: Array,
        companies: Array,
        filters: Object,
    },
    data() {
        return {
            loading: false,
            searchTimeout: null,
            currentPage: this.parcels.current_page,
            lastClickedParcelId: null,
            showPriceChangeDialog: false,
            showPriceHistoryDialog: false,
            selectedParcel: null,
            newPrice: 0,
            priceChangeReason: "",
            updatingPrice: false,
            loadingPriceHistory: false,
            priceHistory: [],
            snackbar: {
                show: false,
                message: "",
                color: "success",
                timeout: 3000,
            },
        };
    },
    computed: {
        statusOptions() {
            return [
                { text: this.$t("parcels.status_pending"), value: "pending" },
                {
                    text: this.$t("parcels.status_picked_up"),
                    value: "picked_up",
                },
                {
                    text: this.$t("parcels.status_in_transit"),
                    value: "in_transit",
                },
                {
                    text: this.$t("parcels.status_out_for_delivery"),
                    value: "out_for_delivery",
                },
                {
                    text: this.$t("parcels.status_delivered"),
                    value: "delivered",
                },
                { text: this.$t("parcels.status_returned"), value: "returned" },
                {
                    text: this.$t("parcels.status_cancelled"),
                    value: "cancelled",
                },
            ];
        },
        stateOptions() {
            return (
                this.states?.map((state) => ({
                    text: state.name,
                    value: state.id,
                })) || []
            );
        },
        companyOptions() {
            return (
                this.companies?.map((company) => ({
                    text: company.name,
                    value: company.id,
                })) || []
            );
        },
        cityOptions() {
            if (!this.filters.state_id) return [];
            return (
                this.cities
                    ?.filter((city) => city.state_id === this.filters.state_id)
                    .map((city) => ({
                        text: city.name,
                        value: city.id,
                    })) || []
            );
        },
        totalPages() {
            return this.parcels?.last_page || 1;
        },
    },
    methods: {
        getStatusColor(status) {
            const colors = {
                pending: "orange",
                picked_up: "blue",
                in_transit: "purple",
                out_for_delivery: "indigo",
                delivered: "green",
                returned: "red",
                cancelled: "grey",
            };
            return colors[status] || "grey";
        },
        getPendingDays(parcel) {
            if (parcel.status !== "pending") return 0;

            // Use parcel_creation_date if available, otherwise use created_at
            const startDate = parcel.parcel_creation_date || parcel.created_at;
            if (!startDate) return 0;

            const start = new Date(startDate);
            const now = new Date();
            const diffTime = Math.abs(now - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

            return diffDays;
        },
        getUrgencyColor(parcel) {
            const days = this.getPendingDays(parcel);

            if (days >= 7) return "red"; // Critical - 7+ days
            if (days >= 3) return "orange"; // High urgency - 3-6 days
            if (days >= 1) return "yellow"; // Medium urgency - 1-2 days
            return "grey"; // Low urgency - same day
        },
        formatCurrency(amount) {
            return new Intl.NumberFormat("ar", {
                style: "currency",
                currency: "DZD",
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            }).format(amount);
        },
        debounceSearch() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.applyFilters();
            }, 500);
        },
        applyFilters() {
            this.currentPage = 1;
            router.get(
                "/parcels",
                {
                    ...this.filters,
                    page: 1,
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    onStart: () => {
                        this.loading = true;
                    },
                    onFinish: () => {
                        this.loading = false;
                    },
                },
            );
        },
        clearFilters() {
            this.filters = {
                search: "",
                status: null,
                state_id: null,
                city_id: null,
                company_id: null,
            };
            this.currentPage = 1;
            this.applyFilters();
        },
        onStateChange() {
            this.filters.city_id = null;
            this.applyFilters();
        },
        onPageChange(page) {
            this.currentPage = page;
            router.get(
                "/parcels",
                {
                    ...this.filters,
                    page: page,
                },
                {
                    preserveState: true,
                    preserveScroll: false,
                    onStart: () => {
                        this.loading = true;
                    },
                    onFinish: () => {
                        this.loading = false;
                    },
                },
            );
        },
        openTrackingPage(trackingNumber, parcelId) {
            // Store the clicked parcel ID for visual reference
            this.lastClickedParcelId = parcelId;

            const trackingUrl = `https://suivi.ecotrack.dz/suivi/${trackingNumber}`;
            window.open(trackingUrl, "_blank");
        },
        openPriceChangeDialog(parcel) {
            this.selectedParcel = parcel;
            this.newPrice = parcel.cod_amount;
            this.priceChangeReason = "";
            this.showPriceChangeDialog = true;
        },
        closePriceChangeDialog() {
            this.showPriceChangeDialog = false;
            this.selectedParcel = null;
            this.newPrice = 0;
            this.priceChangeReason = "";
        },
        async updatePrice() {
            if (!this.selectedParcel) return;
            if (!this.newPrice || this.newPrice < 0) {
                this.showSnackbar("Please enter a valid price", "error");
                return;
            }

            this.updatingPrice = true;
            try {
                const response = await fetch(
                    `/whatsapp/parcels/${this.selectedParcel.id}/update-price`,
                    {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document
                                .querySelector('meta[name="csrf-token"]')
                                .getAttribute("content"),
                        },
                        body: JSON.stringify({
                            new_price: this.newPrice,
                            reason: this.priceChangeReason,
                        }),
                    },
                );

                const result = await response.json();

                if (result.success) {
                    this.showSnackbar("Price updated successfully!", "success");
                    this.closePriceChangeDialog();
                    // Reload parcels to show updated price
                    router.reload();
                } else {
                    this.showSnackbar(
                        result.error || "Failed to update price",
                        "error",
                    );
                }
            } catch (error) {
                this.showSnackbar("Error updating price", "error");
            } finally {
                this.updatingPrice = false;
            }
        },
        async fetchPriceHistory() {
            if (!this.selectedParcel) return;

            this.loadingPriceHistory = true;
            try {
                const response = await fetch(
                    `/whatsapp/parcels/${this.selectedParcel.id}/price-history`,
                );
                const result = await response.json();

                if (result.success) {
                    this.priceHistory = result.data;
                } else {
                    this.showSnackbar("Failed to load price history", "error");
                }
            } catch (error) {
                this.showSnackbar("Error loading price history", "error");
            } finally {
                this.loadingPriceHistory = false;
            }
        },
        async viewPriceHistory() {
            this.showPriceHistoryDialog = true;
            await this.fetchPriceHistory();
        },
        showSnackbar(message, color = "success") {
            this.snackbar.message = message;
            this.snackbar.color = color;
            this.snackbar.show = true;
        },
    },
};
</script>

<style scoped>
/* Filters Section */
.filters-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 24px;
    border-radius: 16px;
    border: 1px solid #dee2e6;
}

.filter-field {
    background: white;
    border-radius: 12px;
}

.filter-clear-btn {
    border-radius: 8px;
    text-transform: none;
    font-weight: 500;
}

/* Parcel Cards */
.parcel-cards-container {
    padding: 24px;
}

.parcel-card {
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.parcel-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1) !important;
    border-color: #6b7280 !important;
}

.parcel-card:active {
    transform: translateY(0);
}

/* Recipient Info Styling */
.recipient-info h4 {
    line-height: 1.4;
}

.recipient-info .v-btn {
    text-transform: none;
    font-weight: 500;
    letter-spacing: normal;
}

.recipient-info .v-btn:hover {
    background-color: rgba(25, 118, 210, 0.08);
}

/* Financial Info Styling */
.financial-info .text-h6 {
    line-height: 1.2;
}

/* Status Chip Styling */
.v-chip {
    font-weight: 500;
    letter-spacing: 0.5px;
}

/* Urgency Chip Styling */
.urgency-chip {
    font-weight: 600 !important;
    letter-spacing: 0.3px !important;
    animation: pulse 2s infinite;
}

/* Last Clicked Icon Styling */
.last-clicked-icon {
    animation: checkBounce 0.6s ease-in-out;
    filter: drop-shadow(0 2px 4px rgba(76, 175, 80, 0.3));
}

@keyframes checkBounce {
    0% {
        transform: scale(0);
        opacity: 0;
    }
    50% {
        transform: scale(1.2);
        opacity: 1;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

.urgency-chip.v-chip--color-red {
    background-color: #ffebee !important;
    color: #d32f2f !important;
    border-color: #f44336 !important;
    font-weight: 700 !important;
}

.urgency-chip.v-chip--color-orange {
    background-color: #fff3e0 !important;
    color: #e65100 !important;
    border-color: #ff9800 !important;
    font-weight: 700 !important;
}

.urgency-chip.v-chip--color-yellow {
    background-color: #f57f17 !important;
    color: #ffffff !important;
    border-color: #f57f17 !important;
    font-weight: 700 !important;
}

@keyframes pulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
    100% {
        transform: scale(1);
    }
}

/* Action Buttons */
.v-btn {
    text-transform: none;
    font-weight: 500;
    letter-spacing: normal;
    border-radius: 8px;
}

.v-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Pagination Styling */
.v-pagination {
    justify-content: center;
}

.v-pagination .v-pagination__item {
    border-radius: 8px;
    font-weight: 500;
}

.v-pagination .v-pagination__item--is-active {
    background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
    color: white;
}

/* Loading State */
.v-progress-circular {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}

/* Empty State */
.v-icon {
    transition: all 0.3s ease;
}

/* Responsive Design */
@media (max-width: 768px) {
    .filters-section {
        padding: 16px;
    }

    .parcel-cards-container {
        padding: 16px;
    }

    .parcel-card .v-card-text {
        padding: 16px !important;
    }

    .recipient-info h4 {
        font-size: 1rem;
    }

    .financial-info .text-h6 {
        font-size: 1.1rem;
    }
}

/* Card Header Styling */
.v-card-title {
    position: relative;
    overflow: hidden;
}

.v-card-title::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(
        135deg,
        rgba(255, 255, 255, 0.1) 0%,
        rgba(255, 255, 255, 0.05) 100%
    );
    pointer-events: none;
}

/* Filter Chips in Header */
.v-chip {
    transition: all 0.2s ease;
}

.v-chip:hover {
    transform: scale(1.05);
}

/* Clean Input Field Styling - Complete Vuetify Override */
.filter-field :deep(.v-input__control) {
    border-radius: 8px !important;
    background: white !important;
    border: 1px solid #d1d5db !important;
    transition: all 0.2s ease !important;
    box-shadow: none !important;
}

.filter-field :deep(.v-field__outline) {
    display: none !important;
}

.filter-field :deep(.v-field__outline__start),
.filter-field :deep(.v-field__outline__end),
.filter-field :deep(.v-field__outline__notch) {
    display: none !important;
}

.filter-field :deep(.v-input--is-focused .v-input__control) {
    border-color: #6b7280 !important;
    box-shadow: 0 0 0 3px rgba(107, 114, 128, 0.1) !important;
}

.filter-field :deep(.v-input__control:hover) {
    border-color: #9ca3af !important;
}

/* Dropdown Menu Styling */
.v-menu__content {
    border-radius: 12px !important;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
    border: 1px solid #e5e7eb !important;
    max-height: 300px !important;
    overflow-y: auto !important;
}

.v-list {
    padding: 8px 0 !important;
}

.v-list-item {
    transition: all 0.2s ease !important;
    padding: 12px 16px !important;
    min-height: 44px !important;
    border-radius: 8px !important;
    margin: 2px 8px !important;
}

.v-list-item:hover {
    background-color: #f8f9fa !important;
    transform: translateX(4px);
}

.v-list-item--active {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%) !important;
    color: #1976d2 !important;
    font-weight: 500;
}

.v-list-item--active:hover {
    background: linear-gradient(135deg, #bbdefb 0%, #90caf9 100%) !important;
}

/* WhatsApp Count Section */
.whatsapp-count-section {
    padding: 8px;
    border-radius: 12px;
    transition: all 0.3s ease;
}

.whatsapp-count-section:hover {
    background-color: rgba(37, 211, 102, 0.05);
}

.whatsapp-count-section .v-btn {
    transition: all 0.3s ease;
}

.whatsapp-count-section .v-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3);
}
</style>
