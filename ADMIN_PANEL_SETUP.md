# Senior Floors Admin Panel - Setup Guide

## ?? What You Got

A complete modular admin panel system that can be extended with multiple features!

## ? Features

- ?? **Dashboard** - Overview statistics and recent leads
- ?? **CRM Module** - Full lead management system (search, filter, export)
- ?? **Settings** - Admin panel configuration
- ?? **Password Protected** - Secure access
- ?? **Responsive Design** - Works on all devices
- ?? **Modular System** - Easy to add new features

## ?? Quick Setup

### Step 1: Upload Files

Upload these files/folders to your server:
- `system.php` (main admin panel)
- `admin-modules/` folder (contains all modules)
  - `dashboard.php`
  - `crm.php`
  - `settings.php`

### Step 2: Change Password

**IMPORTANT:** Open `system.php` and change the password on line 12:

```php
$ADMIN_PASSWORD = 'senior-floors-2024'; // CHANGE THIS PASSWORD!
```

Change `'senior-floors-2024'` to your own secure password.

### Step 3: Access Admin Panel

Visit: `https://yourdomain.com/system` or `https://yourdomain.com/system.php`

Both URLs work! Enter your password and you're in!

## ?? File Structure

```
senior-floors-landing/
├── system.php               (Main admin panel)
├── admin-modules/
│   ├── dashboard.php        (Dashboard module)
│   ├── crm.php              (CRM/Leads module)
│   └── settings.php         (Settings module)
└── leads.csv                (Auto-generated leads file)
```

## ?? Modules Overview

### ?? Dashboard Module
- Overview statistics
- Recent leads preview
- Quick actions (call, email, view all)

### ?? CRM Module
- View all leads
- Search functionality
- Filter by form type and date range
- Export to CSV
- Pagination

### ?? Settings Module
- Admin panel configuration info
- System information
- Security recommendations
- Instructions for adding new modules

## ?? Adding New Modules

To add a new module:

1. **Create the module file:**
   Create a new PHP file in `admin-modules/` directory, e.g., `admin-modules/reports.php`

2. **Add to system.php:**
   Edit `system.php` and add your module to the `$modules` array (around line 119):

```php
$modules = [
    'dashboard' => [
        'name' => 'Dashboard',
        'icon' => '??',
        'file' => 'admin-modules/dashboard.php',
        'default' => true
    ],
    'crm' => [
        'name' => 'CRM - Leads',
        'icon' => '??',
        'file' => 'admin-modules/crm.php'
    ],
    'your-module' => [  // Add this
        'name' => 'Your Module Name',
        'icon' => '??',
        'file' => 'admin-modules/your-module.php'
    ],
    'settings' => [
        'name' => 'Settings',
        'icon' => '??',
        'file' => 'admin-modules/settings.php'
    ]
];
```

3. **Create the module content:**
   Your module file should output HTML directly (no `<html>`, `<head>`, `<body>` tags - those are handled by `system.php`)

Example module structure:
```php
<?php
/**
 * Your Module Name
 */
?>
<style>
    /* Your CSS here */
</style>

<h1>Your Module Title</h1>
<!-- Your content here -->
```

## ?? Customization

### Change Admin Panel Title

Edit `system.php` line 13:
```php
$ADMIN_TITLE = 'Your Custom Title';
```

### Change Colors

The admin panel uses a purple gradient theme. To change:
- Search for `#667eea` and `#764ba2` in `system.php`
- Replace with your brand colors

### Change Leads Per Page

Edit `admin-modules/crm.php` line 6:
```php
$LEADS_PER_PAGE = 25; // Change this number
```

## ?? Module Features

### Dashboard Module
- Total leads count
- Today's leads
- Weekly/monthly statistics
- Recent leads preview
- Quick action buttons

### CRM Module
- Full lead management
- Search across all fields
- Filter by form type
- Date range filtering
- CSV export
- Pagination

### Settings Module
- Configuration information
- System status
- Security tips
- Module development guide

## ?? Security

- Password-protected access
- Session-based authentication
- Secure logout functionality
- Change default password immediately
- Use strong passwords

## ?? Responsive Design

The admin panel is fully responsive:
- Desktop: Sidebar navigation
- Tablet: Sidebar navigation
- Mobile: Horizontal scrolling navigation

## ? Checklist

- [ ] Upload `system.php` to server
- [ ] Upload `admin-modules/` folder to server
- [ ] Change password in `system.php` (line 12)
- [ ] Test login with new password
- [ ] Verify dashboard shows statistics
- [ ] Test CRM module functionality
- [ ] Test settings module
- [ ] Bookmark admin panel URL

## ?? Troubleshooting

### "Module Not Found" Error
- Check that module file exists in `admin-modules/` folder
- Verify file path in `$modules` array matches actual file name
- Check file permissions

### "Invalid password"
- Make sure you changed the password in `system.php`
- Check for typos
- Clear browser cache

### Leads not showing
- Verify `leads.csv` exists
- Check file permissions
- Make sure forms are submitting successfully

## ?? Next Steps

1. **Set up the admin panel** (upload files and change password)
2. **Explore the modules** (dashboard, CRM, settings)
3. **Add custom modules** (if needed)
4. **Bookmark for easy access**

## ?? Module Ideas

Here are some ideas for additional modules you could add:

- **?? Reports** - Analytics and reporting
- **?? Email Templates** - Manage email templates
- **?? Users** - User management (if you add multi-user support)
- **?? Notes** - Add notes to leads
- **?? Notifications** - Notification settings
- **?? Analytics** - Detailed analytics dashboard
- **?? Customization** - Theme and branding settings

Enjoy your new admin panel system! ??
