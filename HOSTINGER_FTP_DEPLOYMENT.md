# Hostinger FTP Deployment Guide

This guide will help you deploy your Senior Floors landing page to Hostinger via FTP.

## 📋 Files to Upload

Upload these files and folders to your Hostinger hosting:

### Required Files:
- ✅ `index.html` (main page)
- ✅ `styles.css` (styles)
- ✅ `script.js` (JavaScript)
- ✅ `contact-form-handler.php` (form submission handler)
- ✅ `assets/` folder (all images and icons)

### Optional Files (recommended):
- ✅ `.htaccess` (for proper routing and performance)

### Files to EXCLUDE (don't upload):
- ❌ `.git/` folder
- ❌ `node_modules/` folder
- ❌ `package.json`
- ❌ `package-lock.json`
- ❌ `server.py`
- ❌ `start-server.sh`
- ❌ `.gitignore`
- ❌ `DEPLOYMENT.md`
- ❌ `HOSTINGER_FTP_DEPLOYMENT.md`
- ❌ `netlify.toml`
- ❌ `vercel.json`
- ❌ `README.md` (optional)

---

## 🚀 Step-by-Step FTP Upload Instructions

### Step 1: Get Your FTP Credentials

1. Log in to your Hostinger account
2. Go to **hPanel** → **Files** → **FTP Accounts**
3. Note down:
   - **FTP Host** (usually `ftp.yourdomain.com` or IP address)
   - **FTP Username**
   - **FTP Password**
   - **Port** (usually 21)

### Step 2: Connect via FTP Client

**Option A: Using FileZilla (Recommended - Free)**
1. Download FileZilla: https://filezilla-project.org/
2. Open FileZilla
3. Click **File** → **Site Manager**
4. Click **New Site** and enter:
   - **Host**: Your FTP host
   - **Port**: 21 (or your custom port)
   - **Protocol**: FTP - File Transfer Protocol
   - **Encryption**: Use explicit FTP over TLS if available
   - **Logon Type**: Normal
   - **User**: Your FTP username
   - **Password**: Your FTP password
5. Click **Connect**

**Option B: Using Hostinger File Manager**
1. Log in to hPanel
2. Go to **Files** → **File Manager**
3. Navigate to `public_html` folder (or your domain's root folder)

### Step 3: Upload Files

**Using FileZilla:**
1. On the **left side** (Local site): Navigate to your project folder
2. On the **right side** (Remote site): Navigate to `public_html` (or your domain root)
3. Select these files/folders from left:
   - `index.html`
   - `styles.css`
   - `script.js`
   - `assets` folder
   - `.htaccess` file
4. **Drag and drop** or **Right-click** → **Upload**
5. Wait for upload to complete

**Using File Manager:**
1. Click **Upload** button
2. Select all files and folders
3. Wait for upload to complete

### Step 4: Verify File Structure

Your `public_html` folder should look like this:
```
public_html/
├── index.html
├── styles.css
├── script.js
├── contact-form-handler.php
├── .htaccess
└── assets/
    ├── background.jpg
    ├── logoSeniorFloors.png
    ├── project1.jpg
    ├── project2.jpg
    ├── project3.jpg
    ├── project4.jpg
    ├── hammer.png
    ├── house.png
    ├── laminating.png
    ├── parquet.png
    ├── stairs.png
    └── tools.png
```

### Step 5: Set File Permissions (if needed)

In FileZilla or File Manager:
- `index.html`: **644** (rw-r--r--)
- `styles.css`: **644**
- `script.js`: **644**
- `contact-form-handler.php`: **644** (rw-r--r--)
- `.htaccess`: **644**
- `assets/` folder: **755** (drwxr-xr-x)
- All image files: **644**

**To set permissions in FileZilla:**
1. Right-click file → **File permissions**
2. Enter **644** for files, **755** for folders
3. Check **Recurse into subdirectories** for folders

### Step 6: Test Your Website

1. Visit your domain: `https://yourdomain.com`
2. Check if the page loads correctly
3. Test all links and buttons
4. Verify images load properly
5. Test on mobile device

---

## 🔧 Troubleshooting

### Images Not Loading?
- Check file paths in HTML (should be `assets/filename.jpg`)
- Verify all images are uploaded to `assets/` folder
- Check file permissions (should be 644)

### CSS/JS Not Loading?
- Verify file names match exactly (case-sensitive)
- Check file permissions (should be 644)
- Clear browser cache (Ctrl+F5 or Cmd+Shift+R)

### 404 Errors?
- Make sure `.htaccess` file is uploaded
- Check file permissions on `.htaccess` (644)
- Verify you're in the correct directory (`public_html`)

### SSL/HTTPS Issues?
- In Hostinger hPanel, enable SSL certificate
- Uncomment HTTPS redirect in `.htaccess` if needed

---

## 📝 Quick Upload Checklist

- [ ] FTP credentials ready
- [ ] FTP client installed (FileZilla)
- [ ] Connected to Hostinger FTP
- [ ] Navigated to `public_html` folder
- [ ] Uploaded `index.html`
- [ ] Uploaded `styles.css`
- [ ] Uploaded `script.js`
- [ ] Uploaded `contact-form-handler.php`
- [ ] Uploaded `assets/` folder (with all images)
- [ ] Uploaded `.htaccess` file
- [ ] Set correct file permissions
- [ ] Tested website in browser
- [ ] Verified mobile responsiveness

---

## 🆘 Need Help?

- **Hostinger Support**: https://www.hostinger.com/contact
- **FileZilla Guide**: https://wiki.filezilla-project.org/
- **Hostinger Knowledge Base**: https://support.hostinger.com/

---

## 💡 Tips

1. **Backup First**: Always backup your current website before uploading
2. **Test Locally**: Test with `npm start` before uploading
3. **Use SFTP**: If available, use SFTP instead of FTP for better security
4. **Keep Backup**: Keep a local copy of all files
5. **Update Files**: When updating, just re-upload changed files

---

**Your site should be live at:** `https://yourdomain.com` 🎉

