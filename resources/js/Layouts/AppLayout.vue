<template>
  <v-app>
    <!-- Navigation Drawer -->
    <v-navigation-drawer
      v-model="drawer"
      app
      width="280"
      class="sidebar-modern"
      style="background: #1a1d29;
             border-right: 1px solid rgba(255,255,255,0.08);"
    >
      <!-- Header Section -->
      <div class="pa-5 text-center position-relative" 
           style="background: linear-gradient(135deg, #2c3142 0%, #1a1d29 100%); 
                  border-bottom: 1px solid rgba(255,255,255,0.08);">
        
        <div class="d-flex align-center justify-start mb-3 px-2">
          <v-avatar size="48" 
                    class="mr-3"
                    style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                           box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);">
            <v-icon size="28" color="white">mdi-truck-delivery</v-icon>
          </v-avatar>
          <div class="text-left">
            <h2 class="text-h6 font-weight-bold text-white mb-0">ECTManager</h2>
            <p class="text-caption text-grey-lighten-1 mb-0">Delivery System</p>
          </div>
        </div>
      </div>

      <!-- Navigation Menu -->
      <v-list class="pa-3" nav>
        <v-list-item 
          @click="$inertia.visit('/dashboard')" 
          link
          :class="{ 'sidebar-item-active': $page.component === 'Dashboard/Index' }"
          class="sidebar-item mb-1"
        >
          <template v-slot:prepend>
            <v-icon :color="$page.component === 'Dashboard/Index' ? '#667eea' : '#8b92a8'" size="22">mdi-view-dashboard</v-icon>
          </template>
          <v-list-item-title :class="$page.component === 'Dashboard/Index' ? 'text-white' : 'text-grey-lighten-1'" class="font-weight-medium text-body-2">
            {{ $t('navigation.dashboard') }}
          </v-list-item-title>
        </v-list-item>

        <v-list-item 
          @click="$inertia.visit('/parcels')" 
          link
          :class="{ 'sidebar-item-active': $page.component.startsWith('Parcels/') }"
          class="sidebar-item mb-1"
        >
          <template v-slot:prepend>
            <v-icon :color="$page.component.startsWith('Parcels/') ? '#667eea' : '#8b92a8'" size="22">mdi-package-variant</v-icon>
          </template>
          <v-list-item-title :class="$page.component.startsWith('Parcels/') ? 'text-white' : 'text-grey-lighten-1'" class="font-weight-medium text-body-2">
            {{ $t('navigation.parcels') }}
          </v-list-item-title>
        </v-list-item>

        <v-list-item 
          @click="$inertia.visit('/stopdesk-payment')" 
          link
          :class="{ 'sidebar-item-active': $page.component.startsWith('StopDeskPayment/') }"
          class="sidebar-item mb-1"
        >
          <template v-slot:prepend>
            <v-icon :color="$page.component.startsWith('StopDeskPayment/') ? '#667eea' : '#8b92a8'" size="22">mdi-cash-register</v-icon>
          </template>
          <v-list-item-title :class="$page.component.startsWith('StopDeskPayment/') ? 'text-white' : 'text-grey-lighten-1'" class="font-weight-medium text-body-2">
            {{ $t('stopdesk_payment.title') }}
          </v-list-item-title>
        </v-list-item>

        <v-list-item 
        v-if="$page.props.auth.user.role === 'admin' || $page.props.auth.user.role === 'supervisor'"

          @click="$inertia.visit('/recoltes')" 
          link
          :class="{ 'sidebar-item-active': $page.component.startsWith('Recoltes/') }"
          class="sidebar-item mb-1"
        >
          <template v-slot:prepend>
            <v-icon :color="$page.component.startsWith('ecoltes/') ? '#667eea' : '#8b92a8'" size="22">mdi-cash-multiple</v-icon>
          </template>
          <v-list-item-title :class="$page.component.startsWith('Recoltes/') ? 'text-white' : 'text-grey-lighten-1'" class="font-weight-medium text-body-2">
            {{ $t('navigation.recoltes') || 'Collection Transfer' }}
          </v-list-item-title>
        </v-list-item>

        <v-list-item 
          @click="$inertia.visit('/driver-settlement')" 
          link
          :class="{ 'sidebar-item-active': $page.component.startsWith('DriverSettlement/') }"
          class="sidebar-item mb-1"
        >
          <template v-slot:prepend>
            <v-icon :color="$page.component.startsWith('DriverSettlement/') ? '#667eea' : '#8b92a8'" size="22">mdi-hand-coin</v-icon>
          </template>
          <v-list-item-title :class="$page.component.startsWith('DriverSettlement/') ? 'text-white' : 'text-grey-lighten-1'" class="font-weight-medium text-body-2">
            {{ $t('navigation.driverSettlement') }}
          </v-list-item-title>
        </v-list-item>

        <v-list-item 
          @click="$inertia.visit('/expenses')" 
          link
          :class="{ 'sidebar-item-active': $page.component.startsWith('Expense/') }"
          class="sidebar-item mb-1"
        >
          <template v-slot:prepend>
            <v-icon :color="$page.component.startsWith('Expense/') ? '#667eea' : '#8b92a8'" size="22">mdi-receipt</v-icon>
          </template>
          <v-list-item-title :class="$page.component.startsWith('Expense/') ? 'text-white' : 'text-grey-lighten-1'" class="font-weight-medium text-body-2">
            {{ t('navigation.expenses') }}
          </v-list-item-title>
        </v-list-item>

        <v-list-item 
          v-if="$page.props.auth.user.role === 'admin'"
          @click="$inertia.visit('/expense-categories')" 
          link
          :class="{ 'sidebar-item-active': $page.component.startsWith('ExpenseCategory/') }"
          class="sidebar-item mb-1"
        >
          <template v-slot:prepend>
            <v-icon :color="$page.component.startsWith('ExpenseCategory/') ? '#667eea' : '#8b92a8'" size="22">mdi-tag-multiple</v-icon>
          </template>
          <v-list-item-title :class="$page.component.startsWith('ExpenseCategory/') ? 'text-white' : 'text-grey-lighten-1'" class="font-weight-medium text-body-2">
            Depenses Categories
          </v-list-item-title>
        </v-list-item>

        <v-list-item 
          v-if="$page.props.auth.user.role === 'admin' || $page.props.auth.user.role === 'supervisor'"
          @click="$inertia.visit('/users')" 
          link
          :class="{ 'sidebar-item-active': $page.component.startsWith('Users/') }"
          class="sidebar-item mb-1"
        >
          <template v-slot:prepend>
            <v-icon :color="$page.component.startsWith('Users/') ? '#667eea' : '#8b92a8'" size="22">mdi-account-group</v-icon>
          </template>
          <v-list-item-title :class="$page.component.startsWith('Users/') ? 'text-white' : 'text-grey-lighten-1'" class="font-weight-medium text-body-2">
            {{ $t('navigation.users') || 'User Management' }}
          </v-list-item-title>
        </v-list-item>

        <v-list-item 
          v-if="$page.props.auth.user.role === 'admin' || $page.props.auth.user.role === 'supervisor'"
          @click="$inertia.visit('/drivers')" 
          link
          :class="{ 'sidebar-item-active': $page.component.startsWith('Drivers/') }"
          class="sidebar-item mb-1"
        >
          <template v-slot:prepend>
            <v-icon :color="$page.component.startsWith('Drivers/') ? '#667eea' : '#8b92a8'" size="22">mdi-motorbike</v-icon>
          </template>
          <v-list-item-title :class="$page.component.startsWith('Drivers/') ? 'text-white' : 'text-grey-lighten-1'" class="font-weight-medium text-body-2">
            {{ $t('navigation.drivers') }}
          </v-list-item-title>
        </v-list-item>

        <v-list-item 
          @click="$inertia.visit('/whatsapp')" 
          link
          :class="{ 'sidebar-item-active': $page.component.startsWith('WhatsApp/') }"
          class="sidebar-item mb-1"
        >
          <template v-slot:prepend>
            <v-icon :color="$page.component.startsWith('WhatsApp/') ? '#25D366' : '#8b92a8'" size="22">mdi-whatsapp</v-icon>
          </template>
          <v-list-item-title :class="$page.component.startsWith('WhatsApp/') ? 'text-white' : 'text-grey-lighten-1'" class="font-weight-medium text-body-2">
            {{ $t('navigation.whatsapp') }}
          </v-list-item-title>
        </v-list-item>

        <v-list-item 
          v-if="$page.props.auth.user.role === 'admin'"
          @click="$inertia.visit('/companies')" 
          link
          :class="{ 'sidebar-item-active': $page.component.startsWith('Companies/') }"
          class="sidebar-item mb-1"
        >
          <template v-slot:prepend>
            <v-icon :color="$page.component.startsWith('Companies/') ? '#667eea' : '#8b92a8'" size="22">mdi-domain</v-icon>
          </template>
          <v-list-item-title :class="$page.component.startsWith('Companies/') ? 'text-white' : 'text-grey-lighten-1'" class="font-weight-medium text-body-2">
            {{ $t('navigation.companies') }}
          </v-list-item-title>
        </v-list-item>
      </v-list>

      <!-- Footer Section -->
      <template v-slot:append>
        <div class="pa-4 text-center" 
             style="background: rgba(255,255,255,0.03); 
                    border-top: 1px solid rgba(255,255,255,0.08);">
          <p class="text-caption text-grey mb-1">
            Version 2.0.1
          </p>
          <p class="text-caption text-grey-darken-1 mb-0">
            © 2025 ECTManager
          </p>
        </div>
      </template>
    </v-navigation-drawer>

    <!-- App Bar -->
    <v-app-bar app 
               elevation="0"
               style="background: rgba(255, 255, 255, 0.5);
                      border-bottom: 1px solid rgba(229, 231, 235, 0.5);
                      backdrop-filter: blur(10px);">
      
      <v-app-bar-nav-icon 
        @click="drawer = !drawer"
        style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
               color: white;
               border-radius: 10px;"
      ></v-app-bar-nav-icon>
      
      <v-toolbar-title class="ml-4">
        <span class="text-h6 font-weight-bold" 
              style="color: #1a1d29;">
          <slot name="title">{{ title || 'Delivery Management System' }}</slot>
        </span>
      </v-toolbar-title>
      
      <v-spacer></v-spacer>
      
      <!-- Global Parcel Search -->
      <div class="d-flex align-center mr-4" style="max-width: 300px; width: 100%;">
        <v-text-field
          v-model="parcelSearchQuery"
          :label="$t('parcels.search_global')"
          prepend-inner-icon="mdi-magnify"
          variant="outlined"
          density="compact"
          hide-details
          clearable
          @keyup.enter="searchParcel"
          @click:clear="clearParcelSearch"
          style="background: white; border-radius: 12px;"
          class="parcel-search-field"
        >
          <template v-slot:append>
            <v-btn
              @click="searchParcel"
              icon
              size="small"
              color="primary"
              variant="text"
              :disabled="!parcelSearchQuery"
            >
              <v-icon size="18">mdi-magnify</v-icon>
            </v-btn>
          </template>
        </v-text-field>
      </div>
      
      <!-- Action Buttons Slot -->
      <slot name="actions"></slot>

      <!-- Language Switcher -->
      <LanguageSwitcher class="mr-2" />

      <!-- User Menu -->
      <v-menu>
        <template v-slot:activator="{ props }">
          <v-chip v-bind="props" 
                  class="user-chip" 
                  variant="flat" 
                  style="background: #f3f4f6; border-radius: 12px; padding: 8px 12px;">
            <v-avatar size="32" class="mr-2" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
              <v-icon color="white" size="18">mdi-account</v-icon>
            </v-avatar>
            <span v-if="$page?.props?.auth?.user" 
                  class="text-body-2 font-weight-medium d-none d-sm-inline"
                  style="color: #1a1d29; max-width: 160px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
              {{ $page.props.auth.user.first_name || $page.props.auth.user.first_name || $page.props.auth.user.email }}
            </span>
          </v-chip>
        </template>
        <v-list style="border-radius: 12px; 
                       box-shadow: 0 8px 32px rgba(0,0,0,0.1);
                       border: 1px solid #e5e7eb;">
          <v-list-item @click="$inertia.visit('/profile')" 
                       style="border-radius: 8px; margin: 4px;">
            <template v-slot:prepend>
              <v-icon color="#667eea">mdi-account</v-icon>
            </template>
            <v-list-item-title>{{ $t('navigation.profile') }}</v-list-item-title>
          </v-list-item>
          <v-divider class="mx-2"></v-divider>
          <v-list-item @click="logout" 
                       style="border-radius: 8px; margin: 4px;">
            <template v-slot:prepend>
              <v-icon color="error">mdi-logout</v-icon>
            </template>
            <v-list-item-title>{{ $t('navigation.logout') }}</v-list-item-title>
          </v-list-item>
        </v-list>
      </v-menu>
    </v-app-bar>

    <!-- Main Content -->
    <v-main style="background: #f9fafb;">
      <slot name="content">
        <v-container fluid class="pa-6">
          <slot></slot>
        </v-container>
      </slot>
    </v-main>
  </v-app>
