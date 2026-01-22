# PHPMailer Quick Setup Guide

## ✅ Current Status

Your form is **already configured** to use PHPMailer (`send-lead.php`), which is the **better and more reliable** option compared to manual SMTP.

## 🔧 What You Need to Do

### Step 1: Install PHPMailer Library

You need to download and upload the PHPMailer library files:

1. **Download PHPMailer:**
   - Go to: https://github.com/PHPMailer/PHPMailer/releases/latest
   - Download the ZIP file (e.g., `PHPMailer-6.x.x.zip`)

2. **Extract and Upload:**
   - Extract the ZIP file
   - Find the `src` folder inside
   - Upload these 3 files to your server in a folder called `PHPMailer`:
     - `src/Exception.php` → `PHPMailer/Exception.php`
     - `src/PHPMailer.php` → `PHPMailer/PHPMailer.php`
     - `src/SMTP.php` → `PHPMailer/SMTP.php`

   **Final structure on your server:**
   ```
   public_html/
   ├── index.html
   ├── send-lead.php
   ├── script.js
   └── PHPMailer/
       ├── Exception.php
       ├── PHPMailer.php
       └── SMTP.php
   ```

### Step 2: Configure Google App Password

1. **Create App Password:**
   - Go to: https://myaccount.google.com/apppasswords
   - Sign in with: `contact@senior-floors.com`
   - Enable 2-Step Verification if not already enabled
   - Create App Password:
     - App: "Mail"
     - Device: "Other (Custom name)"
     - Name: "Senior Floors PHPMailer"
   - Copy the 16-character password (remove spaces)

2. **Update send-lead.php:**
   - Open `send-lead.php`
   - Find line 74:
     ```php
     define('SMTP_PASS', 'YOUR_APP_PASSWORD_HERE');
     ```
   - Replace with your App Password:
     ```php
     define('SMTP_PASS', 'abcdefghijklmnop'); // Your 16-character App Password
     ```

### Step 3: Test

1. Fill out the form on your website
2. Submit it
3. Check `leads@senior-floors.com` for the email
4. Check `email-status.log` for detailed status

## ✅ Advantages of PHPMailer

- ✅ **More reliable** than manual SMTP
- ✅ **Better error handling** and debugging
- ✅ **HTML email support** (your emails will look professional)
- ✅ **Widely used** and well-maintained
- ✅ **Automatic retry** and error recovery

## 🔍 Verification Checklist

- [ ] PHPMailer folder created on server
- [ ] 3 PHP files uploaded (Exception.php, PHPMailer.php, SMTP.php)
- [ ] App Password created for `contact@senior-floors.com`
- [ ] App Password added to `send-lead.php` (line 74)
- [ ] Form tested successfully
- [ ] Email received at `leads@senior-floors.com`

## ❌ Troubleshooting

### Error: "Class 'PHPMailer\PHPMailer\PHPMailer' not found"

**Solution:** PHPMailer files are not uploaded correctly. Verify:
- Folder name is exactly `PHPMailer` (case-sensitive)
- All 3 files are in the `PHPMailer` folder
- Files are uploaded to the same directory as `send-lead.php`

### Error: "SMTP password not configured"

**Solution:** You haven't updated the App Password in `send-lead.php`. Replace `YOUR_APP_PASSWORD_HERE` with your actual App Password.

### Email not arriving

**Solutions:**
1. Check `email-status.log` for error messages
2. Verify App Password is correct (16 characters, no spaces)
3. Check spam folder
4. Ensure 2-Step Verification is enabled
5. Verify `leads@senior-floors.com` exists and is active

## 📝 Current Configuration

- **Form Handler:** `send-lead.php` (PHPMailer) ✅
- **JavaScript:** Already pointing to `send-lead.php` ✅
- **Email From:** `contact@senior-floors.com`
- **Email To:** `leads@senior-floors.com`
- **SMTP Server:** `smtp.gmail.com:587` (TLS)

## 🎯 Summary

**You don't need to change anything** - your form is already set up correctly to use PHPMailer! You just need to:

1. ✅ Install PHPMailer library files
2. ✅ Configure the Google App Password

That's it! Once these two steps are done, your form will work perfectly.
