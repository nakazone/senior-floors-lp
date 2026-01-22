# How to Test the Form Locally

## ❌ The Problem: "Failed to fetch" Error

If you're getting a "Failed to fetch" error, it's because you're opening the HTML file directly (using `file://` protocol). PHP files need to run through a web server.

## ✅ Solution: Use PHP's Built-in Server

### Option 1: Use the Script (Easiest)

1. Open Terminal
2. Navigate to the project folder:
   ```bash
   cd /Users/naka/senior-floors-landing
   ```
3. Run the PHP server script:
   ```bash
   ./start-php-server.sh
   ```
4. Open your browser and visit:
   ```
   http://localhost:8000/test-form.html
   ```

### Option 2: Manual PHP Server

1. Open Terminal
2. Navigate to the project folder:
   ```bash
   cd /Users/naka/senior-floors-landing
   ```
3. Start PHP's built-in server:
   ```bash
   php -S localhost:8000
   ```
4. Open your browser and visit:
   ```
   http://localhost:8000/test-form.html
   ```

### Option 3: If PHP is Not Installed

**On macOS:**
```bash
brew install php
```

Then follow Option 1 or 2 above.

## 🧪 Testing Steps

1. **Start the PHP server** (using one of the options above)
2. **Open the test form** in your browser: `http://localhost:8000/test-form.html`
3. **Fill out the form:**
   - Name: Your name
   - Email: test@example.com
   - Cellphone: (720) 555-1234
   - Zipcode: 80202
4. **Submit the form**
5. **Check the results:**
   - You should see a success message
   - Check `leads.csv` for the saved submission
   - Check `email-status.log` for email sending status

## 🔍 Troubleshooting

### "Failed to fetch" Error
- ✅ Make sure you're accessing via `http://localhost:8000/test-form.html` (not `file://`)
- ✅ Make sure the PHP server is running
- ✅ Check that `send-lead.php` exists in the same directory

### "PHP not found" Error
- Install PHP: `brew install php` (macOS)
- Or test directly on your web server instead of locally

### Form submits but email doesn't send
- Check `email-status.log` for error messages
- Make sure Google App Password is configured in `send-lead.php`
- Verify PHPMailer library is installed (if using PHPMailer)

### 404 Error
- Make sure `send-lead.php` is in the same directory as `test-form.html`
- Check that the PHP server is running from the correct directory

## 📝 Quick Test Without Server

If you just want to test the form UI (without PHP), you can:
1. Open `test-form.html` directly
2. The form will show an error when submitting (expected)
3. But you can test the validation and UI

For full functionality, you **must** use a PHP server.
