# Attendance Management System - ZKTeco K60 Pro Integration

## Overview

This attendance management system integrates with ZKTeco K60 Pro biometric devices to automatically track employee attendance, calculate work hours, and generate monthly reports.

## Features

### 1. **Device Management**
- Add and configure multiple ZKTeco K60 Pro devices
- Test device connections
- Monitor sync status and errors
- Track total attendance records per device

### 2. **Attendance Tracking**
- Automatic sync from ZKTeco devices
- Support for multiple punch types (check-in, check-out, break)
- Fingerprint, face, card, and password verification modes
- Manual punch entry for corrections

### 3. **Daily Summaries**
- Automatic calculation of:
  - Total work hours
  - Break time
  - Overtime hours
  - Late arrivals
  - Early departures
- Attendance status (Present, Absent, Half Day, Leave, Holiday)

### 4. **Monthly Reports**
- Employee-wise attendance statistics
- Total work hours per month
- Present/Absent/Half day counts
- Late day tracking
- Excel export functionality

### 5. **Configurable Settings**
- Standard work hours
- Grace period for late arrivals
- Working days configuration
- Auto-checkout settings
- Sync interval customization

## Installation & Setup

### 1. Install Dependencies

```bash
cd /Users/mac/ect2/delivery-management-system
composer update
```

### 2. Run Migrations

```bash
php artisan migrate
```

This will create the following tables:
- `attendance_devices` - Store device information
- `attendance_records` - Store individual punch records
- `attendance_summaries` - Store daily attendance summaries
- `attendance_settings` - Store system configuration
- Updates to `users` table for attendance fields

### 3. Configure Your ZKTeco K60 Pro Device

1. **Connect the device to your network**
   - Access the device settings menu
   - Go to Network Settings
   - Configure IP address (e.g., 192.168.1.100)
   - Note the port number (default: 4370)

2. **Enable UDP Communication**
   - Ensure UDP protocol is enabled on the device
   - Check firewall settings to allow communication

3. **Register Users on Device**
   - Each employee needs a unique device user ID
   - Enroll fingerprints/face data on the device
   - Note the user ID for each employee

### 4. Add Device in System

1. Navigate to **Attendance > Manage Devices**
2. Click **Add New Device**
3. Fill in the device information:
   - **Name**: Descriptive name (e.g., "Main Office K60 Pro")
   - **Device Model**: K60 Pro
   - **Serial Number**: Device serial number
   - **IP Address**: Device IP (e.g., 192.168.1.100)
   - **Port**: Device port (default: 4370)
   - **Location**: Physical location (optional)
4. Click **Save Device**
5. Test the connection using the connection test button

### 5. Configure Employee Attendance

1. Go to **Users Management**
2. Edit each employee
3. Set the following fields:
   - **Device User ID**: The ID assigned on the ZKTeco device
   - **Attendance Enabled**: Check to enable tracking
   - **Custom Work Hours** (optional): Override default work hours

## Usage

### Syncing Attendance Data

**Automatic Sync:**
- The system can be configured to sync automatically at intervals
- Set sync interval in Attendance Settings

**Manual Sync:**
1. Go to **Attendance Dashboard**
2. Click **Sync Devices** button
3. Wait for sync to complete
4. View updated attendance records

### Viewing Attendance Reports

**Monthly Dashboard:**
1. Navigate to **Attendance**
2. Select month and year
3. View statistics for all employees:
   - Total work hours
   - Present/Absent days
   - Late days
   - Overtime hours

**Employee Details:**
1. Click the eye icon next to an employee
2. View daily attendance records
3. See check-in/check-out times
4. Add notes to specific days

### Manual Punch Entry

For corrections or missed punches:
1. Go to **Attendance Dashboard**
2. Click **Manual Punch** (admin only)
3. Select employee
4. Enter punch time and type
5. Add notes explaining the manual entry

### Exporting Reports

**All Employees:**
1. Go to **Attendance Dashboard**
2. Select month and year
3. Click **Export Report**
4. Download Excel file

**Single Employee:**
1. View employee attendance details
2. Click **Export Report**
3. Download Excel file

## Database Schema

### attendance_devices
- Device information and connection details
- Sync status tracking

### attendance_records
- Individual punch records
- Links to user and device
- Punch type and verification mode

### attendance_summaries
- Daily attendance summaries per user
- Calculated work hours and status
- Late/early departure tracking

### attendance_settings
- Global attendance configuration
- Work hours and grace periods
- Working days definition

### users (extended)
- `device_user_id` - ID in ZKTeco device
- `attendance_enabled` - Enable/disable tracking
- `custom_work_start_time` - Custom start time
- `custom_work_end_time` - Custom end time

## API Endpoints

### Attendance
- `GET /attendance` - Dashboard
- `GET /attendance/daily` - Daily view
- `GET /attendance/users/{user}` - User details
- `POST /attendance/sync` - Sync devices
- `POST /attendance/manual-punch` - Manual entry
- `GET /attendance/export` - Export report

### Devices
- `GET /attendance/devices` - List devices
- `POST /attendance/devices` - Create device
- `GET /attendance/devices/{device}` - View device
- `PUT /attendance/devices/{device}` - Update device
- `DELETE /attendance/devices/{device}` - Delete device
- `POST /attendance/devices/{device}/test-connection` - Test connection

## Troubleshooting

### Device Connection Issues

**Problem:** Cannot connect to device
**Solutions:**
1. Verify device IP address and port
2. Check network connectivity
3. Ensure device is powered on
4. Verify firewall settings
5. Check if UDP port is open

### Sync Errors

**Problem:** Sync fails or returns no data
**Solutions:**
1. Test device connection first
2. Check device has attendance records
3. Verify users have device_user_id set
4. Check device time synchronization
5. Review sync error messages

### Missing Attendance Records

**Problem:** Employee punches not appearing
**Solutions:**
1. Verify employee has device_user_id set
2. Check attendance_enabled is true
3. Ensure device is syncing properly
4. Verify employee is enrolled on device
5. Use manual punch as temporary solution

### Incorrect Work Hours

**Problem:** Work hours calculation seems wrong
**Solutions:**
1. Check attendance settings (work start/end times)
2. Verify employee custom work hours
3. Review punch records for the day
4. Check for missing check-out punches
5. Add manual punch to correct

## Best Practices

1. **Regular Syncing**
   - Sync at least once daily
   - Configure automatic sync for convenience

2. **User Management**
   - Keep device_user_id consistent
   - Disable attendance for inactive employees
   - Update work hours when schedules change

3. **Device Maintenance**
   - Test connections weekly
   - Monitor sync errors
   - Keep device firmware updated

4. **Data Review**
   - Review attendance summaries regularly
   - Add notes for exceptions
   - Export monthly reports for records

5. **Backup**
   - Regular database backups
   - Export monthly reports
   - Keep device configuration documented

## Monthly Workflow

1. **Beginning of Month**
   - Verify all devices are syncing
   - Check employee settings are current

2. **During Month**
   - Daily or automatic sync
   - Review and add notes as needed
   - Handle manual punches promptly

3. **End of Month**
   - Final sync for the month
   - Review all attendance summaries
   - Export monthly reports
   - Calculate payroll based on work hours

## Support

For technical issues or questions:
1. Check device documentation
2. Review error messages in device sync status
3. Test device connection
4. Check application logs
5. Contact system administrator

## Future Enhancements

Potential features for future development:
- Shift management
- Leave management integration
- Mobile app for attendance viewing
- Real-time notifications
- Advanced analytics and insights
- Integration with payroll system
- Multiple shift support
- Geofencing for mobile check-in