</template>

<script>
import { router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import LanguageSwitcher from '@/Components/LanguageSwitcher.vue'

export default {
  name: 'AppLayout',
  components: {
    LanguageSwitcher
  },
  props: {
    title: {
      type: String,
      default: null
    }
  },
  setup() {
    const { t } = useI18n()
    return { t }
  },
  data() {
    return {
      drawer: true,
      parcelSearchQuery: ''
    }
  },
  methods: {
    logout() {
      router.post('/logout')
    },
    searchParcel() {
      if (!this.parcelSearchQuery || this.parcelSearchQuery.trim() === '') {
        return
      }
      
      // Navigate to parcels page with search query
      router.get('/parcels', {
        search: this.parcelSearchQuery.trim(),
        page: 1
      }, {
        preserveState: false,
        preserveScroll: false
      })
    },
    clearParcelSearch() {
      this.parcelSearchQuery = ''
    }
  }
}
</script>

<style scoped>
/* Glassmorphism Effect for Toolbar */
:deep(.v-toolbar__content) {
  background: rgba(255, 255, 255, 0.011) !important;
  backdrop-filter: blur(20px) saturate(180%);
  -webkit-backdrop-filter: blur(20px) saturate(180%);
  border-bottom: 1px solid rgba(255, 255, 255, 0.3);
  box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05);
}

