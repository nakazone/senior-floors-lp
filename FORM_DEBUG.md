# Form Debugging Checklist

## What to Check:

1. **Open Browser Console (F12)**
   - Look for any JavaScript errors
   - Check if you see: "Senior Floors landing page initialized"
   - Check if you see: "Hero form found: true" and "Contact form found: true"
   - When you submit, check if you see: "Hero form submitted" or "Contact form submitted"

2. **Check Network Tab**
   - When you submit the form, check if there's a request to `send-lead.php`
   - What's the response status? (200, 404, 500?)
   - What's the response body?

3. **Check Form Elements**
   - Are the error message divs visible in the HTML? (Right-click > Inspect)
   - Are the success/error message containers visible?
   - Do the input fields have the correct IDs?

4. **Common Issues:**
   - If form submits but page reloads: JavaScript might not be loading
   - If nothing happens: Check console for errors
   - If validation doesn't work: Check if error divs exist
   - If success message doesn't show: Check CSS for `.success-message.show`

## Quick Test:

1. Open the page
2. Open browser console (F12)
3. Try submitting the form with empty fields - you should see error messages
4. Fill in all fields correctly and submit - you should see "Submitting..." on button
5. Check console for any errors

## Files Updated:
- ✅ index.html - Added error message divs and success/error containers
- ✅ script.js - Updated to match test-form.html pattern
- ✅ styles.css - Added success/error message styles
- ✅ send-lead.php - Fixed to work without PHPMailer
