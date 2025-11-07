# Parcel Price Change Feature

This feature allows users to modify parcel prices and track all changes with full audit history.

## Overview

The price change feature provides:
- Ability to change the COD amount of parcels
- Full audit trail of all price modifications
- Visual indicators for parcels with modified prices
- Price change history with reasons and timestamps
- User tracking for who made each change

## Database Schema

### parcel_price_changes Table

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| parcel_id | bigint | Foreign key to parcels table |
| old_price | decimal(10,2) | Previous price before change |
| new_price | decimal(10,2) | New price after change |
| changed_by | bigint | Foreign key to users table (who made the change) |
| reason | text | Optional reason for the price change |
| created_at | timestamp | When the change was made |
| updated_at | timestamp | Last update timestamp |

## API Endpoints

### Update Parcel Price
```
POST /whatsapp/parcels/{parcel}/update-price
```

**Request Body:**
```json
{
    "new_price": 1500.00,
    "reason": "Customer negotiation"
}
```

**Response:**
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

### Get Price Change History
```
GET /whatsapp/parcels/{parcel}/price-history
```

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "old_price": 1000.00,
            "new_price": 1500.00,
            "reason": "Customer negotiation",
            "changed_by": "John Doe",
            "changed_at": "2025-11-07 23:30:45"
        }
    ]
}
```

## User Interface

### Price Display
- Parcels with modified prices show a pencil icon (✏️) next to the price
- Hover over the icon to see "Price has been modified" tooltip
- The price is displayed in the COD Amount column with "DZD" suffix

### Change Price Button
- Located in the Actions column of each parcel row
- Icon: Currency symbol (💲)
- Opens the "Change Parcel Price" dialog

### Price Change Dialog
The dialog includes:
1. **Tracking Number** (read-only) - Shows which parcel is being modified
2. **Current Price** (read-only) - Displays the current COD amount
3. **New Price** - Input field for entering the new price
4. **Reason** (optional) - Text area for explaining why the price is being changed
5. **View Price History** - Button to see all past changes

### Price History Dialog
Shows a timeline of all price changes including:
- Who made the change (user name)
- When it was changed (date and time)
- Old price → New price (with color-coded chips)
- Reason for the change (if provided)

## Models

### ParcelPriceChange Model
Located at: `app/Models/ParcelPriceChange.php`

**Relationships:**
- `parcel()` - Belongs to a Parcel
- `changedBy()` - Belongs to a User (who made the change)

**Attributes:**
- Automatically casts prices to decimal with 2 decimal places

### Parcel Model Updates
Added relationships:
- `priceChanges()` - Has many ParcelPriceChange records
- `latestPriceChange()` - Has one (most recent) ParcelPriceChange

## Usage Examples

### Changing a Price
1. Navigate to WhatsApp Management page
2. Find the parcel you want to modify
3. Click the currency icon (💲) in the Actions column
4. Enter the new price
5. Optionally add a reason for the change
6. Click "Update Price"
7. The parcel list will reload with the updated price

### Viewing Price History
1. Open the price change dialog for a parcel
2. Click "View Price History" button
3. See a timeline of all changes
4. Each change shows:
   - Date and time
   - User who made the change
   - Old and new prices
   - Reason (if any was provided)

## Security & Validation

- Only authenticated users can change prices
- New price must be a positive number (>= 0)
- Validates that new price is different from current price
- Uses database transactions to ensure data consistency
- Records user ID for audit purposes
- Uses CSRF token protection for all API calls

## Visual Indicators

### Modified Price Badge
Parcels that have had their price changed show a warning-colored pencil icon next to the price amount. This makes it easy to identify which parcels have been modified at a glance.

### Color Coding in History
- **Red chip**: Old price (before change)
- **Green chip**: New price (after change)
- Arrow icon between them to show the direction of change

## Migration

To set up the feature:
```bash
php artisan migrate
```

This runs the `2025_11_07_233045_create_parcel_price_changes_table` migration.

## Frontend Components

The feature is implemented in: `resources/js/Pages/WhatsApp/Index.vue`

**New reactive variables:**
- `showPriceChangeDialog` - Controls price change dialog visibility
- `showPriceHistoryDialog` - Controls history dialog visibility
- `newPrice` - Stores the new price being entered
- `priceChangeReason` - Stores the reason for the change
- `updatingPrice` - Loading state for price update
- `loadingPriceHistory` - Loading state for fetching history
- `priceHistory` - Array of price change records

**New methods:**
- `openPriceChangeDialog(parcel)` - Opens the price change dialog
- `closePriceChangeDialog()` - Closes and resets the dialog
- `updatePrice()` - Submits the price change to the API
- `viewPriceHistory()` - Opens history dialog and fetches data
- `fetchPriceHistory()` - Retrieves price history from API

## Notes

- Price changes are permanent and cannot be undone (but history is preserved)
- All changes are logged for audit purposes
- The reason field is optional but recommended for documentation
- Historical data is never deleted, ensuring complete audit trail
- The feature integrates seamlessly with existing WhatsApp management functionality