/* Modern Sidebar Styling */
.sidebar-item {
  position: relative;
  border-radius: 10px;
  margin-bottom: 4px;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  min-height: 44px;
}

.sidebar-item:hover {
  background: rgba(255,255,255,0.08) !important;
  transform: translateX(4px);
}

.sidebar-item-active {
  background: rgba(102, 126, 234, 0.15) !important;
  border-left: 3px solid #667eea;
}

.sidebar-item-active:hover {
  background: rgba(102, 126, 234, 0.2) !important;
}

/* Smooth transitions */
.v-navigation-drawer {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Custom scrollbar for sidebar */
.v-navigation-drawer ::-webkit-scrollbar {
  width: 6px;
}

.v-navigation-drawer ::-webkit-scrollbar-track {
  background: rgba(255,255,255,0.05);
  border-radius: 3px;
}

.v-navigation-drawer ::-webkit-scrollbar-thumb {
  background: rgba(255,255,255,0.15);
  border-radius: 3px;
}

.v-navigation-drawer ::-webkit-scrollbar-thumb:hover {
  background: rgba(255,255,255,0.25);
}

/* User chip hover effect */
.user-chip {
  transition: all 0.2s ease;
}

.user-chip:hover {
  background: #e5e7eb !important;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

/* Parcel Search Field - NUCLEAR Vuetify Override */
.parcel-search-field :deep(.v-input__control) {
  border-radius: 8px !important;
  background: white !important;
  border: 1px solid #d1d5db !important;
  transition: all 0.2s ease !important;
  box-shadow: none !important;
  outline: none !important;
}

.parcel-search-field :deep(.v-field__outline),
.parcel-search-field :deep(.v-field__outline__start),
.parcel-search-field :deep(.v-field__outline__end),
.parcel-search-field :deep(.v-field__outline__notch) {
  display: none !important;
  visibility: hidden !important;
  opacity: 0 !important;
  height: 0 !important;
  width: 0 !important;
  border: none !important;
  outline: none !important;
  box-shadow: none !important;
}

.parcel-search-field :deep(.v-field__outline__start::before),
.parcel-search-field :deep(.v-field__outline__end::before),
.parcel-search-field :deep(.v-field__outline__notch::before) {
  display: none !important;
  visibility: hidden !important;
  opacity: 0 !important;
  height: 0 !important;
  width: 0 !important;
  border: none !important;
  outline: none !important;
  box-shadow: none !important;
}

.parcel-search-field :deep(.v-input--is-focused .v-input__control) {
  border-color: #6b7280 !important;
  box-shadow: 0 0 0 3px rgba(107, 114, 128, 0.1) !important;
  outline: none !important;
}

.parcel-search-field :deep(.v-input__control:hover) {
  border-color: #9ca3af !important;
  outline: none !important;
}

.parcel-search-field :deep(.v-field__input) {
  padding: 8px 12px !important;
  font-size: 14px !important;
  outline: none !important;
}

.parcel-search-field :deep(.v-field__append-inner) {
  padding: 0 8px !important;
}

/* Force remove any remaining Vuetify focus styling */
.parcel-search-field :deep(.v-input--is-focused *) {
  outline: none !important;
}

.parcel-search-field :deep(.v-field__outline *) {
  display: none !important;
  visibility: hidden !important;
  opacity: 0 !important;
}
</style>
