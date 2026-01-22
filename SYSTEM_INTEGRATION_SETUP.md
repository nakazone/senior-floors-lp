# System.php Integration Setup

## File Structure on Hostinger
```
public_html/
├── system.php          (Admin panel)
└── lp/
    └── send-lead.php   (Form handler)
```

## Quick Setup

### Option 1: Manual URL Configuration (Recommended)

1. Open `send-lead.php` in your `lp` folder
2. Find line 88 (around there) where it says:
   ```php
   define('SYSTEM_API_URL', ''); // Set your full URL here if needed
   ```
3. Replace with your actual domain:
   ```php
   define('SYSTEM_API_URL', 'https://yourdomain.com/system.php?api=receive-lead');
   ```
   Replace `yourdomain.com` with your actual domain name.

4. Save and upload the file

### Option 2: Auto-Detection (Already Configured)

The code will try to auto-detect the path. If it doesn't work, use Option 1.

## Testing

1. Submit a test form on your landing page
2. Check the log file: `lp/system-integration.log`
3. Look for:
   - ✅ "Lead sent to system.php API successfully" = Working!
   - ⚠️ "System API failed" = Check the URL in the log

## Troubleshooting

### Check the Log File
Open `lp/system-integration.log` and look for:
- The URL being attempted
- Any error messages
- HTTP response codes

### Common Issues

1. **404 Error**: URL is wrong
   - Solution: Use Option 1 (Manual URL) above

2. **Connection Timeout**: Server can't reach itself
   - Solution: Make sure both files are on the same server

3. **SSL Error**: HTTPS certificate issue
   - Solution: Already handled in code (SSL verification disabled)

## Verify It's Working

1. Submit a form
2. Check `system-api.log` in `public_html/` folder
3. You should see: "✅ API: Lead received and processed"

## Need Help?

Check these files:
- `lp/system-integration.log` - Shows what send-lead.php is doing
- `public_html/system-api.log` - Shows what system.php received
