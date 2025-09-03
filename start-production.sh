#!/bin/bash

# Production-ready Laravel server startup script
# This script starts the server in the background and provides process management

SERVER_PID_FILE="laravel-server.pid"
LOG_FILE="laravel-server.log"

echo "Starting Laravel production server..."

# Check if server is already running
if [ -f "$SERVER_PID_FILE" ]; then
    PID=$(cat "$SERVER_PID_FILE")
    if ps -p $PID > /dev/null 2>&1; then
        echo "Server is already running with PID: $PID"
        echo "To stop it, run: kill $PID"
        exit 1
    else
        echo "Removing stale PID file..."
        rm -f "$SERVER_PID_FILE"
    fi
fi

# Start the server in the background
nohup php artisan serve --host=0.0.0.0 --port=8000 > "$LOG_FILE" 2>&1 &
SERVER_PID=$!

# Save the PID
echo $SERVER_PID > "$SERVER_PID_FILE"

echo "Laravel server started with PID: $SERVER_PID"
echo "Server is accessible at: http://YOUR_VPS_IP:8000"
echo "Log file: $LOG_FILE"
echo "PID file: $SERVER_PID_FILE"
echo ""
echo "To stop the server: ./stop-server.sh"
echo "To check status: ./status-server.sh" 