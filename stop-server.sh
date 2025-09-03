#!/bin/bash

# Stop Laravel server script

SERVER_PID_FILE="laravel-server.pid"

if [ ! -f "$SERVER_PID_FILE" ]; then
    echo "No PID file found. Server may not be running."
    exit 1
fi

PID=$(cat "$SERVER_PID_FILE")

if ! ps -p $PID > /dev/null 2>&1; then
    echo "Server process not found. Removing stale PID file."
    rm -f "$SERVER_PID_FILE"
    exit 1
fi

echo "Stopping Laravel server (PID: $PID)..."
kill $PID

# Wait a bit for graceful shutdown
sleep 2

# Force kill if still running
if ps -p $PID > /dev/null 2>&1; then
    echo "Force killing server process..."
    kill -9 $PID
fi

# Remove PID file
rm -f "$SERVER_PID_FILE"
echo "Server stopped successfully." 