# Automatic Line Unassignment Cron Job

This system automatically unassigns lines from VPS that have been assigned for more than 1 day.

## What was implemented:

1. **Database Changes:**
   - Added `assigned_at` timestamp field to the `vps` table
   - Updated VPS model to include the new field

2. **Assignment Logic:**
   - Modified `VpsController::assignLineToVps()` to set `assigned_at` timestamp when assigning lines
   - Modified `VpsController::unassignLineFromVps()` to clear `assigned_at` timestamp when unassigning

3. **Command:**
   - Created `php artisan lines:unassign-old` command
   - Command finds VPS with lines assigned more than specified days ago (default: 1 day)
   - Unassigns those lines automatically
   - Logs all actions for monitoring

4. **Cron Job:**
   - Set up daily cron job that runs at midnight (00:00)
   - Logs output to `/var/log/unassign-lines.log`
   - Alternative hourly option available (commented out)

## Usage:

### Manual Command:
```bash
# Unassign lines older than 1 day (default)
php artisan lines:unassign-old

# Unassign lines older than 2 days
php artisan lines:unassign-old --days=2

# Check command help
php artisan lines:unassign-old --help
```

### Cron Job:
The cron job runs automatically every day at midnight. To modify the schedule:

```bash
# Edit crontab
crontab -e

# Current schedule: Daily at midnight
0 0 * * * cd /home/xserver-monitor && php artisan lines:unassign-old >> /var/log/unassign-lines.log 2>&1

# Alternative: Every hour (uncomment and comment the daily one)
# 0 * * * * cd /home/xserver-monitor && php artisan lines:unassign-old >> /var/log/unassign-lines.log 2>&1
```

### Monitoring:
- Check logs: `tail -f /var/log/unassign-lines.log`
- Laravel logs: `tail -f storage/logs/laravel.log`
- View cron job: `crontab -l`

## Files Modified:
- `database/migrations/2025_09_10_062802_add_assigned_at_to_vps_table.php`
- `app/Models/Vps.php`
- `app/Http/Controllers/VpsController.php`
- `app/Console/Commands/UnassignOldLines.php`

## Testing:
The system has been tested and verified to work correctly. It will:
1. Find VPS with lines assigned more than 1 day ago
2. Unassign those lines (clear linename, serverdomain, domains, assigned_at)
3. Log all actions for monitoring
4. Run automatically via cron job
