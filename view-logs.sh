#!/bin/bash

# Laravel Log Viewer Script
# Provides easy access to different types of logs

LOG_DIR="storage/logs"
SERVER_LOG="laravel-server.log"
LARAVEL_LOG="$LOG_DIR/laravel.log"

echo "=== Laravel Log Viewer ==="
echo "1. View Laravel application logs"
echo "2. View server logs"
echo "3. View both logs simultaneously"
echo "4. Search logs for specific content"
echo "5. View log file sizes"
echo "6. Clear old logs"
echo "7. Exit"
echo ""

read -p "Choose an option (1-7): " choice

case $choice in
    1)
        echo "=== Laravel Application Logs ==="
        if [ -f "$LARAVEL_LOG" ]; then
            echo "File: $LARAVEL_LOG"
            echo "Size: $(du -h "$LARAVEL_LOG" | cut -f1)"
            echo "Last 20 lines:"
            echo "----------------------------------------"
            tail -20 "$LARAVEL_LOG"
            echo ""
            echo "Press Enter to continue following logs, or Ctrl+C to exit..."
            read
            tail -f "$LARAVEL_LOG"
        else
            echo "Laravel log file not found: $LARAVEL_LOG"
        fi
        ;;
    2)
        echo "=== Server Logs ==="
        if [ -f "$SERVER_LOG" ]; then
            echo "File: $SERVER_LOG"
            echo "Size: $(du -h "$SERVER_LOG" | cut -f1)"
            echo "Last 20 lines:"
            echo "----------------------------------------"
            tail -20 "$SERVER_LOG"
            echo ""
            echo "Press Enter to continue following logs, or Ctrl+C to exit..."
            read
            tail -f "$SERVER_LOG"
        else
            echo "Server log file not found: $SERVER_LOG"
        fi
        ;;
    3)
        echo "=== Both Logs (Laravel + Server) ==="
        echo "Following both log files simultaneously..."
        echo "Press Ctrl+C to exit..."
        tail -f "$LARAVEL_LOG" "$SERVER_LOG"
        ;;
    4)
        echo "=== Search Logs ==="
        read -p "Enter search term: " search_term
        echo "Searching for: '$search_term'"
        echo ""
        
        echo "Results in Laravel logs:"
        echo "----------------------------------------"
        if [ -f "$LARAVEL_LOG" ]; then
            grep -i "$search_term" "$LARAVEL_LOG" | tail -20
        else
            echo "Laravel log file not found"
        fi
        
        echo ""
        echo "Results in server logs:"
        echo "----------------------------------------"
        if [ -f "$SERVER_LOG" ]; then
            grep -i "$search_term" "$SERVER_LOG" | tail -20
        else
            echo "Server log file not found"
        fi
        ;;
    5)
        echo "=== Log File Sizes ==="
        echo "Laravel logs directory:"
        if [ -d "$LOG_DIR" ]; then
            ls -lah "$LOG_DIR"
        else
            echo "Log directory not found: $LOG_DIR"
        fi
        
        echo ""
        echo "Server logs:"
        if [ -f "$SERVER_LOG" ]; then
            ls -lah "$SERVER_LOG"
        else
            echo "Server log file not found: $SERVER_LOG"
        fi
        ;;
    6)
        echo "=== Clear Old Logs ==="
        read -p "Are you sure you want to clear old logs? (y/N): " confirm
        if [[ $confirm =~ ^[Yy]$ ]]; then
            echo "Clearing old logs..."
            if [ -f "$LARAVEL_LOG" ]; then
                > "$LARAVEL_LOG"
                echo "Cleared Laravel logs"
            fi
            if [ -f "$SERVER_LOG" ]; then
                > "$SERVER_LOG"
                echo "Cleared server logs"
            fi
        else
            echo "Operation cancelled."
        fi
        ;;
    7)
        echo "Exiting..."
        exit 0
        ;;
    *)
        echo "Invalid option. Please choose 1-7."
        ;;
esac 