# Senior Floors CRM - Setup Guide

## ?? What You Got

A complete CRM (Customer Relationship Management) system to view and manage all leads from your landing pages!

## ? Features

- ?? **Dashboard Statistics** - See total leads, today's leads, weekly stats
- ?? **Search & Filter** - Search by name, email, phone, zipcode, or message
- ?? **Date Range Filter** - Filter leads by date range
- ?? **Form Type Filter** - Filter by Hero Form or Contact Form
- ?? **Export to CSV** - Download filtered leads as CSV
- ?? **Responsive Design** - Works on desktop, tablet, and mobile
- ?? **Password Protected** - Secure access to your leads
- ?? **Pagination** - View leads in pages (25 per page)

## ?? Quick Setup

### Step 1: Upload the File

Upload `crm.php` to your server (same directory as your landing page files).

### Step 2: Change the Password

**IMPORTANT:** Open `crm.php` and change the password on line 11:

```php
$CRM_PASSWORD = 'senior-floors-2024'; // CHANGE THIS PASSWORD!
```

Change `'senior-floors-2024'` to your own secure password.

### Step 3: Access Your CRM

Visit: `https://yourdomain.com/crm.php`

Enter your password and you're in!

## ?? How to Use

### Viewing Leads

- All leads are displayed in a table
- Newest leads appear first
- Click on phone numbers to call
- Click on emails to send an email

### Searching

- Use the search box to find leads by:
  - Name
  - Email
  - Phone number
  - Zip code
  - Message content

### Filtering

- **Form Type:** Filter by Hero Form or Contact Form
- **Date Range:** Select start and end dates
- Click "Apply Filters" to filter results
- Click "Reset" to clear all filters

### Exporting

- Click "Export CSV" button to download all filtered leads
- The CSV file will include all columns from your leads

### Statistics Dashboard

The dashboard shows:
- **Total Leads** - All-time lead count
- **Today** - Leads submitted today
- **Last 7 Days** - Leads from the past week
- **Hero Form** - Count of hero form submissions
- **Contact Form** - Count of contact form submissions
- **Filtered Results** - Current filtered lead count

## ?? Security

- The CRM is password protected
- Change the default password immediately
- Use a strong password (mix of letters, numbers, symbols)
- Don't share the password publicly
- Logout when done using the CRM

## ?? Mobile Friendly

The CRM is fully responsive and works great on:
- Desktop computers
- Tablets
- Mobile phones

## ?? Features Overview

| Feature | Description |
|---------|-------------|
| **Search** | Find leads instantly by any field |
| **Filter** | Narrow down leads by form type or date |
| **Export** | Download leads as CSV for Excel/Google Sheets |
| **Statistics** | See key metrics at a glance |
| **Pagination** | Navigate through large lists easily |
| **Click-to-Call** | Call leads directly from the CRM |
| **Click-to-Email** | Email leads directly from the CRM |

## ?? Customization

### Change Leads Per Page

Edit line 12 in `crm.php`:

```php
$LEADS_PER_PAGE = 25; // Change this number
```

### Change Colors

The CRM uses a purple gradient theme. To change colors, edit the CSS in `crm.php`:
- Search for `#667eea` and `#764ba2` (purple gradient)
- Replace with your brand colors

## ?? Notes

- Leads are read from `leads.csv` file
- The CSV file is automatically created when forms are submitted
- All leads are saved automatically (no manual entry needed)
- The CRM is read-only (doesn't modify your leads)

## ? Checklist

- [ ] Upload `crm.php` to your server
- [ ] Change the password in `crm.php` (line 11)
- [ ] Test login with new password
- [ ] Verify leads are displaying correctly
- [ ] Test search functionality
- [ ] Test filter functionality
- [ ] Test export functionality
- [ ] Bookmark the CRM URL for easy access

## ?? Troubleshooting

### "No leads found"
- Check that `leads.csv` exists in the same directory
- Verify forms are submitting successfully
- Check file permissions (should be readable)

### "Invalid password"
- Make sure you changed the password in `crm.php`
- Check for typos in the password
- Clear browser cache and try again

### Leads not showing
- Verify `leads.csv` file exists
- Check file permissions
- Make sure CSV format is correct (Date,Form,Name,Phone,Email,ZipCode,Message)

## ?? Next Steps

1. **Set up the CRM** (upload and change password)
2. **Test it out** (login and explore)
3. **Bookmark it** (for easy access)
4. **Share with your team** (if needed, share the password securely)

Enjoy your new CRM system! ??
