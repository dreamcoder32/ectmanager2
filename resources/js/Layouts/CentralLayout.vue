<template>
    <v-app>
        <!-- Navigation Drawer -->
        <v-navigation-drawer
            v-model="drawer"
            app
            width="280"
            class="sidebar-modern"
            style="background: #1a1d29; border-right: 1px solid rgba(255, 255, 255, 0.08);"
        >
            <!-- Header Section -->
            <div class="pa-5 text-center position-relative" style="background: linear-gradient(135deg, #2c3142 0%, #1a1d29 100%); border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
                <div class="d-flex align-center justify-start mb-3 px-2">
                    <v-avatar size="48" class="mr-3" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);">
                        <v-icon size="28" color="white">mdi-shield-crown</v-icon>
                    </v-avatar>
                    <div class="text-left">
                        <h2 class="text-h6 font-weight-bold text-white mb-0">Central Admin</h2>
                        <p class="text-caption text-grey-lighten-1 mb-0">Tenant Management</p>
                    </div>
                </div>
            </div>

            <!-- Navigation Menu -->
            <v-list class="pa-3" nav>
                <v-list-item
                    @click="$inertia.visit('/dashboard')"
                    link
                    :class="{ 'sidebar-item-active': $page.component === 'Central/Index' }"
                    class="sidebar-item mb-1"
                >
                    <template v-slot:prepend>
                        <v-icon :color="$page.component === 'Central/Index' ? '#667eea' : '#8b92a8'" size="22">mdi-view-dashboard</v-icon>
                    </template>
                    <v-list-item-title :class="$page.component === 'Central/Index' ? 'text-white' : 'text-grey-lighten-1'" class="font-weight-medium text-body-2">
                        Dashboard
                    </v-list-item-title>
                </v-list-item>

                <v-list-item
                    @click="$inertia.visit('/tenants/create')"
                    link
                    :class="{ 'sidebar-item-active': $page.component === 'Central/Create' }"
                    class="sidebar-item mb-1"
                >
                    <template v-slot:prepend>
                        <v-icon :color="$page.component === 'Central/Create' ? '#667eea' : '#8b92a8'" size="22">mdi-plus-box</v-icon>
                    </template>
                    <v-list-item-title :class="$page.component === 'Central/Create' ? 'text-white' : 'text-grey-lighten-1'" class="font-weight-medium text-body-2">
                        Create Tenant
                    </v-list-item-title>
                </v-list-item>
            </v-list>

            <!-- Footer Section -->
            <template v-slot:append>
                <div class="pa-4 text-center" style="background: rgba(255, 255, 255, 0.03); border-top: 1px solid rgba(255, 255, 255, 0.08);">
                    <p class="text-caption text-grey-darken-1 mb-0">© 2025 Central Admin</p>
                </div>
            </template>
        </v-navigation-drawer>

        <!-- App Bar -->
        <v-app-bar app elevation="0" style="background: rgba(255, 255, 255, 0.5); border-bottom: 1px solid rgba(229, 231, 235, 0.5); backdrop-filter: blur(10px);">
            <v-app-bar-nav-icon @click="drawer = !drawer" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 10px;"></v-app-bar-nav-icon>
            <v-toolbar-title class="ml-4">
                <span class="text-h6 font-weight-bold" style="color: #1a1d29">
                    <slot name="title">{{ title || "Central Dashboard" }}</slot>
                </span>
            </v-toolbar-title>
            <v-spacer></v-spacer>
            
            <!-- User Menu -->
            <v-menu>
                <template v-slot:activator="{ props }">
                    <v-chip v-bind="props" class="user-chip" variant="flat" style="background: #f3f4f6; border-radius: 12px; padding: 8px 12px;">
                        <v-avatar size="32" class="mr-2" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <v-icon color="white" size="18">mdi-account</v-icon>
                        </v-avatar>
                        <span class="text-body-2 font-weight-medium d-none d-sm-inline" style="color: #1a1d29;">Admin</span>
                    </v-chip>
                </template>
                <v-list style="border-radius: 12px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb;">
                    <v-list-item @click="logout" style="border-radius: 8px; margin: 4px">
                        <template v-slot:prepend>
                            <v-icon color="error">mdi-logout</v-icon>
                        </template>
                        <v-list-item-title>Logout</v-list-item-title>
                    </v-list-item>
                </v-list>
            </v-menu>
        </v-app-bar>

        <!-- Main Content -->
        <v-main style="background: #f9fafb">
            <slot name="content">
                <v-container fluid class="pa-6">
                    <slot></slot>
                </v-container>
            </slot>
        </v-main>
    </v-app>
</template>

<script>
import { router } from "@inertiajs/vue3";

export default {
    name: "CentralLayout",
    props: {
        title: {
            type: String,
            default: null,
        },
    },
    data() {
        return {
            drawer: true,
        };
    },
    methods: {
        logout() {
            router.post("/logout");
        },
    },
};
</script>

<style scoped>
/* Reuse styles from AppLayout */
:deep(.v-toolbar__content) {
    background: rgba(255, 255, 255, 0.011) !important;
    backdrop-filter: blur(20px) saturate(180%);
    -webkit-backdrop-filter: blur(20px) saturate(180%);
    border-bottom: 1px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05);
}
.sidebar-item {
    position: relative;
    border-radius: 10px;
    margin-bottom: 4px;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    min-height: 44px;
}
.sidebar-item:hover {
    background: rgba(255, 255, 255, 0.08) !important;
    transform: translateX(4px);
}
.sidebar-item-active {
    background: rgba(102, 126, 234, 0.15) !important;
    border-left: 3px solid #667eea;
}
.sidebar-item-active:hover {
    background: rgba(102, 126, 234, 0.2) !important;
}
.v-navigation-drawer {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>
