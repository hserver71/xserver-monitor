<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the monitoring system.
    |
    */

    'api_base_url' => env('MONITORING_API_BASE_URL', 'https://your-domain.com'),
    
    'ping_timeout' => env('MONITORING_PING_TIMEOUT', 1000), // milliseconds
    
    'log_retention_days' => env('MONITORING_LOG_RETENTION_DAYS', 30),
    
    'enable_uptime_check' => env('MONITORING_ENABLE_UPTIME_CHECK', true),
    
    'enable_admin_notes' => env('MONITORING_ENABLE_ADMIN_NOTES', true),
]; 