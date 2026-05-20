#!/bin/bash
set -e

echo "🚀 Starting Flix Marketplace deployment..."

cd /app/flix

echo "📦 Installing npm dependencies..."
npm install --legacy-peer-deps

echo "✅ Dependencies installed"

# Try to build if webpack exists, but don't fail if it doesn't
if [ -f "package.json" ]; then
    if grep -q '"webpack"' package.json; then
        echo "🔨 Building webpack assets..."
        npx webpack --mode production || echo "⚠️ Webpack build skipped (optional)"
    fi
fi

echo "🌐 Starting PHP server on port ${PORT:-8080}..."
php -S 0.0.0.0:${PORT:-8080}
