<?php
/**
 * Customer Detail Module
 */

require_once __DIR__ . '/../config/database.php';

$customer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($customer_id <= 0) {
    echo '<div class="error-message">Invalid customer ID</div>';
    exit;
}

$customer = null;
$projects = [];
$activities = [];
$notes = [];
$tags = [];

if (isDatabaseConfigured()) {
    try {
        $pdo = getDBConnection();
        
        // Get customer
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$customer_id]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$customer) {
            echo '<div class="error-message">Customer not found</div>';
            exit;
        }
        
        // Get projects
        $projects_stmt = $pdo->prepare("SELECT * FROM projects WHERE customer_id = ? ORDER BY created_at DESC");
        $projects_stmt->execute([$customer_id]);
        $projects = $projects_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get activities
        $activities_stmt = $pdo->prepare("SELECT * FROM activities WHERE customer_id = ? ORDER BY activity_date DESC LIMIT 50");
        $activities_stmt->execute([$customer_id]);
        $activities = $activities_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get notes
        $notes_stmt = $pdo->prepare("SELECT * FROM customer_notes WHERE customer_id = ? ORDER BY created_at DESC");
        $notes_stmt->execute([$customer_id]);
        $notes = $notes_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get tags
        $tags_stmt = $pdo->prepare("SELECT tag_name FROM customer_tags WHERE customer_id = ?");
        $tags_stmt->execute([$customer_id]);
        $tags = array_column($tags_stmt->fetchAll(PDO::FETCH_ASSOC), 'tag_name');
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get users
$users = [];
if (isDatabaseConfigured()) {
    try {
        $pdo = getDBConnection();
        $users_stmt = $pdo->query("SELECT id, name FROM users WHERE is_active = 1 ORDER BY name");
        $users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Ignore
    }
}
?>
<div class="module-header">
    <h2>Customer Details</h2>
    <div class="module-actions">
        <a href="?module=customers" class="btn btn-secondary">← Back to Customers</a>
    </div>
</div>

<?php if ($customer): ?>
    <div class="detail-container">
        <!-- Customer Info -->
        <div class="detail-section">
            <h3>Customer Information</h3>
            <div class="detail-grid">
                <div class="detail-item">
                    <label>Name:</label>
                    <span><?php echo htmlspecialchars($customer['name']); ?></span>
                </div>
                <div class="detail-item">
                    <label>Email:</label>
                    <span><a href="mailto:<?php echo htmlspecialchars($customer['email']); ?>"><?php echo htmlspecialchars($customer['email']); ?></a></span>
                </div>
                <div class="detail-item">
                    <label>Phone:</label>
                    <span><a href="tel:<?php echo htmlspecialchars($customer['phone']); ?>"><?php echo htmlspecialchars($customer['phone']); ?></a></span>
                </div>
                <div class="detail-item">
                    <label>Customer Type:</label>
                    <span class="badge badge-type"><?php echo ucfirst(str_replace('_', ' ', $customer['customer_type'])); ?></span>
                </div>
                <div class="detail-item">
                    <label>Status:</label>
                    <select id="customerStatus" onchange="updateCustomerStatus(<?php echo $customer_id; ?>, this.value)" class="status-select">
                        <option value="active" <?php echo $customer['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $customer['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="archived" <?php echo $customer['status'] === 'archived' ? 'selected' : ''; ?>>Archived</option>
                    </select>
                </div>
                <div class="detail-item">
                    <label>Owner:</label>
                    <select id="customerOwner" onchange="assignCustomer(<?php echo $customer_id; ?>, this.value)" class="owner-select">
                        <option value="">Unassigned</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>" <?php echo $customer['owner_id'] == $user['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($customer['address']): ?>
                    <div class="detail-item full-width">
                        <label>Address:</label>
                        <span><?php echo htmlspecialchars($customer['address']); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($customer['city'] || $customer['state'] || $customer['zipcode']): ?>
                    <div class="detail-item">
                        <label>Location:</label>
                        <span>
                            <?php 
                            $location = array_filter([$customer['city'], $customer['state'], $customer['zipcode']]);
                            echo htmlspecialchars(implode(', ', $location));
                            ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tags -->
        <div class="detail-section">
            <h3>Tags</h3>
            <div id="customerTags">
                <?php foreach ($tags as $tag): ?>
                    <span class="tag"><?php echo htmlspecialchars($tag); ?></span>
                <?php endforeach; ?>
            </div>
            <div class="add-tag-section">
                <select id="tagSelect">
                    <option value="">Select tag...</option>
                    <option value="vinyl">Vinyl</option>
                    <option value="hardwood">Hardwood</option>
                    <option value="repair">Repair</option>
                    <option value="installation">Installation</option>
                    <option value="refinishing">Refinishing</option>
                    <option value="commercial">Commercial</option>
                    <option value="residential">Residential</option>
                </select>
                <button onclick="addCustomerTag(<?php echo $customer_id; ?>)" class="btn btn-sm btn-primary">Add Tag</button>
            </div>
        </div>

        <!-- Projects -->
        <div class="detail-section">
            <h3>Projects (<?php echo count($projects); ?>)</h3>
            <?php if (empty($projects)): ?>
                <p>No projects yet. <a href="?module=projects&customer_id=<?php echo $customer_id; ?>">Create Project</a></p>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Post-Service</th>
                            <th>Estimated Cost</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $project): ?>
                            <tr>
                                <td><a href="?module=project-detail&id=<?php echo $project['id']; ?>"><?php echo htmlspecialchars($project['name']); ?></a></td>
                                <td><?php echo ucfirst($project['project_type']); ?></td>
                                <td><span class="badge badge-<?php echo $project['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $project['status'])); ?></span></td>
                                <td>
                                    <?php if ($project['post_service_status']): ?>
                                        <span class="badge badge-info"><?php echo ucfirst(str_replace('_', ' ', $project['post_service_status'])); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $project['estimated_cost'] ? '$' . number_format($project['estimated_cost'], 2) : '-'; ?></td>
                                <td><a href="?module=project-detail&id=<?php echo $project['id']; ?>" class="btn btn-sm btn-primary">View</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Notes -->
        <div class="detail-section">
            <h3>Notes</h3>
            <div id="customerNotes">
                <?php foreach ($notes as $note): ?>
                    <div class="note-item">
                        <div class="note-content"><?php echo nl2br(htmlspecialchars($note['note'])); ?></div>
                        <div class="note-meta">By <?php echo htmlspecialchars($note['created_by']); ?> on <?php echo date('M d, Y H:i', strtotime($note['created_at'])); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="add-note-section">
                <textarea id="newNote" rows="3" placeholder="Add a note..."></textarea>
                <button onclick="addCustomerNote(<?php echo $customer_id; ?>)" class="btn btn-primary">Add Note</button>
            </div>
        </div>

        <!-- Activities Timeline -->
        <div class="detail-section">
            <h3>Activity Timeline</h3>
            <div id="customerActivities">
                <?php if (empty($activities)): ?>
                    <p class="text-muted">No activities yet</p>
                <?php else: ?>
                    <?php foreach ($activities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon"><?php echo getActivityIcon($activity['activity_type']); ?></div>
                            <div class="activity-content">
                                <div class="activity-subject"><?php echo htmlspecialchars($activity['subject'] ?? $activity['activity_type']); ?></div>
                                <?php if ($activity['description']): ?>
                                    <div class="activity-description"><?php echo nl2br(htmlspecialchars($activity['description'])); ?></div>
                                <?php endif; ?>
                                <div class="activity-date"><?php echo date('M d, Y H:i', strtotime($activity['activity_date'])); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
function updateCustomerStatus(customerId, status) {
    const formData = new FormData();
    formData.append('customer_id', customerId);
    formData.append('status', status);
    
    fetch('api/customers/update.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Success - status updated
        } else {
            alert('Error: ' + data.message);
            location.reload();
        }
    });
}

