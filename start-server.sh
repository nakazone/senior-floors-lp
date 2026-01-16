#!/bin/bash
# Simple bash script to start a local server
# Usage: ./start-server.sh

PORT=8000

echo "=========================================="
echo "Senior Floors Landing Page - Local Server"
echo "=========================================="
echo ""

# Check if Python 3 is available
if command -v python3 &> /dev/null; then
    echo "Starting server with Python 3..."
    echo "Server will be available at: http://localhost:$PORT"
    echo "Press Ctrl+C to stop the server"
    echo ""
    python3 -m http.server $PORT
# Check if Python is available
elif command -v python &> /dev/null; then
    echo "Starting server with Python..."
    echo "Server will be available at: http://localhost:$PORT"
    echo "Press Ctrl+C to stop the server"
    echo ""
    python -m http.server $PORT
# Check if Node.js is available
elif command -v npx &> /dev/null; then
    echo "Starting server with Node.js http-server..."
    echo "Server will be available at: http://localhost:$PORT"
    echo "Press Ctrl+C to stop the server"
    echo ""
    npx http-server -p $PORT -o
else
    echo "Error: No suitable server found."
    echo "Please install Python 3 or Node.js to run a local server."
    echo ""
    echo "Alternatively, you can open index.html directly in your browser,"
    echo "but some features may not work due to CORS restrictions."
    exit 1
fi
