<template>
  <v-app>
    <!-- Navigation Drawer -->
    <v-navigation-drawer
      v-model="drawer"
      app
      width="300"
      class="sidebar-gradient"
      style="background: linear-gradient(180deg, #1976d2 0%, #1565c0 50%, #0d47a1 100%);
             border-right: 1px solid rgba(255,255,255,0.1);"
    >
      <!-- Header Section -->
      <div class="pa-6 text-center position-relative" 
           style="background: rgba(255,255,255,0.1); 
                  backdrop-filter: blur(10px);
                  border-bottom: 1px solid rgba(255,255,255,0.1);">
        
        <!-- Decorative background element -->
        <div class="position-absolute" 
             style="top: -30px; right: -30px; width: 80px; height: 80px; 
                    background: rgba(255,255,255,0.05); 
                    border-radius: 50%;"></div>
        
        <div class="d-flex align-center justify-center mb-3">
          <v-avatar size="56" 
                    class="mr-3"
                    style="background: rgba(255,255,255,0.2); 
                           backdrop-filter: blur(10px);
                           border: 2px solid rgba(255,255,255,0.3);">
            <v-icon size="32" color="white">mdi-truck-delivery</v-icon>
          </v-avatar>
          <div class="text-left">
            <h2 class="text-h5 font-weight-bold text-white mb-1">ECTManager</h2>
            <p class="text-body-2 text-white opacity-80 mb-0">Management System</p>
          </div>
        </div>
      </div>

      <!-- Navigation Menu -->
      <v-list class="pa-4" nav>
        <v-list-item 
          @click="$inertia.visit('/dashboard')" 
          link
          :class="{ 'sidebar-item-active': $page.component === 'Dashboard/Index' }"
          class="sidebar-item mb-2 d-flex align-center"
          style="border-radius: 12px; 
                 transition: all 0.3s ease;
                 backdrop-filter: blur(10px);
                 min-height: 48px;
                 padding: 12px 16px;"
        >
          <v-icon color="white" size="24" class="mr-4">mdi-view-dashboard</v-icon>
          <span class="text-white font-weight-medium text-body-1">
            {{ $t('navigation.dashboard') }}
          </span>
        </v-list-item>

        <v-list-item 
          @click="$inertia.visit('/parcels')" 
          link
          :class="{ 'sidebar-item-active': $page.component.startsWith('Parcels/') }"
          class="sidebar-item mb-2 d-flex align-center"
          style="border-radius: 12px; 
                 transition: all 0.3s ease;
                 backdrop-filter: blur(10px);
                 min-height: 48px;
                 padding: 12px 16px;"
        >
          <v-icon color="white" size="24" class="mr-4">mdi-package-variant</v-icon>
          <span class="text-white font-weight-medium text-body-1">
            {{ $t('navigation.parcels') }}
          </span>
        </v-list-item>

        <v-list-item 
          @click="$inertia.visit('/stopdesk-payment')" 
          link
          :class="{ 'sidebar-item-active': $page.component.startsWith('StopDeskPayment/') }"
          class="sidebar-item mb-2 d-flex align-center"
          style="border-radius: 12px; 
                 transition: all 0.3s ease;
                 backdrop-filter: blur(10px);
                 min-height: 48px;
                 padding: 12px 16px;"
        >
          <v-icon color="white" size="24" class="mr-4">mdi-cash-register</v-icon>
          <span class="text-white font-weight-medium text-body-1">
            Stopdesk Payment
          </span>
        </v-list-item>

        <v-list-item 
          @click="$inertia.visit('/recoltes')" 
          link
          :class="{ 'sidebar-item-active': $page.component.startsWith('Recoltes/') }"
          class="sidebar-item mb-2 d-flex align-center"
          style="border-radius: 12px; 
                 transition: all 0.3s ease;
                 backdrop-filter: blur(10px);
                 min-height: 48px;
                 padding: 12px 16px;"
        >
          <v-icon color="white" size="24" class="mr-4">mdi-cash-multiple</v-icon>
          <span class="text-white font-weight-medium text-body-1">
            Collection Transfer
          </span>
        </v-list-item
        >

        <!-- Driver Settlement -->
        <v-list-item 
          @click="$inertia.visit('/driver-settlement')" 
          link
          :class="{ 'sidebar-item-active': $page.component.startsWith('DriverSettlement/') }"
          class="sidebar-item mb-2 d-flex align-center"
          style="border-radius: 12px; 
                 transition: all 0.3s ease;
                 backdrop-filter: blur(10px);
                 min-height: 48px;
                 padding: 12px 16px;"
        >
          <v-icon color="white" size="24" class="mr-4">mdi-hand-coin</v-icon>
          <span class="text-white font-weight-medium text-body-1">
            Driver Settlement
          </span>
        </v-list-item>

        <v-list-item 
          @click="$inertia.visit('/expenses')" 
          link
          :class="{ 'sidebar-item-active': $page.component.startsWith('Expense/') }"
          class="sidebar-item mb-2 d-flex align-center"
          style="border-radius: 12px; 
                 transition: all 0.3s ease;
                 backdrop-filter: blur(10px);
                 min-height: 48px;
                 padding: 12px 16px;"
        >
          <v-icon color="white" size="24" class="mr-4">mdi-receipt</v-icon>
          <span class="text-white font-weight-medium text-body-1">
            {{t('navigation.expenses')}}
          </span>
        </v-list-item>

        <!-- Admin Only: Expense Categories -->
        <v-list-item 
          v-if="$page.props.auth.user.role === 'admin'"
          @click="$inertia.visit('/expense-categories')" 
          link
          :class="{ 'sidebar-item-active': $page.component.startsWith('ExpenseCategory/') }"
          class="sidebar-item mb-2 d-flex align-center"
          style="border-radius: 12px; 
                 transition: all 0.3s ease;
                 backdrop-filter: blur(10px);
                 min-height: 48px;
                 padding: 12px 16px;"
        >
          <v-icon color="white" size="24" class="mr-4">mdi-tag-multiple</v-icon>
          <span class="text-white font-weight-medium text-body-1">
            Expense Categories
          </span>
        </v-list-item>

        <!-- Supervisor & Admin Only: User Management -->
        <v-list-item 
          v-if="$page.props.auth.user.role === 'admin' || $page.props.auth.user.role === 'supervisor'"
          @click="$inertia.visit('/users')" 
          link
          :class="{ 'sidebar-item-active': $page.component.startsWith('Users/') }"
          class="sidebar-item mb-2 d-flex align-center"
          style="border-radius: 12px; 
                 transition: all 0.3s ease;
                 backdrop-filter: blur(10px);
                 min-height: 48px;
                 padding: 12px 16px;"
        >
          <v-icon color="white" size="24" class="mr-4">mdi-account-group</v-icon>
          <span class="text-white font-weight-medium text-body-1">
            User Management
          </span>
        </v-list-item>

        <!-- Additional Menu Items -->
        <!-- <v-list-item 
          @click="$inertia.visit('/reports')" 
          link
          class="sidebar-item mb-2 d-flex align-center"
          style="border-radius: 12px; 
                 transition: all 0.3s ease;
                 backdrop-filter: blur(10px);
                 min-height: 48px;
                 padding: 12px 16px;"
        >
          <v-icon color="white" size="24" class="mr-4">mdi-chart-line</v-icon>
          <span class="text-white font-weight-medium text-body-1">
            {{ $t('navigation.reports') }}
          </span>
        </v-list-item> -->
