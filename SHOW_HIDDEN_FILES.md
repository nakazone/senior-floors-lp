# How to See .htaccess File

The `.htaccess` file exists but is hidden because files starting with a dot (.) are hidden on macOS.

## Option 1: Use the Visible Copy (Easiest)

I've created a visible copy called `htaccess.txt` in your project folder. 

**For FTP Upload:**
1. Upload `htaccess.txt` to your Hostinger server
2. Rename it to `.htaccess` on the server (remove the `.txt` extension)
3. Or rename it locally before uploading:
   - In Finder: Right-click → Rename → Change `htaccess.txt` to `.htaccess`
   - Or use Terminal: `mv htaccess.txt .htaccess`

## Option 2: Show Hidden Files in Finder

**macOS:**
1. Open Finder
2. Press `Cmd + Shift + .` (period) to toggle hidden files
3. Hidden files (including `.htaccess`) will now be visible
4. Press again to hide them

**Or via Terminal:**
```bash
defaults write com.apple.finder AppleShowAllFiles TRUE
killall Finder
```

To hide again:
```bash
defaults write com.apple.finder AppleShowAllFiles FALSE
killall Finder
```

## Option 3: Access via Terminal

```bash
cd /Users/naka/senior-floors-landing
ls -la .htaccess
cat .htaccess
```

## Option 4: Use FileZilla

When you connect via FTP, FileZilla will show hidden files by default:
1. Connect to your Hostinger FTP
2. Navigate to your project folder
3. You'll see `.htaccess` in the file list
4. Upload it directly

## For FTP Upload to Hostinger:

**Recommended Method:**
1. Use the `htaccess.txt` file I created
2. Upload it to Hostinger
3. In FileZilla or File Manager, rename it to `.htaccess` (remove `.txt`)

**Or:**
1. Show hidden files in Finder (Cmd+Shift+.)
2. Find `.htaccess` in your project folder
3. Upload it directly via FTP

---

**Note:** The `.htaccess` file is important for proper website routing and performance on Hostinger's Apache servers.

