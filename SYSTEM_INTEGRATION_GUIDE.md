# System Integration Guide

## Current Status

✅ **Form submissions are already going to your system!**

Your form submissions are currently:
1. ✅ Saved to `leads.csv` (which your CRM system reads)
2. ✅ Sent via email (if PHPMailer is configured)
3. ✅ Logged to `form-submissions.log`

Your CRM system (`system.php` → CRM module) reads from `leads.csv` and displays all leads.

## Adding Additional System Integrations

If you want to send form data to **another system** (API, webhook, database, third-party CRM), follow these steps:

### Step 1: Open `send-lead.php`

Find the section labeled `// SEND TO EXTERNAL SYSTEM` (around line 255).

### Step 2: Choose Your Integration Type

#### Option A: Webhook/API Endpoint

Uncomment the webhook section and add your URL:

```php
$webhook_url = 'https://your-system.com/api/leads';
// ... rest of the code
```

#### Option B: Database (MySQL)

Uncomment the database section and configure:

```php
$db_host = 'localhost';
$db_name = 'your_database';
$db_user = 'your_username';
$db_pass = 'your_password';
// ... rest of the code
```

#### Option C: Third-Party CRM (HubSpot, Salesforce, etc.)

Uncomment the CRM section and add your API credentials:

```php
$hubspot_api_key = 'YOUR_HUBSPOT_API_KEY';
// ... rest of the code
```

### Step 3: Test

1. Submit a test form
2. Check `system-integration.log` for status
3. Verify data appears in your external system

## What System Do You Want to Integrate?

Please let me know:
- **What system?** (API URL, database, CRM name, etc.)
- **What format?** (JSON, form data, specific API format)
- **Authentication?** (API key, OAuth, username/password)

I can help you configure the exact integration you need!

## Current Data Flow

```
Form Submission
    ↓
send-lead.php
    ↓
    ├─→ leads.csv (✅ Your CRM reads this)
    ├─→ Email (if configured)
    └─→ External System (configure above)
```

## Need Help?

If you need help configuring a specific integration, provide:
1. System name/type
2. API endpoint or connection details
3. Required data format
4. Authentication method
