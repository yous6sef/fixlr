#!/bin/bash
# Fix Railway deployment - Remove node_modules from Git tracking

echo "🔧 Fixing Railway deployment issue..."
echo ""
echo "Step 1: Remove node_modules from Git tracking"
git rm -r --cached flix/node_modules

echo ""
echo "Step 2: Commit the changes"
git commit -m "chore: remove committed node_modules from tracking

The flix/node_modules directory was committed to Git despite being in .gitignore.
This caused Railway builds to fail due to disk space exhaustion when cloning
thousands of dependency files.

After this commit, node_modules will be ignored as per .gitignore."

echo ""
echo "Step 3: Push to GitHub"
git push origin main

echo ""
echo "✅ Done! Railway will now deploy successfully."
echo "The next deployment will have a much smaller snapshot."
