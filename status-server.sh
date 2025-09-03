#!/bin/bash

# Check Laravel server status

SERVER_PID_FILE="laravel-server.pid"
LOG_FILE="laravel-server.log"

echo "=== Laravel Server Status ==="

if [ ! -f "$SERVER_PID_FILE" ]; then
    echo "Status: Not running (no PID file)"
    exit 0
fi

PID=$(cat "$SERVER_PID_FILE")

if ps -p $PID > /dev/null 2>&1; then
    echo "Status: Running"
    echo "PID: $PID"
    echo "Port: 8000"
    echo "Host: 0.0.0.0 (accessible from external connections)"
    echo ""
    echo "Process details:"
    ps -p $PID -o pid,ppid,cmd,etime
    echo ""
    echo "Recent log entries:"
    if [ -f "$LOG_FILE" ]; then
        tail -10 "$LOG_FILE"
    else
        echo "No log file found."
    fi
else
    echo "Status: Not running (stale PID file)"
    echo "Removing stale PID file..."
    rm -f "$SERVER_PID_FILE"
fi 