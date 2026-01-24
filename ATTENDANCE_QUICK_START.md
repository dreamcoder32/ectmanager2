# Attendance Management System - Quick Start Guide

## 🚀 Quick Setup (5 Minutes)

### Step 1: Install & Migrate (1 min)

```bash
cd /Users/mac/ect2/delivery-management-system
composer update
php artisan migrate
```

### Step 2: Configure Your ZKTeco K60 Pro Device (2 min)

1. **Find Device IP Address:**
   - On the device, go to: Menu → System → Network
   - Note the IP address (e.g., `192.168.1.100`)
   - Note the port (usually `4370`)

2. **Enable Network Communication:**
   - Ensure the device is connected to the same network as your server
   - Test ping from your server: `ping 192.168.1.100`

### Step 3: Add Device to System (1 min)

1. Login to your delivery management system
2. Navigate to: **Attendance → Manage Devices**
3. Click **Add New Device**
4. Fill in:
   ```
   Name: Main Office K60 Pro
   Device Model: K60 Pro
   Serial Number: [Check device label]
   IP Address: 192.168.1.100
   Port: 4370
   Location: Main Office
   Status: Active ✓
   ```
5. Click **Save** and then **Test Connection**

### Step 4: Configure Employees (1 min)

1. Go to **Users Management**
2. For each employee, click **Edit**
3. Set:
   - **Device User ID**: The ID number shown on the ZKTeco device (e.g., 1, 2, 3...)
   - **Attendance Enabled**: ✓ Check this box
4. Click **Save**

### Step 5: Sync & View (30 seconds)

1. Go to **Attendance Dashboard**
2. Click **Sync Devices**
3. Wait for sync to complete
4. View attendance data!

---

## 📊 Daily Usage

### Morning Routine
- Employees punch in on the ZKTeco device
- System automatically records attendance

### Sync Attendance (Do this at least once daily)
1. Go to **Attendance Dashboard**
2. Click **Sync Devices** button
3. Data is automatically calculated

### View Reports
- **Dashboard**: See all employees' monthly statistics
- **Employee Details**: Click eye icon to see daily records
- **Export**: Click Export Report for Excel file

---

## 🔧 Common Tasks

### Add a New Employee to Attendance
1. Enroll employee on ZKTeco device (fingerprint/face)
2. Note the User ID assigned by device
3. In system: Users → Edit Employee
4. Set Device User ID and enable attendance
5. Save

### Manual Punch Entry (for corrections)
1. Attendance Dashboard → Manual Punch
2. Select employee, date, time, and type
3. Add note explaining why
4. Save

### Monthly Report
1. Select month and year
2. Review all employee statistics
3. Click Export Report
4. Use Excel file for payroll

---

## 📱 What Employees See on ZKTeco Device

1. **Punch In (Morning)**
   - Place finger on scanner or look at camera
   - Device shows: "Thank you" or beep sound
   - Screen shows time recorded

2. **Punch Out (Evening)**
   - Same process as punch in
   - Device automatically determines if check-in or check-out

3. **Break Time (Optional)**
   - Punch out when starting break
   - Punch in when returning from break

---

## 💡 Pro Tips

### For Accurate Tracking
- ✅ Sync attendance daily
- ✅ Review summaries weekly
- ✅ Export monthly reports for records
- ✅ Add notes for exceptions

### For Best Performance
- ✅ Keep device time synchronized
- ✅ Test device connection weekly
- ✅ Clean fingerprint scanner regularly
- ✅ Update employee list when hiring/leaving

### For Payroll
- ✅ Export reports at month end
- ✅ Review overtime hours
- ✅ Check late/absent days
- ✅ Verify total work hours

---

## 🎯 Monthly Workflow

### Week 1-4: Daily Operations
- Employees punch in/out normally
- Sync once daily (or set automatic sync)
- Review and add notes as needed

### End of Month
1. **Final Sync**: Sync all devices one last time
2. **Review**: Check all employee summaries
3. **Export**: Download monthly report
4. **Payroll**: Use work hours for salary calculation
5. **Archive**: Save Excel file for records

---

## 🆘 Quick Troubleshooting

| Problem | Solution |
|---------|----------|
| Device won't connect | Check IP, port, and network connectivity |
| No attendance records | Verify employee has Device User ID set |
| Wrong work hours | Check Attendance Settings for work times |
| Employee not in list | Enable "Attendance Enabled" in user profile |
| Sync takes too long | Normal for first sync, faster afterwards |

---

## 📞 Need Help?

1. **Check Documentation**: See `ATTENDANCE_SYSTEM_README.md`
2. **Test Connection**: Use the Test Connection button
3. **Review Logs**: Check device sync error messages
4. **Manual Entry**: Use manual punch as temporary fix

---

## 🎉 You're All Set!

Your attendance system is now ready to:
- ✅ Track employee attendance automatically
- ✅ Calculate work hours precisely
- ✅ Generate monthly reports
- ✅ Integrate with payroll

**Next Steps:**
1. Configure attendance settings (work hours, grace period)
2. Set up automatic sync schedule
3. Train employees on device usage
4. Review first month's data

---

## 📈 Features You'll Love

- **Automatic Calculation**: Work hours, overtime, breaks
- **Late Tracking**: Know who arrives late and by how much
- **Excel Export**: Easy payroll integration
- **Multiple Devices**: Support for multiple locations
- **Manual Corrections**: Admin can fix missed punches
- **Daily Summaries**: See attendance at a glance
- **Monthly Reports**: Complete statistics per employee

---

**Enjoy your new attendance management system! 🎊**
