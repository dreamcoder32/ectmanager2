# Testing Guide: Parcel Price Change Feature

This guide will help you test the new price change functionality in the WhatsApp Management page.

## Prerequisites

1. Make sure the migration has been run:
   ```bash
   php artisan migrate
   ```

2. Clear cache and rebuild assets if needed:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   npm run build
   # OR for development
   npm run dev
   ```

3. Ensure you're logged in as an authenticated user

## Test Scenarios

### 1. Visual Indicators Test

**Objective:** Verify that modified prices are visually indicated

**Steps:**
1. Navigate to `/whatsapp` (WhatsApp Management page)
2. Look at the "COD Amount" column
3. Initially, prices should show without any icon
4. After changing a price (see Test 2), the modified parcel should show a pencil icon (✏️)
5. Hover over the pencil icon
6. **Expected:** Tooltip shows "Price has been modified"

### 2. Change Price Test

**Objective:** Test the basic price change functionality

**Steps:**
1. Navigate to `/whatsapp`
2. Find any parcel in the table
3. Note the current price in the "COD Amount" column
4. In the "Actions" column, click the currency icon (💲)
5. **Expected:** "Change Parcel Price" dialog opens
6. Verify the dialog shows:
   - Tracking number (read-only)
   - Current price (read-only)
   - New price input field
   - Reason text area
   - "View Price History" button
7. Enter a new price (e.g., if current is 1000, enter 1500)
8. Optionally enter a reason (e.g., "Customer negotiation")
9. Click "Update Price"
10. **Expected:** 
    - Success message appears
    - Dialog closes
    - Page reloads
    - Price in the table is updated
    - Pencil icon appears next to the price

### 3. Validation Tests

**Test 3a: Same Price**

**Steps:**
1. Open price change dialog for a parcel
2. Enter the exact same price as current price
3. Click "Update Price"
4. **Expected:** Error message: "New price is the same as the current price."

**Test 3b: Negative Price**

**Steps:**
1. Open price change dialog
2. Enter a negative number (e.g., -100)
3. Click "Update Price"
4. **Expected:** Error message: "Please enter a valid price"

**Test 3c: Zero Price**

**Steps:**
1. Open price change dialog
2. Enter 0
3. Click "Update Price"
4. **Expected:** Should accept (0 is valid for free deliveries)

### 4. Price History Test

**Objective:** Test viewing price change history

**Steps:**
1. Open price change dialog for a parcel that has been modified
2. Click "View Price History" button
3. **Expected:** Price History dialog opens showing:
   - Tracking number at the top
   - Timeline of changes
   - Each entry shows:
     - User name who made the change
     - Date and time
     - Old price (in red chip)
     - Arrow icon
     - New price (in green chip)
     - Reason (if provided)

**Test 4b: No History**

**Steps:**
1. Open price change dialog for a parcel that has never been modified
2. Click "View Price History"
3. **Expected:** Message: "No price changes recorded for this parcel."

### 5. Multiple Changes Test

**Objective:** Verify that multiple price changes are tracked correctly

**Steps:**
1. Change a parcel's price from 1000 to 1500 (Reason: "Price increase")
2. Wait a moment
3. Change the same parcel's price from 1500 to 1200 (Reason: "Customer discount")
4. View price history
5. **Expected:** 
   - Two entries in the timeline
   - Most recent change appears first
   - Both changes show correct old→new prices
   - Both reasons are displayed
   - Both show your username

### 6. Database Verification Test

**Objective:** Verify data is correctly stored in database

**Steps:**
1. Make a price change
2. Check the database:
   ```bash
   php artisan tinker
   ```
   ```php
   $changes = App\Models\ParcelPriceChange::with('parcel', 'changedBy')->latest()->take(5)->get();
   foreach($changes as $change) {
       echo "Parcel: {$change->parcel->tracking_number}\n";
       echo "Changed by: {$change->changedBy->name}\n";
       echo "Old: {$change->old_price} -> New: {$change->new_price}\n";
       echo "Reason: {$change->reason}\n";
       echo "Date: {$change->created_at}\n\n";
   }
   ```
3. **Expected:** All data is correctly stored

### 7. UI Responsiveness Test

**Objective:** Verify loading states and user feedback

**Steps:**
1. Open network throttling in browser DevTools (simulate slow connection)
2. Make a price change
3. **Expected:** 
   - "Update Price" button shows loading spinner
   - Button is disabled during update
   - Success message appears after completion
4. Open price history dialog
5. **Expected:** 
   - Loading spinner shows while fetching
   - Timeline appears after data loads

### 8. Permission Test (if applicable)

**Objective:** Verify only authorized users can change prices

**Steps:**
1. If you have role-based permissions, test with different user roles
2. Try changing price with each role
3. **Expected:** Only authorized users can see/use the feature

### 9. Concurrent Changes Test

**Objective:** Test transaction safety

**Steps:**
1. Open the same parcel in two browser tabs
2. In tab 1, change price from 1000 to 1500
3. In tab 2, change price from (whatever it shows) to 2000
4. Both should complete successfully
5. View history
6. **Expected:** Both changes are recorded in correct order

### 10. API Direct Test

**Objective:** Test API endpoints directly

**Update Price:**
```bash
curl -X POST http://your-domain/whatsapp/parcels/1/update-price \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: your-csrf-token" \
  -d '{"new_price": 1500.00, "reason": "API test"}'
```

**Expected Response:**
```json
{
    "success": true,
    "message": "Price updated successfully.",
    "data": {
        "old_price": 1000.00,
        "new_price": 1500.00
    }
}
```

**Get History:**
```bash
curl http://your-domain/whatsapp/parcels/1/price-history
```

**Expected Response:**
```json
{
    "success": true,
    "data": [...]
}
```

## Common Issues and Solutions

### Issue 1: Dialog doesn't open
- **Solution:** Check browser console for errors
- Verify Vue components are loaded
- Check that `showPriceChangeDialog` ref is properly defined

### Issue 2: Price doesn't update
- **Solution:** 
  - Check Laravel logs: `tail -f storage/logs/laravel.log`
  - Verify CSRF token is valid
  - Check database connection

### Issue 3: History doesn't load
- **Solution:**
  - Check API endpoint returns data
  - Verify `fetchPriceHistory` method is called
  - Check for JavaScript errors in console

### Issue 4: Pencil icon doesn't appear
- **Solution:**
  - Verify `price_modified` flag is added in controller
  - Check that relationship exists: `$parcel->priceChanges()`
  - Clear cache and reload page

## Success Criteria

✅ All test scenarios pass without errors
✅ Visual indicators appear correctly
✅ Price changes are saved to database
✅ History displays all changes chronologically
✅ Validation prevents invalid inputs
✅ Loading states provide good UX
✅ No console errors during operation
✅ Page reloads show updated data

## Rollback Plan

If issues are found and need to be rolled back:

```bash
# Rollback the migration
php artisan migrate:rollback --step=1

# Or manually remove:
# - Database table: parcel_price_changes
# - Model: app/Models/ParcelPriceChange.php
# - Controller methods in WhatsAppController.php
# - Routes in web.php
# - Frontend components in Index.vue
```

## Performance Notes

- Price history queries are optimized with eager loading
- Indexes on `parcel_id` and `changed_by` ensure fast lookups
- Consider pagination if a parcel has many changes (currently loads all)

## Next Steps After Testing

1. Document any bugs found
2. Test with real production data (if applicable)
3. Train users on the new feature
4. Monitor database growth of `parcel_price_changes` table
5. Consider adding export functionality for audit reports