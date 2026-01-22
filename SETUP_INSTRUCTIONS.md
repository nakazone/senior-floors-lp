# Senior Floors Form Setup Instructions

## ✅ Form Status

Your form is already set up with the following fields:
- **Name** (required)
- **Email** (required)
- **Cellphone** (required)
- **Zipcode** (required)

The form is configured to send emails via Google Workspace SMTP.

## 🔧 Quick Setup Steps

### Step 1: Create Google Workspace App Password

1. Go to your Google Account: https://myaccount.google.com/security
2. Sign in with: `contact@senior-floors.com`
3. Enable **2-Step Verification** (if not already enabled)
   - Go to: Security → 2-Step Verification
   - Follow the setup process
4. Create App Password:
   - Go to: Security → App passwords (or visit: https://myaccount.google.com/apppasswords)
   - Select: **Mail** → **Other (Custom name)**
   - Name it: `Senior Floors Contact Form`
   - Click **Generate**
   - **Copy the 16-character password** (it will look like: `abcd efgh ijkl mnop`)
   - Remove spaces: `abcdefghijklmnop`

### Step 2: Configure the Form Handler

1. Open the file: `contact-form-handler.php`
2. Find line 127:
   ```php
   define('SMTP_PASS', 'YOUR_APP_PASSWORD_HERE');
   ```
3. Replace `YOUR_APP_PASSWORD_HERE` with your App Password (16 characters, no spaces)
   ```php
   define('SMTP_PASS', 'abcdefghijklmnop'); // Your actual App Password
   ```
4. Save the file
5. Upload to your server

### Step 3: Test the Form

1. Fill out the form on your website
2. Submit it
3. Check `leads@senior-floors.com` for the email
4. Check the spam folder (first email might go there)
5. Check `email-status.log` for detailed status

## 📧 Email Configuration

- **From:** `contact@senior-floors.com`
- **To:** `leads@senior-floors.com`
- **SMTP Server:** `smtp.gmail.com`
- **Port:** `587` (TLS)

## 📋 Form Fields

The form collects:
- Full Name
- Email Address
- Cellphone Number
- Zip Code

All submissions are also saved to `leads.csv` for backup.

## 🔍 Troubleshooting

### Email not arriving?
1. Check `email-status.log` for error messages
2. Verify App Password is correct (16 characters, no spaces)
3. Check spam folder
4. Verify `leads@senior-floors.com` exists and is active
5. Ensure 2-Step Verification is enabled

### Form not submitting?
1. Check browser console for errors
2. Verify `contact-form-handler.php` is uploaded correctly
3. Check server PHP error logs

## ✅ Checklist

- [ ] 2-Step Verification enabled on `contact@senior-floors.com`
- [ ] App Password created and copied
- [ ] App Password added to `contact-form-handler.php` (line 127)
- [ ] File uploaded to server
- [ ] Form tested successfully
- [ ] Email received at `leads@senior-floors.com`

## 📞 Support

If you need help, check:
- `GOOGLE_WORKSPACE_SETUP.md` - Detailed Google Workspace setup
- `email-status.log` - Form submission logs
- `leads.csv` - All form submissions (backup)
