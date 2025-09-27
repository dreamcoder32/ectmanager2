# Automated Salary and Commission Processing

This document describes the automated salary and commission processing system implemented in the delivery management system.

## Overview

The system provides automated monthly processing of:
- **Salary Payments**: Monthly salary payments for all eligible users
- **Commission Payments**: Commission payments based on completed collections

## Commands

### Salary Processing Command

```bash
php artisan salary:process-monthly [options]
```

**Options:**
- `--month=N`: The month to process (1-12). Defaults to current month
- `--year=YYYY`: The year to process. Defaults to current year
- `--payment-date=YYYY-MM-DD`: The payment date. Defaults to today
- `--dry-run`: Run without making actual changes (for testing)

**Example Usage:**
```bash
# Process current month salaries
php artisan salary:process-monthly

# Process specific month with dry run
php artisan salary:process-monthly --month=8 --year=2024 --dry-run

# Process with specific payment date
php artisan salary:process-monthly --payment-date=2024-09-01
```

### Commission Processing Command

```bash
php artisan commission:process-monthly [options]
```

**Options:**
- `--month=N`: The month to process (1-12). Defaults to current month
- `--year=YYYY`: The year to process. Defaults to current year
- `--payment-date=YYYY-MM-DD`: The payment date. Defaults to today
- `--dry-run`: Run without making actual changes (for testing)

**Example Usage:**
```bash
# Process current month commissions
php artisan commission:process-monthly

# Process specific month with dry run
php artisan commission:process-monthly --month=8 --year=2024 --dry-run

# Process with specific payment date
php artisan commission:process-monthly --payment-date=2024-09-02
```

## Automated Scheduling

The system is configured to run automatically via Laravel's task scheduler:

### Schedule Configuration
- **Salary Processing**: Runs on the 1st of every month at 9:00 AM
- **Commission Processing**: Runs on the 2nd of every month at 10:00 AM

### Cron Job Setup

To enable automated processing, add this cron job to your server:

```bash
# Edit crontab
crontab -e

# Add this line
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

Replace `/path/to/your/project` with the actual path to your Laravel application.

## Processing Logic

### Salary Processing

1. **Eligibility Check**: Finds all users with:
   - `salary_amount` is not null and > 0
   - `salary_is_active` is true

2. **Duplicate Prevention**: Checks if a salary payment already exists for the user and month/year

3. **Payment Creation**: Creates:
   - `SalaryPayment` record with status 'pending'
   - Corresponding `Expense` record for financial tracking

4. **Error Handling**: Skips users with existing payments and logs all operations

### Commission Processing

1. **Collection Eligibility**: Finds collections that:
   - Have status 'completed'
   - Have an assigned agent (`agent_id` is not null)
   - Were completed in the specified month/year
   - Don't already have commission payments

2. **Agent Eligibility**: Checks if the agent has:
   - `commission_is_active` is true
   - `commission_rate` > 0

3. **Commission Calculation**: Uses the agent's `calculateCommission()` method based on:
   - Collection total amount
   - Agent's commission type (percentage/fixed)
   - Agent's commission rate

4. **Payment Creation**: Creates:
   - `CommissionPayment` record with status 'pending'
   - Corresponding `Expense` record for financial tracking

## Features

### Safety Features
- **Dry Run Mode**: Test processing without making changes
- **Duplicate Prevention**: Prevents creating duplicate payments
- **Transaction Safety**: Uses database transactions for data integrity
- **Error Logging**: Comprehensive logging of all operations
- **Email Notifications**: Sends email alerts on processing failures

### Monitoring and Reporting
- **Detailed Output**: Shows processing results with tables
- **Skip Tracking**: Reports why certain records were skipped
- **Summary Statistics**: Provides counts of created/skipped records
- **Log Integration**: All operations are logged for audit trails

### Scheduling Features
- **No Overlapping**: Prevents multiple instances from running simultaneously
- **Single Server**: Ensures commands run on only one server in multi-server setups
- **Failure Notifications**: Sends email alerts when scheduled jobs fail

## Configuration

### Email Notifications
Update the email address in `routes/console.php` for failure notifications:

```php
->emailOutputOnFailure('your-admin@email.com');
```

### System User
The automated processing uses user ID 1 as the system user. Ensure this user exists in your database.

## Troubleshooting

### Common Issues

1. **No Users/Collections Found**
   - Check that users have proper salary/commission configuration
   - Verify collections have assigned agents and are completed

2. **Permission Errors**
   - Ensure the web server has write permissions to storage/logs
   - Check database connection and permissions

3. **Scheduling Not Working**
   - Verify cron job is properly configured
   - Check that `php artisan schedule:list` shows your commands
   - Review Laravel logs for scheduler errors

### Testing

Always test with `--dry-run` first:

```bash
# Test salary processing
php artisan salary:process-monthly --dry-run

# Test commission processing
php artisan commission:process-monthly --dry-run
```

### Logs

Check application logs for detailed information:
- Laravel logs: `storage/logs/laravel.log`
- Scheduler logs: Available via `php artisan schedule:list`

## Security Considerations

- Commands are designed to be run by system administrators only
- All database operations use transactions for data integrity
- Sensitive operations are logged for audit purposes
- Email notifications help monitor system health

## Integration

The automated processing integrates with:
- **Financial Dashboard**: Processed payments appear in financial metrics
- **Expense Management**: Creates corresponding expense records
- **User Management**: Respects user salary/commission configuration
- **Collection System**: Processes commissions for completed collections