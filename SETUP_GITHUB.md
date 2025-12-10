# GitHub Repository Setup Instructions

## Step 1: Create Repository on GitHub

1. Go to https://github.com/new
2. Repository name: `yadmoshe` (or your preferred name)
3. Description: "SMS Campaign Management System with MMS support"
4. Choose **Public** or **Private**
5. **DO NOT** initialize with README, .gitignore, or license (we already have these)
6. Click "Create repository"

## Step 2: Add Remote and Push

After creating the repository, GitHub will show you commands. Use these commands:

```bash
# Add the remote (replace YOUR_USERNAME with your GitHub username)
git remote add origin https://github.com/YOUR_USERNAME/yadmoshe.git

# Rename branch to main (if needed)
git branch -M main

# Push to GitHub
git push -u origin main
```

## Alternative: Using SSH (if you have SSH keys set up)

```bash
git remote add origin git@github.com:YOUR_USERNAME/yadmoshe.git
git branch -M main
git push -u origin main
```

## What's Included

✅ All source code files
✅ README.md with full documentation
✅ .gitignore (excludes sensitive files, logs, uploads, vendor)
✅ config.example.php (template for configuration)
✅ Composer files

## What's Excluded (for security)

❌ config.php (contains Twilio credentials)
❌ logs/ directory
❌ temp/ directory
❌ uploads/ directory
❌ vendor/ directory (install via `composer install`)