function assignCustomer(customerId, userId) {
    const formData = new FormData();
    formData.append('customer_id', customerId);
    formData.append('to_user_id', userId || '');
    formData.append('assigned_by', 1); // TODO: Get from session
    
    fetch('api/assignment/assign.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Success
        } else {
            alert('Error: ' + data.message);
            location.reload();
        }
    });
}

function addCustomerNote(customerId) {
    const note = document.getElementById('newNote').value;
    if (!note.trim()) {
        alert('Please enter a note');
        return;
    }
    
    const formData = new FormData();
    formData.append('customer_id', customerId);
    formData.append('note', note);
    
    fetch('api/customers/notes.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('newNote').value = '';
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

function addCustomerTag(customerId) {
    const tag = document.getElementById('tagSelect').value;
    if (!tag) {
        alert('Please select a tag');
        return;
    }
    
    const formData = new FormData();
    formData.append('customer_id', customerId);
    formData.append('action', 'add');
    formData.append('tag', tag);
    
    fetch('api/customers/tags.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}
</script>

<?php
function getActivityIcon($type) {
    $icons = [
        'email_sent' => '📧',
        'whatsapp_message' => '💬',
        'phone_call' => '📞',
        'meeting_scheduled' => '📅',
        'site_visit' => '🏠',
        'proposal_sent' => '📄',
        'note' => '📝',
        'status_change' => '🔄',
        'assignment' => '👤',
        'other' => '⚙️'
    ];
    return $icons[$type] ?? '⚙️';
}
?>

<style>
.detail-container {
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.detail-section {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.detail-section h3 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #333;
    border-bottom: 2px solid #007bff;
    padding-bottom: 10px;
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.detail-item.full-width {
    grid-column: 1 / -1;
}

.detail-item label {
    font-weight: 600;
    color: #666;
    font-size: 14px;
}

.detail-item span {
    color: #333;
    font-size: 15px;
}

.status-select,
.owner-select {
    padding: 6px 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    width: 200px;
}

.tag {
    display: inline-block;
    background: #e7f3ff;
    color: #0066cc;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    margin-right: 8px;
    margin-bottom: 8px;
}

.add-tag-section {
    margin-top: 15px;
    display: flex;
    gap: 10px;
}

.note-item {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 15px;
    border-left: 3px solid #007bff;
}

.note-content {
    margin-bottom: 8px;
    color: #333;
}

.note-meta {
    font-size: 12px;
    color: #666;
}

.add-note-section {
    margin-top: 20px;
}

.add-note-section textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 10px;
    font-family: inherit;
}

.activity-item {
    display: flex;
    gap: 15px;
    padding: 15px;
    border-left: 3px solid #007bff;
    background: #f9f9f9;
    margin-bottom: 15px;
    border-radius: 4px;
}

.activity-icon {
    font-size: 24px;
}

.activity-content {
    flex: 1;
}

.activity-subject {
    font-weight: 600;
    color: #333;
    margin-bottom: 5px;
}

.activity-description {
    color: #666;
    margin-bottom: 5px;
}

.activity-date {
    font-size: 12px;
    color: #999;
}

.badge-info {
    background: #d1ecf1;
    color: #0c5460;
}
</style>
