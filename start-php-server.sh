#!/bin/bash
# Start PHP built-in server for testing PHP forms
# Usage: ./start-php-server.sh

PORT=8000

echo "=========================================="
echo "Senior Floors - PHP Test Server"
echo "=========================================="
echo ""
echo "Starting PHP server on port $PORT..."
echo "Server will be available at: http://localhost:$PORT"
echo ""
echo "To test the form, visit: http://localhost:$PORT/test-form.html"
echo ""
echo "Press Ctrl+C to stop the server"
echo "=========================================="
echo ""

# Check if PHP is available
if command -v php &> /dev/null; then
    php -S localhost:$PORT
else
    echo "Error: PHP is not installed or not in PATH."
    echo "Please install PHP to test PHP forms locally."
    echo ""
    echo "On macOS, you can install PHP via Homebrew:"
    echo "  brew install php"
    exit 1
fi
