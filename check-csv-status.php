<?php
/**
 * Check CSV Status - View CSV file and submission logs
 */

header('Content-Type: text/html; charset=UTF-8');

$csv_file = __DIR__ . '/leads.csv';
$log_file = __DIR__ . '/email-status.log';
$submissions_log = __DIR__ . '/form-submissions.log';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Check CSV Status</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        th { background: #007bff; color: white; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .stats { display: flex; gap: 20px; margin: 20px 0; }
        .stat-box { padding: 15px; background: #e7f3ff; border-left: 4px solid #2196F3; flex: 1; }
    </style>
</head>
<body>
    <h1>CSV Status Check</h1>
    
    <div class="stats">
        <div class="stat-box">
            <strong>CSV File:</strong> <?php echo file_exists($csv_file) ? '✅ EXISTS' : '❌ NOT FOUND'; ?><br>
            <strong>Size:</strong> <?php echo file_exists($csv_file) ? filesize($csv_file) . ' bytes' : 'N/A'; ?><br>
            <strong>Writable:</strong> <?php echo is_writable($csv_file) ? '✅ YES' : '❌ NO'; ?>
        </div>
        <div class="stat-box">
            <strong>Log File:</strong> <?php echo file_exists($log_file) ? '✅ EXISTS' : '❌ NOT FOUND'; ?><br>
            <strong>Size:</strong> <?php echo file_exists($log_file) ? filesize($log_file) . ' bytes' : 'N/A'; ?>
        </div>
        <div class="stat-box">
            <strong>Submissions Log:</strong> <?php echo file_exists($submissions_log) ? '✅ EXISTS' : '❌ NOT FOUND'; ?><br>
            <strong>Size:</strong> <?php echo file_exists($submissions_log) ? filesize($submissions_log) . ' bytes' : 'N/A'; ?>
        </div>
    </div>
    
    <h2>CSV Contents (<?php echo file_exists($csv_file) ? count(file($csv_file)) - 1 : 0; ?> leads)</h2>
    <?php if (file_exists($csv_file)): ?>
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
                <?php
                $handle = fopen($csv_file, 'r');
                $header = fgetcsv($handle);
                $row_num = 0;
                while (($data = fgetcsv($handle)) !== FALSE) {
                    $row_num++;
                    echo '<tr>';
                    foreach ($data as $cell) {
                        echo '<td>' . htmlspecialchars($cell) . '</td>';
                    }
                    echo '</tr>';
                }
                fclose($handle);
                ?>
            </tbody>
        </table>
        <p><strong>Total rows in CSV:</strong> <?php echo $row_num; ?> (excluding header)</p>
    <?php else: ?>
        <p>❌ CSV file not found!</p>
    <?php endif; ?>
    
    <h2>Recent Email Status Logs (Last 50 lines)</h2>
    <?php if (file_exists($log_file)): ?>
        <pre><?php 
        $lines = file($log_file);
        echo htmlspecialchars(implode('', array_slice($lines, -50)));
        ?></pre>
    <?php else: ?>
        <p>❌ Log file not found!</p>
    <?php endif; ?>
    
    <h2>Form Submissions Log (Last 50 lines)</h2>
    <?php if (file_exists($submissions_log)): ?>
        <pre><?php 
        $lines = file($submissions_log);
        echo htmlspecialchars(implode('', array_slice($lines, -50)));
        ?></pre>
    <?php else: ?>
        <p>❌ Submissions log file not found!</p>
    <?php endif; ?>
    
    <p><a href="?refresh=1">Refresh</a></p>
</body>
</html>