<!-- 
        <v-list-item 
          @click="$inertia.visit('/settings')" 
          link
          class="sidebar-item mb-2 d-flex align-center"
          style="border-radius: 12px; 
                 transition: all 0.3s ease;
                 backdrop-filter: blur(10px);
                 min-height: 48px;
                 padding: 12px 16px;"
        >
          <v-icon color="white" size="24" class="mr-4">mdi-cog</v-icon>
          <span class="text-white font-weight-medium text-body-1">
            {{ $t('navigation.settings') }}
          </span>
        </v-list-item> -->
      </v-list>

      <!-- Footer Section -->
      <template v-slot:append>
        <div class="pa-4 text-center" 
             style="background: rgba(255,255,255,0.05); 
                    border-top: 1px solid rgba(255,255,255,0.1);">
          <p class="text-body-2 text-white opacity-70 mb-2">
            {{ $t('common.version') }} 2.0.1
          </p>
          <p class="text-caption text-white opacity-50 mb-0">
            © 2025 ECTManager
          </p>
        </div>
      </template>
    </v-navigation-drawer>

    <!-- App Bar -->
    <v-app-bar app 
               elevation="0"
               style="background: linear-gradient(90deg, #e3f2fd 0%, #bbdefb 100%);
                      border-bottom: 1px solid rgba(0,0,0,0.05);
                      backdrop-filter: blur(10px);">
      
      <v-app-bar-nav-icon 
        @click="drawer = !drawer"
        style="background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
               color: white;
               border-radius: 8px;"
      ></v-app-bar-nav-icon>
      
      <v-toolbar-title class="ml-4">
        <span class="text-h6 font-weight-bold" 
              style="background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
                     -webkit-background-clip: text;
                     -webkit-text-fill-color: transparent;
                     background-clip: text;">
          <slot name="title">{{ title || 'Delivery Management System' }}</slot>
        </span>
      </v-toolbar-title>
      
      <v-spacer></v-spacer>
      
      <!-- Action Buttons Slot -->
      <slot name="actions"></slot>

      <!-- Language Switcher -->
      <LanguageSwitcher class="mr-2" />

      <!-- User Menu -->
      <v-menu>
        <template v-slot:activator="{ props }">
          <v-btn icon v-bind="props" class="ml-2">
            <v-avatar size="40" 
                      style="background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
                             border: 2px solid rgba(102, 126, 234, 0.2);">
              <v-icon color="white">mdi-account-circle</v-icon>
            </v-avatar>
          </v-btn>
        </template>
        <v-list style="border-radius: 12px; 
                       box-shadow: 0 8px 32px rgba(0,0,0,0.1);
                       border: 1px solid rgba(0,0,0,0.05);">
          <v-list-item @click="$inertia.visit('/profile')" 
                       style="border-radius: 8px; margin: 4px;">
            <template v-slot:prepend>
              <v-icon color="primary">mdi-account</v-icon>
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
    <v-main style="background: linear-gradient(135deg, #e8f4fd 0%, #d1e7dd 100%);">
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
      drawer: true
    }
  },
  methods: {
    logout() {
      router.post('/logout')
    }
  }
}
</script>

<style scoped>
/* Sidebar animations and effects */
.sidebar-item {
  position: relative;
  overflow: hidden;
}

.sidebar-item::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: rgba(255,255,255,0.1);
  transition: left 0.3s ease;
}

.sidebar-item:hover::before {
  left: 0;
}

.sidebar-item:hover {
  background: rgba(255,255,255,0.15) !important;
  transform: translateX(4px);
  box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.sidebar-item-active {
  background: rgba(255,255,255,0.2) !important;
  box-shadow: 0 4px 20px rgba(0,0,0,0.15);
  border: 1px solid rgba(255,255,255,0.3);
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
  background: rgba(255,255,255,0.1);
  border-radius: 3px;
}

.v-navigation-drawer ::-webkit-scrollbar-thumb {
  background: rgba(255,255,255,0.3);
  border-radius: 3px;
}

.v-navigation-drawer ::-webkit-scrollbar-thumb:hover {
  background: rgba(255,255,255,0.5);
}
</style>