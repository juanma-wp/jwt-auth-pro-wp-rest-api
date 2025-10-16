#!/bin/bash

TARGET="vendor/wp-rest-auth/auth-toolkit"

if [ -L "$TARGET" ]; then
    echo "🔗 Found symlink at $TARGET"
    rm "$TARGET"
    echo "✅ Symlink removed"
elif [ -d "$TARGET" ]; then
    echo "📁 Found directory at $TARGET"
    rm -rf "$TARGET"
    echo "✅ Directory removed"
else
    echo "❌ $TARGET not found"
    exit 1
fi