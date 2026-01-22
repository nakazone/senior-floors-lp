# How to Install PHP (for Testing Forms Locally)

## The Problem

PHP is not installed on your system, so you can't run the PHP server locally.

## ✅ Solution Options

### Option 1: Install PHP (Recommended for Full Testing)

**On macOS:**

1. **Install Homebrew** (if you don't have it):
   ```bash
   /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
   ```

2. **Install PHP:**
   ```bash
   brew install php
   ```

3. **Verify installation:**
   ```bash
   php --version
   ```

4. **Start the PHP server:**
   ```bash
   cd /Users/naka/senior-floors-landing
   php -S localhost:8000
   ```

5. **Open in browser:**
   ```
   http://localhost:8000/test-form.html
   ```

### Option 2: Use Test Form Without PHP (Quick Testing)

I've created `test-form-no-php.html` which works without PHP:

1. **Just open the file directly** in your browser (double-click it)
2. Fill out the form
3. It will show you what data would be submitted (no actual email sent)
4. Perfect for testing form validation and UI

### Option 3: Test on Your Web Server

Instead of testing locally, upload the files to your web server and test there:

1. Upload `test-form.html` and `send-lead.php` to your server
2. Visit: `https://yourdomain.com/test-form.html`
3. Test the form

## 🎯 Recommended Approach

**For quick UI/validation testing:** Use `test-form-no-php.html` (no installation needed)

**For full functionality testing:** Install PHP (Option 1) or test on your web server (Option 3)

## 📝 Notes

- The test form without PHP (`test-form-no-php.html`) shows you exactly what data would be submitted
- It validates all fields correctly
- It just doesn't send emails (which is fine for testing the form itself)
- When you're ready to test email sending, either install PHP or test on your actual server
