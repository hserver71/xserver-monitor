# Cron Job Setup Guide for Laravel Project

## Overview
This guide explains how to set up cron jobs in your Laravel project using Laravel's built-in Task Scheduler.

## Method 1: Laravel Task Scheduler (Recommended)

### 1. Schedule Commands in Kernel.php
Commands are already scheduled in `app/Console/Kernel.php`. The monitoring command is set to run every 5 minutes.

### 2. Set Up Cron Entry on Server
Add this single cron entry to your server's crontab:

```bash
# Open crontab for editing
crontab -e

# Add this line to run Laravel scheduler every minute
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

**For Windows Server:**
- Use Windows Task Scheduler
- Create a batch file with: `php artisan schedule:run`
- Set it to run every minute

### 3. Test the Setup
```bash
# Test if the command works manually
php artisan monitoring:run

# Check scheduled tasks
php artisan schedule:list

# Run all due scheduled tasks
php artisan schedule:run
```

## Method 2: Direct Cron Commands

### Option A: Direct Command Execution
```bash
# Run every 5 minutes
*/5 * * * * cd /path/to/your/project && php artisan monitoring:run >> /var/log/monitoring.log 2>&1
```

### Option B: With Logging
```bash
# Run every 5 minutes with detailed logging
*/5 * * * * cd /path/to/your/project && php artisan monitoring:run >> /var/log/monitoring.log 2>&1
```

## Available Scheduling Methods

### Time-based Scheduling
```php
$schedule->command('monitoring:run')->everyMinute();
$schedule->command('monitoring:run')->everyTwoMinutes();
$schedule->command('monitoring:run')->everyFiveMinutes();
$schedule->command('monitoring:run')->everyTenMinutes();
$schedule->command('monitoring:run')->everyFifteenMinutes();
$schedule->command('monitoring:run')->everyThirtyMinutes();
$schedule->command('monitoring:run')->hourly();
$schedule->command('monitoring:run')->daily();
$schedule->command('monitoring:run')->weekly();
$schedule->command('monitoring:run')->monthly();
```

### Custom Time Scheduling
```php
$schedule->command('monitoring:run')->dailyAt('13:00');        // 1 PM daily
$schedule->command('monitoring:run')->weeklyOn(1, '8:00');    // Monday 8 AM
$schedule->command('monitoring:run')->monthlyOn(4, '15:00');  // 4th of month at 3 PM
$schedule->command('monitoring:run')->quarterly();             // Every 3 months
$schedule->command('monitoring:run')->yearly();                // Every year
```

### Custom Cron Expressions
```php
$schedule->command('monitoring:run')->cron('0 */2 * * *');    // Every 2 hours
$schedule->command('monitoring:run')->cron('0 9-17 * * *');   // Every hour 9 AM to 5 PM
$schedule->command('monitoring:run')->cron('0 9 * * 1-5');    // 9 AM on weekdays
```

## Advanced Scheduling Features

### Conditional Execution
```php
$schedule->command('monitoring:run')
    ->everyFiveMinutes()
    ->when(function () {
        return app()->environment('production');
    });
```

### Overlapping Prevention
```php
$schedule->command('monitoring:run')
    ->everyFiveMinutes()
    ->withoutOverlapping();
```

### Background Execution
```php
$schedule->command('monitoring:run')
    ->everyFiveMinutes()
    ->runInBackground();
```

### Error Handling
```php
$schedule->command('monitoring:run')
    ->everyFiveMinutes()
    ->onFailure(function () {
        // Handle failure
        Log::error('Monitoring job failed');
    });
```

## Monitoring and Debugging

### Check Scheduled Tasks
```bash
php artisan schedule:list
```

### Test Schedule
```bash
php artisan schedule:test
```

### View Schedule in Browser (Development)
```bash
php artisan schedule:work
```

### Logs
Check these locations for logs:
- `storage/logs/laravel.log`
- Custom log files specified in cron commands
- System cron logs (`/var/log/cron` on Linux)

## Troubleshooting

### Common Issues
1. **Permission denied**: Ensure proper file permissions
2. **Path issues**: Use absolute paths in cron
3. **Environment variables**: Load `.env` file in cron
4. **PHP path**: Use full path to PHP executable

### Debug Commands
```bash
# Check if artisan is accessible
php artisan --version

# Test command manually
php artisan monitoring:run

# Check Laravel logs
tail -f storage/logs/laravel.log

# Check cron logs
tail -f /var/log/cron
```

## Production Considerations

### Security
- Use dedicated user for cron jobs
- Restrict file permissions
- Log all activities

### Performance
- Monitor resource usage
- Use background jobs for long-running tasks
- Implement job queues for heavy operations

### Monitoring
- Set up alerts for failed jobs
- Monitor execution times
- Track success/failure rates

## Example Production Setup

### 1. Create Cron User
```bash
sudo adduser cronuser
sudo usermod -aG www-data cronuser
```

### 2. Set Proper Permissions
```bash
sudo chown -R cronuser:www-data /path/to/your/project
sudo chmod -R 755 /path/to/your/project
```

### 3. Add to Crontab
```bash
sudo crontab -u cronuser -e

# Add this line:
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

### 4. Test and Monitor
```bash
# Check if cron is running
sudo systemctl status cron

# Monitor cron logs
sudo tail -f /var/log/cron
```

This setup ensures your monitoring system runs automatically every 5 minutes, providing continuous monitoring of your VPS instances. 