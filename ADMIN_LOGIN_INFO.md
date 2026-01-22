# Admin Login Information

## 🚀 First-Time Setup

**Before first login, you MUST generate the password hash:**

### Method 1: Auto-Update (if file permissions allow)

1. Upload all files to your server
2. Visit: `https://yourdomain.com/setup-admin-password.php`
3. Click "Generate Hash & Update Config"
4. If successful, you're done! If you get a permissions error, use Method 2 below.

### Method 2: Manual Update (if auto-update fails)

1. Visit: `https://yourdomain.com/get-password-hash.php` (or `setup-admin-password.php`)
2. Copy the generated hash
3. Open `admin-config.php` in a text editor
4. Find the line with `'password' => '...'`
5. Replace the hash with the one you copied
6. Save and upload to server

**See `FIX_PERMISSIONS.md` for help with file permissions issues.**

## 🔐 Default Admin Account

**Username:** `admin`  
**Password:** `SeniorFloors2024!`

⚠️ **IMPORTANT:** Change this password immediately after first login!

## 🔒 Security: Password Hashing

**Passwords are now stored securely using password hashing!**

- Passwords in `admin-config.php` are stored as **hashes**, not plain text
- Even if someone accesses the file, they cannot see the actual passwords
- Uses PHP's `password_hash()` with bcrypt (industry standard)

## 📝 How to Change Password

### Step 1: Generate Password Hash

Run the password hash generator:

```bash
php generate-password-hash.php
```

Enter your new password when prompted, and copy the generated hash.

### Step 2: Update admin-config.php

1. Open `admin-config.php`
2. Find the admin user (around line 12)
3. Replace the password hash:
   ```php
   'password' => '$2y$10$...your-generated-hash-here...',
   ```
4. Save the file
5. Upload to server

**Example:**
```php
'admin' => [
    'username' => 'admin',
    'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'name' => 'Administrator',
    // ...
],
```

## 👤 Adding More Users

1. **Generate hash** for the new user's password:
   ```bash
   php generate-password-hash.php
   ```

2. **Add user** to `admin-config.php`:
   ```php
   $admin_users = [
       'admin' => [
           'username' => 'admin',
           'password' => '$2y$10$...hash...',
           'name' => 'Administrator',
           'email' => 'admin@senior-floors.com',
           'role' => 'admin'
       ],
       'manager' => [  // Add this
           'username' => 'manager',
           'password' => '$2y$10$...generated-hash...', // Use generate-password-hash.php
           'name' => 'Manager Name',
           'email' => 'manager@senior-floors.com',
           'role' => 'admin'
       ],
   ];
   ```

## 🔒 Security Notes

- ✅ **Passwords are hashed** - cannot be read even if file is accessed
- ✅ **Keep `admin-config.php` secure** - don't share it publicly
- ✅ **Use strong passwords** (mix of letters, numbers, symbols)
- ✅ **Change default password immediately**
- ✅ **Don't commit passwords to public repositories**
- ⚠️ **Delete `generate-password-hash.php` after setup** (optional, but recommended)

## 🚪 Access

Visit: `https://yourdomain.com/system.php`

Enter your username and password to login.

## 🛠️ Troubleshooting

### "Could not write to admin-config.php" Error

**Solution:** Use the manual update method:
1. Visit `get-password-hash.php` to get the hash
2. Manually copy the hash into `admin-config.php`
3. See `FIX_PERMISSIONS.md` for detailed instructions

### Can't run `generate-password-hash.php`?

If you don't have PHP CLI access, you can:
- Use `get-password-hash.php` via web browser (easiest)
- Use `setup-admin-password.php` via web browser
- Generate hashes online at: https://codeshack.io/php-password-hash-generator/

Just make sure to use `PASSWORD_DEFAULT` algorithm.
