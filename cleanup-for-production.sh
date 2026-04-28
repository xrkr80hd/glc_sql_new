#!/bin/bash
# Script to remove unnecessary files before production deployment
# Run this from the project root directory

echo "🗑️  Cleaning up unnecessary files for production deployment..."

# Remove legacy Node.js backend
if [ -d "glc_backend" ]; then
    echo "Removing glc_backend/ (legacy Node.js code)..."
    rm -rf glc_backend/
fi

# Remove backup files
if [ -f "database/setup.sql.old" ]; then
    echo "Removing database/setup.sql.old..."
    rm database/setup.sql.old
fi

if [ -f "sql_backup_golibert2_liberty_church_08-10-2025_12_44.sql" ]; then
    echo "Removing SQL backup file..."
    rm sql_backup_golibert2_liberty_church_08-10-2025_12_44.sql
fi

# Remove testing files
if [ -f "test-apis.php" ]; then
    echo "Removing test-apis.php..."
    rm test-apis.php
fi

if [ -f "test-dashboard.php" ]; then
    echo "Removing test-dashboard.php..."
    rm test-dashboard.php
fi

# Remove deployment notes (optional - keep if you want them for reference)
if [ -f "SCHEMA_UPDATE.md" ]; then
    echo "Removing SCHEMA_UPDATE.md..."
    rm SCHEMA_UPDATE.md
fi

# Remove git directory if deploying via FTP (OPTIONAL - uncomment if needed)
# echo "Removing .git directory..."
# rm -rf .git/

# Remove VS Code directory if it exists
if [ -d ".vscode" ]; then
    echo "Removing .vscode/..."
    rm -rf .vscode/
fi

# Remove node_modules if it exists (you don't use Node.js)
if [ -d "node_modules" ]; then
    echo "Removing node_modules/..."
    rm -rf node_modules/
fi

# Remove package files if they exist
if [ -f "package.json" ]; then
    echo "Removing package.json..."
    rm package.json
fi

if [ -f "package-lock.json" ]; then
    echo "Removing package-lock.json..."
    rm package-lock.json
fi

echo ""
echo "✅ Cleanup complete!"
echo ""
echo "Your site is now ready for production deployment."
echo "Remember to:"
echo "  1. Update php/config.php with production database credentials"
echo "  2. Verify .env file has correct YouTube API keys"
echo "  3. Set uploads/ directory to writable (chmod 755)"
echo "  4. Test all pages after deployment"
echo ""
