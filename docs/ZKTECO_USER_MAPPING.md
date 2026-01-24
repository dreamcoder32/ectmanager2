# ZKTeco Device User Mapping Guide

## Overview
The ZKTeco attendance device integration requires mapping users in your system to users enrolled on the physical device. This is done using the `device_user_id` field.

## How It Works

### 1. **Enroll Users on the ZKTeco Device**
   - Go to the ZKTeco device menu
   - Navigate to: **User Management** → **New User**
   - Assign each user a unique **User ID** (e.g., 1, 2, 3, etc.)
   - Enroll their fingerprint or face
   - Save the user

### 2. **Map Users in Your System**
   - Go to **Users** page in your application
   - Click **Edit** on a user
   - In the **Device User ID** field, enter the **exact same ID** you assigned on the ZKTeco device
   - Save the user

### 3. **Sync Attendance**
   - Go to **Attendance** → **Devices**
   - Click **Sync** on your ZKTeco device
   - The system will automatically:
     - Fetch attendance records from the device
     - Match them to users using the `device_user_id`
     - Create attendance records in your database

## Example Mapping

| System User | Device User ID | ZKTeco Device User |
|-------------|----------------|-------------------|
| John Doe    | 1              | User ID: 1        |
| Jane Smith  | 2              | User ID: 2        |
| Bob Johnson | 3              | User ID: 3        |

## Important Notes

- ✅ **Device User ID must be unique** - Each user must have a different ID
- ✅ **IDs must match exactly** - The ID in your system must match the ID on the device
- ✅ **Optional field** - Users without a Device User ID won't have attendance tracked
- ⚠️ **Case sensitive** - Make sure IDs match exactly (though typically they're just numbers)

## Troubleshooting

### "No local user found for DeviceUserID: X"
This warning appears in logs when the device has attendance records for a user ID that doesn't exist in your system.

**Solution**: 
1. Check which users are enrolled on the device
2. Make sure each enrolled user has a matching user in your system with the correct `device_user_id`

### Attendance not syncing
1. Verify the device is connected (check IP and port)
2. Ensure Comm Key is set to `0` on the device
3. Check that users have matching `device_user_id` values
4. Review logs at `storage/logs/laravel.log` for detailed error messages

## Database Schema

The `device_user_id` field is stored in the `users` table:
```sql
ALTER TABLE users ADD COLUMN device_user_id VARCHAR(255) NULL UNIQUE;
```

This field is:
- **Nullable**: Not all users need attendance tracking
- **Unique**: Each user must have a different device ID
- **String**: Can be alphanumeric (though typically just numbers)
