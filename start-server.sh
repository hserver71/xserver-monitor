#!/bin/bash

# Start Laravel server accessible from external connections
# This will bind to all network interfaces (0.0.0.0) on port 8000

echo "Starting Laravel server on 0.0.0.0:8000..."
echo "Your application will be accessible at: http://YOUR_VPS_IP:8000"
echo "Press Ctrl+C to stop the server"

php artisan serve --host=0.0.0.0 --port=8000 