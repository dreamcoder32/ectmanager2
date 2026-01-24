# Adding Attendance to Navigation Menu

## Quick Guide to Add Attendance Menu Items

### Option 1: Add to AppLayout Navigation

If your `AppLayout.vue` has a navigation menu, add these items:

```vue
<!-- In your navigation items array -->
{
  title: 'Attendance',
  icon: 'mdi-calendar-check',
  children: [
    {
      title: 'Dashboard',
      icon: 'mdi-view-dashboard',
      route: '/attendance'
    },
    {
      title: 'Daily View',
      icon: 'mdi-calendar-today',
      route: '/attendance/daily'
    },
    {
      title: 'Devices',
      icon: 'mdi-devices',
      route: '/attendance/devices'
    },
    {
      title: 'Settings',
      icon: 'mdi-cog',
      route: '/attendance/settings'
    }
  ]
}
```

### Option 2: Direct Links

Add these links to your navigation:

```vue
<v-list-item to="/attendance">
  <template v-slot:prepend>
    <v-icon>mdi-calendar-check</v-icon>
  </template>
  <v-list-item-title>Attendance</v-list-item-title>
</v-list-item>

<v-list-item to="/attendance/devices">
  <template v-slot:prepend>
    <v-icon>mdi-devices</v-icon>
  </template>
  <v-list-item-title>Attendance Devices</v-list-item-title>
</v-list-item>
```

### Option 3: Dashboard Widget

Add a quick access widget to your main dashboard:

```vue
<v-card @click="$inertia.visit('/attendance')" style="cursor: pointer;">
  <v-card-text class="text-center">
    <v-icon size="48" color="primary">mdi-calendar-check</v-icon>
    <div class="text-h6 mt-2">Attendance</div>
    <div class="text-caption">Manage employee attendance</div>
  </v-card-text>
</v-card>
```

## Icons Used

The attendance system uses these Material Design Icons:
- `mdi-calendar-check` - Main attendance icon
- `mdi-devices` - Device management
- `mdi-clock-outline` - Work hours
- `mdi-account-group` - Employees
- `mdi-sync` - Sync button
- `mdi-download` - Export
- `mdi-cog` - Settings

## Routes Available

### Main Routes
- `/attendance` - Main dashboard
- `/attendance/daily` - Daily view
- `/attendance/users/{id}` - Employee details
- `/attendance/devices` - Device management
- `/attendance/settings` - Settings

### Device Routes
- `/attendance/devices/create` - Add device
- `/attendance/devices/{id}` - View device
- `/attendance/devices/{id}/edit` - Edit device

## Permissions (Optional)

If you want to restrict access:

```php
// In your middleware or permissions
Route::middleware(['auth', 'can:manage-attendance'])->group(function () {
    // Attendance routes
});
```

Or in your navigation:

```vue
<v-list-item 
  v-if="$page.props.auth.user.role === 'admin'"
  to="/attendance"
>
  <!-- Menu item -->
</v-list-item>
```

## Quick Access Buttons

Add these to your dashboard for quick access:

```vue
<!-- Sync Button -->
<v-btn
  color="primary"
  @click="syncAttendance"
  prepend-icon="mdi-sync"
>
  Sync Attendance
</v-btn>

<!-- View Reports -->
<v-btn
  color="info"
  @click="$inertia.visit('/attendance')"
  prepend-icon="mdi-chart-bar"
>
  View Reports
</v-btn>

<!-- Manage Devices -->
<v-btn
  color="success"
  @click="$inertia.visit('/attendance/devices')"
  prepend-icon="mdi-devices"
>
  Manage Devices
</v-btn>
```

## Example: Full Navigation Section

```vue
<template>
  <v-navigation-drawer>
    <!-- Other menu items -->
    
    <!-- Attendance Section -->
    <v-list-group>
      <template v-slot:activator="{ props }">
        <v-list-item
          v-bind="props"
          prepend-icon="mdi-calendar-check"
          title="Attendance"
        ></v-list-item>
      </template>

      <v-list-item
        to="/attendance"
        prepend-icon="mdi-view-dashboard"
        title="Dashboard"
      ></v-list-item>

      <v-list-item
        to="/attendance/daily"
        prepend-icon="mdi-calendar-today"
        title="Daily View"
      ></v-list-item>

      <v-list-item
        to="/attendance/devices"
        prepend-icon="mdi-devices"
        title="Devices"
      ></v-list-item>

      <v-list-item
        to="/attendance/settings"
        prepend-icon="mdi-cog"
        title="Settings"
        v-if="$page.props.auth.user.role === 'admin'"
      ></v-list-item>
    </v-list-group>
  </v-navigation-drawer>
</template>
```

## Dashboard Card Example

```vue
<v-col cols="12" md="6" lg="4">
  <v-card 
    style="border-radius: 12px;" 
    elevation="1"
    @click="$inertia.visit('/attendance')"
    class="cursor-pointer hover-lift"
  >
    <v-card-text class="text-center pa-6">
      <v-avatar color="primary" size="64" class="mb-4">
        <v-icon color="white" size="32">mdi-calendar-check</v-icon>
      </v-avatar>
      <div class="text-h5 font-weight-bold mb-2">Attendance</div>
      <div class="text-subtitle-2 text--secondary mb-4">
        Track employee attendance and work hours
      </div>
      <v-chip color="success" variant="tonal" size="small">
        <v-icon start>mdi-check-circle</v-icon>
        Active
      </v-chip>
    </v-card-text>
  </v-card>
</v-col>
```

## Notification Badge (Optional)

Show pending sync count:

```vue
<v-badge
  :content="pendingSyncCount"
  :value="pendingSyncCount > 0"
  color="error"
>
  <v-btn icon="mdi-calendar-check" @click="$inertia.visit('/attendance')"></v-btn>
</v-badge>
```

That's it! Choose the option that best fits your application's navigation structure.
