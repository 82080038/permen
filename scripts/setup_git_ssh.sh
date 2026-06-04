#!/bin/bash

# Setup Git SSH Key for GitHub
# This script generates SSH key and provides instructions for adding to GitHub

set -e

echo "=========================================="
echo "Git SSH Key Setup for GitHub"
echo "=========================================="
echo ""

# Check if SSH key already exists
if [ -f ~/.ssh/id_ed25519 ]; then
    echo "SSH key already exists at ~/.ssh/id_ed25519"
    echo "Public key:"
    cat ~/.ssh/id_ed25519.pub
    echo ""
    echo "If you want to use existing key, skip to step 2"
    echo "If you want to generate new key, remove existing key first:"
    echo "  rm ~/.ssh/id_ed25519 ~/.ssh/id_ed25519.pub"
    echo ""
    read -p "Continue with existing key? (y/n): " continue_existing
    if [ "$continue_existing" != "y" ]; then
        echo "Exiting..."
        exit 0
    fi
else
    # Generate SSH key
    echo "Generating new SSH key..."
    read -p "Enter your email for GitHub: " github_email
    
    if [ -z "$github_email" ]; then
        github_email="user@$(hostname).local"
        echo "Using default email: $github_email"
    fi
    
    ssh-keygen -t ed25519 -C "$github_email" -f ~/.ssh/id_ed25519 -N ""
    echo "SSH key generated successfully!"
    echo ""
fi

# Add GitHub to known hosts
echo "Adding GitHub to known hosts..."
ssh-keyscan github.com >> ~/.ssh/known_hosts 2>/dev/null
echo "Done!"
echo ""

# Start SSH agent
echo "Starting SSH agent..."
eval "$(ssh-agent -s)"
ssh-add ~/.ssh/id_ed25519
echo "SSH key added to agent!"
echo ""

# Display public key
echo "=========================================="
echo "STEP 1: Add SSH Key to GitHub"
echo "=========================================="
echo ""
echo "Copy this public key:"
echo "----------------------------------------"
cat ~/.ssh/id_ed25519.pub
echo "----------------------------------------"
echo ""
echo "Then:"
echo "1. Go to: https://github.com/settings/keys"
echo "2. Click: 'New SSH key' or 'Add SSH key'"
echo "3. Title: $(hostname)"
echo "4. Key type: 'Authentication Key'"
echo "5. Paste the public key above"
echo "6. Click: 'Add SSH key'"
echo ""
read -p "Press Enter after you've added the SSH key to GitHub..."
echo ""

# Configure git remote to use SSH
echo "=========================================="
echo "STEP 2: Configure Git Remote"
echo "=========================================="
echo ""
current_dir=$(basename $(pwd))
echo "Current directory: $current_dir"
echo ""
read -p "Enter GitHub repository URL (e.g., https://github.com/username/repo.git): " repo_url

if [ -n "$repo_url" ]; then
    # Convert HTTPS to SSH
    ssh_url=$(echo "$repo_url" | sed 's|https://github.com/|git@github.com:|')
    git remote set-url origin "$ssh_url"
    echo "Git remote updated to use SSH: $ssh_url"
else
    echo "Skipping git remote configuration"
fi
echo ""

# Test connection
echo "=========================================="
echo "STEP 3: Test SSH Connection"
echo "=========================================="
echo ""
echo "Testing connection to GitHub..."
if ssh -T git@github.com 2>&1 | grep -q "successfully authenticated"; then
    echo "✓ SSH connection successful!"
else
    echo "⚠ SSH connection test failed, but this might be normal"
    echo "Try pushing to verify connection works"
fi
echo ""

# Show next steps
echo "=========================================="
echo "Setup Complete!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Verify git remote: git remote -v"
echo "2. Push to GitHub: git push origin main"
echo ""
echo "For other computers:"
echo "- Run this script on each computer"
echo "- Each computer will have its own SSH key"
echo "- Add each SSH key to GitHub account"
echo ""
