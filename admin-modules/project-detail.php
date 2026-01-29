<?php
/**
 * Project Detail Module
 */

require_once __DIR__ . '/../config/database.php';

$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($project_id <= 0) {
    echo '<div class="error-message">Invalid project ID</div>';
    exit;
}

$project = null;
$customer = null;
$activities = [];
$notes = [];
$tags = [];
$documents = [];
$issues = [];
$checklist = [];

if (isDatabaseConfigured()) {
    try {
        $pdo = getDBConnection();
        
        // Get project with customer info
        $stmt = $pdo->prepare("
            SELECT p.*, c.name as customer_name, c.email as customer_email, c.phone as customer_phone, c.id as customer_id
            FROM projects p
            LEFT JOIN customers c ON c.id = p.customer_id
            WHERE p.id = ?
        ");
        $stmt->execute([$project_id]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$project) {
            echo '<div class="error-message">Project not found</div>';
            exit;
        }
        
        // Get customer info
        if ($project['customer_id']) {
            $customer_stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
            $customer_stmt->execute([$project['customer_id']]);
            $customer = $customer_stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Get activities
        $activities_stmt = $pdo->prepare("SELECT * FROM activities WHERE project_id = ? ORDER BY activity_date DESC LIMIT 50");
        $activities_stmt->execute([$project_id]);
        $activities = $activities_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get notes
        $notes_stmt = $pdo->prepare("SELECT * FROM project_notes WHERE project_id = ? ORDER BY created_at DESC");
        $notes_stmt->execute([$project_id]);
        $notes = $notes_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get tags
        $tags_stmt = $pdo->prepare("SELECT tag_name FROM project_tags WHERE project_id = ?");
        $tags_stmt->execute([$project_id]);
        $tags = array_column($tags_stmt->fetchAll(PDO::FETCH_ASSOC), 'tag_name');

        // Pós-venda: documentos, problemas, checklist
        try {
            if ($pdo->query("SHOW TABLES LIKE 'project_documents'")->rowCount() > 0) {
                $st = $pdo->prepare("SELECT * FROM project_documents WHERE project_id = ? ORDER BY created_at DESC");
                $st->execute([$project_id]);
                $documents = $st->fetchAll(PDO::FETCH_ASSOC);
            }
            if ($pdo->query("SHOW TABLES LIKE 'project_issues'")->rowCount() > 0) {
                $st = $pdo->prepare("SELECT * FROM project_issues WHERE project_id = ? ORDER BY created_at DESC");
                $st->execute([$project_id]);
                $issues = $st->fetchAll(PDO::FETCH_ASSOC);
            }
            if ($pdo->query("SHOW TABLES LIKE 'delivery_checklists'")->rowCount() > 0) {
                $st = $pdo->prepare("SELECT * FROM delivery_checklists WHERE project_id = ? ORDER BY id");
                $st->execute([$project_id]);
                $checklist = $st->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {}
        
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
    <h2>Project Details</h2>
    <div class="module-actions">
        <a href="?module=projects" class="btn btn-secondary">← Back to Projects</a>
        <?php if ($customer): ?>
            <a href="?module=customer-detail&id=<?php echo $customer['id']; ?>" class="btn btn-secondary">View Customer</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($project): ?>
    <div class="detail-container">
        <!-- Project Info -->
        <div class="detail-section">
            <h3>Project Information</h3>
            <div class="detail-grid">
                <div class="detail-item">
                    <label>Project Name:</label>
                    <span><?php echo htmlspecialchars($project['name']); ?></span>
                </div>
                <div class="detail-item">
                    <label>Customer:</label>
                    <span>
                        <?php if ($customer): ?>
                            <a href="?module=customer-detail&id=<?php echo $customer['id']; ?>">
                                <?php echo htmlspecialchars($customer['name']); ?>
                            </a>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="detail-item">
                    <label>Project Type:</label>
                    <span><?php echo ucfirst($project['project_type']); ?></span>
                </div>
                <div class="detail-item">
                    <label>Status:</label>
                    <select id="projectStatus" onchange="updateProjectStatus(<?php echo $project_id; ?>, this.value)" class="status-select">
                        <option value="quoted" <?php echo $project['status'] === 'quoted' ? 'selected' : ''; ?>>Quoted</option>
                        <option value="scheduled" <?php echo $project['status'] === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                        <option value="in_progress" <?php echo $project['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="completed" <?php echo $project['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $project['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        <option value="on_hold" <?php echo $project['status'] === 'on_hold' ? 'selected' : ''; ?>>On Hold</option>
                    </select>
                </div>
                <div class="detail-item">
                    <label>Post-Service Status:</label>
                    <select id="postServiceStatus" onchange="updatePostServiceStatus(<?php echo $project_id; ?>, this.value)" class="status-select">
                        <option value="">None</option>
                        <option value="installation_scheduled" <?php echo $project['post_service_status'] === 'installation_scheduled' ? 'selected' : ''; ?>>Installation Scheduled</option>
                        <option value="installation_completed" <?php echo $project['post_service_status'] === 'installation_completed' ? 'selected' : ''; ?>>Installation Completed</option>
                        <option value="follow_up_sent" <?php echo $project['post_service_status'] === 'follow_up_sent' ? 'selected' : ''; ?>>Follow-up Sent</option>
                        <option value="review_requested" <?php echo $project['post_service_status'] === 'review_requested' ? 'selected' : ''; ?>>Review Requested</option>
                        <option value="warranty_active" <?php echo $project['post_service_status'] === 'warranty_active' ? 'selected' : ''; ?>>Warranty Active</option>
                    </select>
                </div>
                <div class="detail-item">
                    <label>Owner:</label>
                    <select id="projectOwner" onchange="assignProject(<?php echo $project_id; ?>, this.value)" class="owner-select">
                        <option value="">Unassigned</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>" <?php echo $project['owner_id'] == $user['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($project['estimated_start_date']): ?>
                    <div class="detail-item">
                        <label>Estimated Start:</label>
                        <span><?php echo date('M d, Y', strtotime($project['estimated_start_date'])); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($project['estimated_end_date']): ?>
                    <div class="detail-item">
                        <label>Estimated End:</label>
                        <span><?php echo date('M d, Y', strtotime($project['estimated_end_date'])); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($project['actual_start_date']): ?>
                    <div class="detail-item">
                        <label>Actual Start:</label>
                        <span><?php echo date('M d, Y', strtotime($project['actual_start_date'])); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($project['actual_end_date']): ?>
                    <div class="detail-item">
                        <label>Actual End:</label>
                        <span><?php echo date('M d, Y', strtotime($project['actual_end_date'])); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($project['estimated_cost']): ?>
                    <div class="detail-item">
                        <label>Estimated Cost:</label>
                        <span>$<?php echo number_format($project['estimated_cost'], 2); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($project['actual_cost']): ?>
                    <div class="detail-item">
                        <label>Actual Cost:</label>
                        <span>$<?php echo number_format($project['actual_cost'], 2); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($project['address']): ?>
                    <div class="detail-item full-width">
                        <label>Address:</label>
                        <span><?php echo htmlspecialchars($project['address']); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tags -->
        <div class="detail-section">
            <h3>Tags</h3>
            <div id="projectTags">
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
                </select>
                <button onclick="addProjectTag(<?php echo $project_id; ?>)" class="btn btn-sm btn-primary">Add Tag</button>
            </div>
        </div>

        <!-- Notes -->
        <div class="detail-section">
            <h3>Notes</h3>
            <div id="projectNotes">
                <?php foreach ($notes as $note): ?>
                    <div class="note-item">
                        <div class="note-content"><?php echo nl2br(htmlspecialchars($note['note'])); ?></div>
                        <div class="note-meta">By <?php echo htmlspecialchars($note['created_by']); ?> on <?php echo date('M d, Y H:i', strtotime($note['created_at'])); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="add-note-section">
                <textarea id="newNote" rows="3" placeholder="Add a note..."></textarea>
                <button onclick="addProjectNote(<?php echo $project_id; ?>)" class="btn btn-primary">Add Note</button>
            </div>
        </div>

        <!-- Pós-venda: Documentos, Problemas, Checklist -->
        <div class="detail-section">
            <h3>Pós-venda</h3>
            <div class="detail-grid" style="grid-template-columns: 1fr 1fr 1fr;">
                <div>
                    <h4 style="margin-bottom: 8px;">Documentos</h4>
                    <ul style="list-style: none; padding: 0;">
                        <?php foreach ($documents as $d): ?>
                            <li><a href="<?php echo htmlspecialchars($d['file_path']); ?>" target="_blank"><?php echo htmlspecialchars(basename($d['file_path'])); ?></a> (<?php echo htmlspecialchars($d['doc_type'] ?? 'doc'); ?>)</li>
                        <?php endforeach; ?>
                        <?php if (empty($documents)): ?><li class="text-muted">Nenhum documento</li><?php endif; ?>
                    </ul>
                    <p style="font-size: 12px; color: #64748b;">Adicione via API: api/projects/documents.php (project_id, file_path, doc_type)</p>
                </div>
                <div>
                    <h4 style="margin-bottom: 8px;">Problemas / Registros</h4>
                    <ul style="list-style: none; padding: 0;">
                        <?php foreach ($issues as $i): ?>
                            <li><span class="badge <?php echo $i['status']; ?>"><?php echo $i['status']; ?></span> <?php echo htmlspecialchars(mb_substr($i['description'], 0, 60)); ?><?php echo mb_strlen($i['description']) > 60 ? '…' : ''; ?></li>
                        <?php endforeach; ?>
                        <?php if (empty($issues)): ?><li class="text-muted">Nenhum problema registrado</li><?php endif; ?>
                    </ul>
                    <p style="font-size: 12px; color: #64748b;">Adicione via API: api/projects/issues.php</p>
                </div>
                <div>
                    <h4 style="margin-bottom: 8px;">Checklist de entrega</h4>
                    <ul style="list-style: none; padding: 0;">
                        <?php foreach ($checklist as $c): ?>
                            <li><?php echo $c['completed'] ? '✓' : '○'; ?> <?php echo htmlspecialchars($c['item_name']); ?></li>
                        <?php endforeach; ?>
                        <?php if (empty($checklist)): ?><li class="text-muted">Nenhum item</li><?php endif; ?>
                    </ul>
                    <p style="font-size: 12px; color: #64748b;">Adicione via API: api/projects/checklist.php</p>
                </div>
            </div>
        </div>

        <!-- Activities Timeline -->
        <div class="detail-section">
            <h3>Activity Timeline</h3>
            <div id="projectActivities">
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
function updateProjectStatus(projectId, status) {
    const formData = new FormData();
    formData.append('project_id', projectId);
    formData.append('status', status);
    
    fetch('api/projects/update.php', {
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

function updatePostServiceStatus(projectId, status) {
    const formData = new FormData();
    formData.append('project_id', projectId);
    formData.append('post_service_status', status);
    
    fetch('api/projects/update.php', {
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

function assignProject(projectId, userId) {
    const formData = new FormData();
    formData.append('project_id', projectId);
    formData.append('to_user_id', userId || '');
    formData.append('assigned_by', 1);
    
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

function addProjectNote(projectId) {
    const note = document.getElementById('newNote').value;
    if (!note.trim()) {
        alert('Please enter a note');
        return;
    }
    
    const formData = new FormData();
    formData.append('project_id', projectId);
    formData.append('note', note);
    
    fetch('api/projects/notes.php', {
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

function addProjectTag(projectId) {
    const tag = document.getElementById('tagSelect').value;
    if (!tag) {
        alert('Please select a tag');
        return;
    }
    
    const formData = new FormData();
    formData.append('project_id', projectId);
    formData.append('action', 'add');
    formData.append('tag', tag);
    
    fetch('api/projects/tags.php', {
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
</style>
