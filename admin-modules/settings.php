<?php
/**
 * Settings Module - Admin panel settings
 */
?>
<style>
    .settings-section {
        margin-bottom: 40px;
    }
    .settings-section h2 {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 20px;
        color: #333;
        padding-bottom: 10px;
        border-bottom: 2px solid #e0e0e0;
    }
    .settings-item {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 15px;
    }
    .settings-item label {
        display: block;
        font-weight: 600;
        margin-bottom: 8px;
        color: #333;
    }
    .settings-item p {
        font-size: 14px;
        color: #666;
        margin-top: 5px;
    }
    .info-box {
        background: #e3f2fd;
        border-left: 4px solid #2196f3;
        padding: 15px;
        border-radius: 6px;
        margin-top: 20px;
    }
    .info-box h3 {
        margin-bottom: 10px;
        color: #1976d2;
    }
    .info-box ul {
        margin-left: 20px;
        color: #666;
    }
    .info-box li {
        margin-bottom: 5px;
    }
</style>

<h1 style="margin-bottom: 30px;">Settings</h1>

<div class="settings-section">
    <h2>Admin Panel Configuration</h2>
    
    <div class="settings-item">
        <label>Admin Users</label>
        <p>To manage admin users (add, edit, change passwords), edit the <code>$admin_users</code> array in <code>admin-config.php</code></p>
        <p><strong>Current Status:</strong> Username/password authentication is enabled</p>
        <p><strong>Default Admin:</strong> username: <code>admin</code></p>
    </div>

    <div class="settings-item">
        <label>Admin Panel Title</label>
        <p>To change the title, edit the <code>$ADMIN_TITLE</code> variable in <code>system.php</code> (line 9)</p>
        <p><strong>Current Title:</strong> Senior Floors System</p>
    </div>
</div>

<div class="settings-section">
    <h2>CRM Settings</h2>
    
    <div class="settings-item">
        <label>Leads Per Page</label>
        <p>To change how many leads show per page, edit <code>$LEADS_PER_PAGE</code> in <code>admin-modules/crm.php</code> (line 6)</p>
        <p><strong>Current:</strong> 25 leads per page</p>
    </div>

    <div class="settings-item">
        <label>Leads CSV File</label>
        <p>Leads are automatically saved to <code>leads.csv</code> when forms are submitted</p>
        <p><strong>Location:</strong> Same directory as your admin panel files</p>
    </div>
</div>

<div class="settings-section">
    <h2>System Information</h2>
    
    <div class="settings-item">
        <label>PHP Version</label>
        <p><?php echo phpversion(); ?></p>
    </div>

    <div class="settings-item">
        <label>Server Time</label>
        <p><?php echo date('Y-m-d H:i:s'); ?></p>
    </div>

    <div class="settings-item">
        <label>Leads CSV File Status</label>
        <?php
        $csv_file = __DIR__ . '/../leads.csv';
        if (file_exists($csv_file)) {
            $file_size = filesize($csv_file);
            $file_date = date('Y-m-d H:i:s', filemtime($csv_file));
            echo "<p><strong>Status:</strong> File exists</p>";
            echo "<p><strong>Size:</strong> " . number_format($file_size) . " bytes</p>";
            echo "<p><strong>Last Modified:</strong> $file_date</p>";
        } else {
            echo "<p><strong>Status:</strong> File not found (will be created when first lead is submitted)</p>";
        }
        ?>
    </div>
</div>

<div class="info-box">
    <h3>?? Adding New Modules</h3>
    <p>To add a new module to the admin panel:</p>
    <ul>
        <li>Create a new PHP file in the <code>admin-modules/</code> directory</li>
        <li>Add the module to the <code>$modules</code> array in <code>system.php</code></li>
        <li>Use the same structure as existing modules</li>
        <li>The module will automatically appear in the sidebar navigation</li>
    </ul>
</div>

<div class="info-box">
    <h3>?? Security Recommendations</h3>
    <ul>
        <li>Change the default password immediately</li>
        <li>Use a strong password (mix of letters, numbers, symbols)</li>
        <li>Don't share the password publicly</li>
        <li>Consider adding IP whitelist restrictions if needed</li>
        <li>Logout when done using the admin panel</li>
    </ul>
</div>
