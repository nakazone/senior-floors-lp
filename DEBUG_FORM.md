# Debugging LP Form Issues

## What to Check

### 1. Open Browser Console
- Press F12 or Right-click → Inspect → Console tab
- Try submitting the form
- Look for any red error messages
- Share what errors you see

### 2. Check Network Tab
- Open Developer Tools → Network tab
- Submit the form
- Look for a request to `send-lead.php`
- Click on it and check:
  - Status code (should be 200)
  - Response tab (what does it say?)

### 3. Common Issues

**Issue: Form doesn't submit at all**
- Check console for JavaScript errors
- Verify `script.js` is loaded (check Network tab)
- Check if form validation is blocking submission

**Issue: Form submits but shows error**
- Check the response from `send-lead.php` in Network tab
- Verify PHPMailer is installed
- Check server error logs

**Issue: Form submits but no success message**
- Check console for JavaScript errors
- Success message might be showing but not visible (check HTML)

## Quick Test

1. Open browser console (F12)
2. Type: `document.getElementById('heroForm')`
3. Should return: `[object HTMLFormElement]` (not null)
4. If null, form ID doesn't match

## Share These Details

1. What happens when you click submit? (nothing? error? page reload?)
2. Any console errors? (copy/paste them)
3. Network tab - does `send-lead.php` get called?
4. What's the response from `send-lead.php`?
