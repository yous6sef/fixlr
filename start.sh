#!/bin/bash
set -e

cd /app/flix

# Install dependencies
npm install --omit=dev

# Build webpack assets
npm run build

# Start PHP server
php -S 0.0.0.0:${PORT:-8080}
