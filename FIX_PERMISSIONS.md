# Fixing File Permissions Issue

If you're getting "Could not write to admin-config.php", here's how to fix it:

## Option 1: Use the Hash Display Script (Easiest)

1. Visit: `https://yourdomain.com/get-password-hash.php`
2. Copy the generated hash
3. Manually update `admin-config.php` with the hash
4. No permissions needed!

## Option 2: Fix File Permissions

### Via SSH (Command Line):

```bash
# Make file writable by owner and group
chmod 664 admin-config.php

# Or make it writable by everyone (less secure, but works)
chmod 666 admin-config.php
```

### Via FTP Client:

1. Connect to your server via FTP
2. Navigate to the file: `admin-config.php`
3. Right-click the file ? **Properties** (or **File Permissions**)
4. Set permissions to: **664** (or **666** if needed)
5. Click **OK**

### Via cPanel File Manager:

1. Log into cPanel
2. Go to **File Manager**
3. Navigate to your directory
4. Right-click `admin-config.php` ? **Change Permissions**
5. Set to: **664** (or **666**)
6. Click **Change Permissions**

## Option 3: Manual Update (No Permissions Needed)

1. Visit: `https://yourdomain.com/get-password-hash.php` or `setup-admin-password.php`
2. Copy the generated hash
3. Download `admin-config.php` to your computer
4. Open it in a text editor
5. Find this line:
   ```php
   'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   ```
6. Replace the hash (everything between the quotes) with the new hash
7. Save the file
8. Upload it back to your server

## Understanding File Permissions

- **644** = Owner can read/write, others can only read (default)
- **664** = Owner and group can read/write, others can only read (recommended)
- **666** = Everyone can read/write (less secure, but works if web server user is different)

The web server user (often `www-data`, `apache`, or `nobody`) needs write permissions to auto-update the file.
