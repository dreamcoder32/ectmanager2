<template>
  <AppLayout>
    <Head :title="$t('drivers.title')" />

    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          {{ $t('drivers.title') }}
        </h2>
        <v-btn
          @click="goCreate"
          color="primary"
          prepend-icon="mdi-account-plus"
        >
          {{ $t('drivers.create') }}
        </v-btn>
      </div>
    </template>

    <v-container fluid class="py-8">
      <v-row justify="center">
        <v-col cols="12" md="11" lg="10">
          <v-card elevation="2" style="border-radius: 12px;">
            <v-card-title class="text-h6 pa-6 d-flex align-center justify-between">
              <div class="d-flex align-center">
                <v-icon class="mr-2">mdi-motorbike</v-icon>
                {{ $t('drivers.list') }}
              </div>
              <v-btn
                @click="goCreate"
                color="primary"
                prepend-icon="mdi-account-plus"
              >
                {{ $t('drivers.new') }}
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-data-table
                :headers="headers"
                :items="drivers"
                item-key="id"
                :items-per-page="10"
                class="elevation-1"
              >
                <template v-slot:[`item.is_active`]="{ item }">
                  <v-chip :color="item.is_active ? 'success' : 'grey'" size="small" variant="flat">
                    {{ item.is_active ? $t('drivers.status_active') : $t('drivers.status_inactive') }}
                  </v-chip>
                </template>

                <template v-slot:[`item.state`]="{ item }">
                  <v-chip v-if="item.state" size="small" color="primary" variant="outlined">
                    {{ item.state.name }}
                  </v-chip>
                  <span v-else class="text-grey">—</span>
                </template>

                <!-- <template v-slot:[`item.commission`]="{ item }">
                  <div>
                    <span v-if="item.commission_is_active">
                      {{ item.commission_type === 'percentage' ? `${item.commission_rate}%` : `${item.commission_rate} / parcel` }}
                    </span>
                    <span v-else class="text-grey">{{ $t('drivers.commission_disabled') }}</span>
                  </div>
                </template> -->

                <template v-slot:[`item.actions`]="{ item }">
                  <v-btn
                    @click="goEdit(item.id)"
                    color="primary"
                    size="small"
                    prepend-icon="mdi-pencil"
                    variant="tonal"
                  >
                    {{ $t('drivers.edit') }}
                  </v-btn>
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AppLayout from '@/Layouts/AppLayout.vue'

const { t } = useI18n()

const props = defineProps({
  drivers: {
    type: Array,
    default: () => []
  }
})

const headers = computed(() => [
  { title: t('drivers.headers.name'), key: 'name' },
  { title: t('drivers.headers.phone'), key: 'phone' },
  { title: t('drivers.headers.license'), key: 'license_number' },
  { title: t('drivers.headers.vehicle'), key: 'vehicle_info' },
  { title: t('drivers.headers.state'), key: 'state', sortable: false },
  { title: t('drivers.headers.commission'), key: 'commission', sortable: false },
  { title: t('drivers.headers.status'), key: 'is_active' },
  { title: t('drivers.headers.actions'), key: 'actions', sortable: false },
])

const goCreate = () => router.visit(route('drivers.create'))
const goEdit = (id) => router.visit(route('drivers.edit', id))
</script>