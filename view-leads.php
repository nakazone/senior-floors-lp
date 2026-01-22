<?php
/**
 * View Leads - Simple page to view submitted leads
 * 
 * SECURITY: Add password protection or restrict access by IP
 * Upload this file to view your leads in the browser
 */

// Simple password protection (change this password!)
$password = 'change-this-password-123';
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Check if password is set
session_start();
if (!isset($_SESSION['leads_authenticated'])) {
    if (isset($_POST['password'])) {
        if (password_verify($_POST['password'], $password_hash)) {
            $_SESSION['leads_authenticated'] = true;
        } else {
            $error = 'Invalid password';
        }
    }
    
    if (!isset($_SESSION['leads_authenticated'])) {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>View Leads - Login</title>
            <style>
                body { font-family: Arial, sans-serif; max-width: 400px; margin: 100px auto; padding: 20px; }
                input { width: 100%; padding: 10px; margin: 10px 0; box-sizing: border-box; }
                button { width: 100%; padding: 10px; background: #007bff; color: white; border: none; cursor: pointer; }
                .error { color: red; }
            </style>
        </head>
        <body>
            <h2>View Leads - Login</h2>
            <?php if (isset($error)) echo '<p class="error">' . $error . '</p>'; ?>
            <form method="POST">
                <input type="password" name="password" placeholder="Enter password" required>
                <button type="submit">Login</button>
            </form>
        </body>
        </html>
        <?php
        exit;
    }
}

// Read CSV file
$csv_file = __DIR__ . '/leads.csv';
if (!file_exists($csv_file)) {
    echo '<h2>No leads found yet</h2>';
    exit;
}

$leads = [];
if (($handle = fopen($csv_file, 'r')) !== FALSE) {
    $header = fgetcsv($handle);
    while (($data = fgetcsv($handle)) !== FALSE) {
        $leads[] = array_combine($header, $data);
    }
    fclose($handle);
}

// Reverse to show newest first
$leads = array_reverse($leads);
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Leads - Senior Floors</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #007bff; color: white; }
        tr:hover { background-color: #f5f5f5; }
        .count { font-size: 18px; margin-bottom: 20px; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>Senior Floors - Submitted Leads</h1>
    <p class="count"><strong>Total Leads: <?php echo count($leads); ?></strong></p>
    <p><a href="?download=1">Download CSV</a> | <a href="?logout=1">Logout</a></p>
    
    <?php if (isset($_GET['download'])): ?>
        <?php
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="leads-' . date('Y-m-d') . '.csv"');
        readfile($csv_file);
        exit;
        ?>
    <?php endif; ?>
    
    <?php if (isset($_GET['logout'])): ?>
        <?php
        session_destroy();
        header('Location: view-leads.php');
        exit;
        ?>
    <?php endif; ?>
    
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Form</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Zip Code</th>
                <th>Message</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($leads)): ?>
                <tr><td colspan="7">No leads found</td></tr>
            <?php else: ?>
                <?php foreach ($leads as $lead): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($lead['Date'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($lead['Form'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($lead['Name'] ?? ''); ?></td>
                        <td><a href="tel:<?php echo htmlspecialchars($lead['Phone'] ?? ''); ?>"><?php echo htmlspecialchars($lead['Phone'] ?? ''); ?></a></td>
                        <td><a href="mailto:<?php echo htmlspecialchars($lead['Email'] ?? ''); ?>"><?php echo htmlspecialchars($lead['Email'] ?? ''); ?></a></td>
                        <td><?php echo htmlspecialchars($lead['ZipCode'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($lead['Message'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
