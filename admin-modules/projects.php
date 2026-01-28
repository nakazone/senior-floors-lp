<?php
/**
 * Projects Module - Project Management System
 */

require_once __DIR__ . '/../config/database.php';

$PROJECTS_PER_PAGE = 25;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$project_type_filter = isset($_GET['project_type']) ? trim($_GET['project_type']) : '';
$post_service_filter = isset($_GET['post_service_status']) ? trim($_GET['post_service_status']) : '';
$customer_id_filter = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;
$owner_filter = isset($_GET['owner_id']) ? (int)$_GET['owner_id'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$projects = [];
$total_projects = 0;
$total_pages = 1;

if (isDatabaseConfigured()) {
    try {
        $pdo = getDBConnection();
        
        // Build query
        $where = [];
        $params = [];
        
        if ($status_filter) {
            $where[] = "p.status = ?";
            $params[] = $status_filter;
        }
        
        if ($project_type_filter) {
            $where[] = "p.project_type = ?";
            $params[] = $project_type_filter;
        }
        
        if ($post_service_filter) {
            $where[] = "p.post_service_status = ?";
            $params[] = $post_service_filter;
        }
        
        if ($customer_id_filter > 0) {
            $where[] = "p.customer_id = ?";
            $params[] = $customer_id_filter;
        }
        
        if ($owner_filter > 0) {
            $where[] = "p.owner_id = ?";
            $params[] = $owner_filter;
        }
        
        if ($search) {
            $where[] = "(p.name LIKE ? OR c.name LIKE ? OR c.email LIKE ?)";
            $search_param = "%$search%";
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
        }
        
        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Get total count
        $count_sql = "SELECT COUNT(*) as total FROM projects p LEFT JOIN customers c ON c.id = p.customer_id $where_clause";
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute($params);
        $total_projects = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        $total_pages = ceil($total_projects / $PROJECTS_PER_PAGE);
        
        // Get projects with customer info
        $offset = ($page - 1) * $PROJECTS_PER_PAGE;
        $sql = "
            SELECT 
                p.*,
                c.name as customer_name,
                c.email as customer_email,
                u.name as owner_name
            FROM projects p
            LEFT JOIN customers c ON c.id = p.customer_id
            LEFT JOIN users u ON u.id = p.owner_id
            $where_clause
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $PROJECTS_PER_PAGE;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get customers for filter
$customers = [];
if (isDatabaseConfigured()) {
    try {
        $pdo = getDBConnection();
        $customers_stmt = $pdo->query("SELECT id, name FROM customers ORDER BY name");
        $customers = $customers_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Ignore
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
    <h2>Projects Management</h2>
    <div class="module-actions">
        <button class="btn btn-primary" onclick="showCreateProjectModal()">+ New Project</button>
    </div>
</div>

<!-- Filters -->
<div class="filters-section">
    <form method="GET" class="filters-form">
        <input type="hidden" name="module" value="projects">
        
        <div class="filter-group">
            <label>Search:</label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Project name, customer...">
        </div>
        
        <div class="filter-group">
            <label>Status:</label>
            <select name="status">
                <option value="">All</option>
                <option value="quoted" <?php echo $status_filter === 'quoted' ? 'selected' : ''; ?>>Quoted</option>
                <option value="scheduled" <?php echo $status_filter === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                <option value="on_hold" <?php echo $status_filter === 'on_hold' ? 'selected' : ''; ?>>On Hold</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label>Project Type:</label>
            <select name="project_type">
                <option value="">All</option>
                <option value="installation" <?php echo $project_type_filter === 'installation' ? 'selected' : ''; ?>>Installation</option>
                <option value="refinishing" <?php echo $project_type_filter === 'refinishing' ? 'selected' : ''; ?>>Refinishing</option>
                <option value="repair" <?php echo $project_type_filter === 'repair' ? 'selected' : ''; ?>>Repair</option>
                <option value="maintenance" <?php echo $project_type_filter === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label>Post-Service Status:</label>
            <select name="post_service_status">
                <option value="">All</option>
                <option value="installation_scheduled" <?php echo $post_service_filter === 'installation_scheduled' ? 'selected' : ''; ?>>Installation Scheduled</option>
                <option value="installation_completed" <?php echo $post_service_filter === 'installation_completed' ? 'selected' : ''; ?>>Installation Completed</option>
                <option value="follow_up_sent" <?php echo $post_service_filter === 'follow_up_sent' ? 'selected' : ''; ?>>Follow-up Sent</option>
                <option value="review_requested" <?php echo $post_service_filter === 'review_requested' ? 'selected' : ''; ?>>Review Requested</option>
                <option value="warranty_active" <?php echo $post_service_filter === 'warranty_active' ? 'selected' : ''; ?>>Warranty Active</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label>Customer:</label>
            <select name="customer_id">
                <option value="0">All</option>
                <?php foreach ($customers as $customer): ?>
                    <option value="<?php echo $customer['id']; ?>" <?php echo $customer_id_filter == $customer['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($customer['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="filter-group">
            <label>Owner:</label>
            <select name="owner_id">
                <option value="0">All</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?php echo $user['id']; ?>" <?php echo $owner_filter == $user['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($user['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <button type="submit" class="btn btn-secondary">Filter</button>
        <a href="?module=projects" class="btn btn-link">Clear</a>
    </form>
</div>

<!-- Stats -->
<div class="stats-section">
    <div class="stat-card">
        <div class="stat-value"><?php echo $total_projects; ?></div>
        <div class="stat-label">Total Projects</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo count(array_filter($projects, fn($p) => $p['status'] === 'completed')); ?></div>
        <div class="stat-label">Completed</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo count(array_filter($projects, fn($p) => $p['status'] === 'in_progress')); ?></div>
        <div class="stat-label">In Progress</div>
    </div>
</div>

<!-- Projects Table -->
<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Customer</th>
                <th>Type</th>
                <th>Status</th>
                <th>Post-Service</th>
                <th>Estimated Cost</th>
                <th>Owner</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($projects)): ?>
                <tr>
                    <td colspan="10" class="text-center">No projects found</td>
                </tr>
            <?php else: ?>
                <?php foreach ($projects as $project): ?>
                    <tr>
                        <td><?php echo $project['id']; ?></td>
                        <td>
                            <a href="?module=project-detail&id=<?php echo $project['id']; ?>" class="link-primary">
                                <?php echo htmlspecialchars($project['name']); ?>
                            </a>
                        </td>
                        <td>
                            <?php if ($project['customer_name']): ?>
                                <a href="?module=customer-detail&id=<?php echo $project['customer_id']; ?>">
                                    <?php echo htmlspecialchars($project['customer_name']); ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo ucfirst($project['project_type']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $project['status']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $project['status'])); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($project['post_service_status']): ?>
                                <span class="badge badge-info"><?php echo ucfirst(str_replace('_', ' ', $project['post_service_status'])); ?></span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $project['estimated_cost'] ? '$' . number_format($project['estimated_cost'], 2) : '-'; ?></td>
                        <td><?php echo htmlspecialchars($project['owner_name'] ?? 'Unassigned'); ?></td>
                        <td><?php echo date('M d, Y', strtotime($project['created_at'])); ?></td>
                        <td>
                            <a href="?module=project-detail&id=<?php echo $project['id']; ?>" class="btn btn-sm btn-primary">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?module=projects&page=<?php echo $page - 1; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $project_type_filter ? '&project_type=' . $project_type_filter : ''; ?><?php echo $post_service_filter ? '&post_service_status=' . $post_service_filter : ''; ?><?php echo $customer_id_filter ? '&customer_id=' . $customer_id_filter : ''; ?><?php echo $owner_filter ? '&owner_id=' . $owner_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-sm">Previous</a>
        <?php endif; ?>
        
        <span>Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
        
        <?php if ($page < $total_pages): ?>
            <a href="?module=projects&page=<?php echo $page + 1; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $project_type_filter ? '&project_type=' . $project_type_filter : ''; ?><?php echo $post_service_filter ? '&post_service_status=' . $post_service_filter : ''; ?><?php echo $customer_id_filter ? '&customer_id=' . $customer_id_filter : ''; ?><?php echo $owner_filter ? '&owner_id=' . $owner_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-sm">Next</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Create Project Modal -->
<div id="createProjectModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeCreateProjectModal()">&times;</span>
        <h3>Create New Project</h3>
        <form id="createProjectForm" onsubmit="createProject(event)">
            <div class="form-group">
                <label>Customer *</label>
                <select name="customer_id" required>
                    <option value="">Select customer...</option>
                    <?php foreach ($customers as $customer): ?>
                        <option value="<?php echo $customer['id']; ?>"><?php echo htmlspecialchars($customer['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Project Name *</label>
                <input type="text" name="name" required>
            </div>
            <div class="form-group">
                <label>Project Type</label>
                <select name="project_type">
                    <option value="installation">Installation</option>
                    <option value="refinishing">Refinishing</option>
                    <option value="repair">Repair</option>
                    <option value="maintenance">Maintenance</option>
                </select>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="quoted">Quoted</option>
                    <option value="scheduled">Scheduled</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
            <div class="form-group">
                <label>Post-Service Status</label>
                <select name="post_service_status">
                    <option value="">None</option>
                    <option value="installation_scheduled">Installation Scheduled</option>
                    <option value="installation_completed">Installation Completed</option>
                    <option value="follow_up_sent">Follow-up Sent</option>
                    <option value="review_requested">Review Requested</option>
                    <option value="warranty_active">Warranty Active</option>
                </select>
            </div>
            <div class="form-group">
                <label>Estimated Start Date</label>
                <input type="date" name="estimated_start_date">
            </div>
            <div class="form-group">
                <label>Estimated End Date</label>
                <input type="date" name="estimated_end_date">
            </div>
            <div class="form-group">
                <label>Estimated Cost</label>
                <input type="number" name="estimated_cost" step="0.01" min="0">
            </div>
            <div class="form-group">
                <label>Owner</label>
                <select name="owner_id">
                    <option value="">Unassigned</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" rows="3"></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Create Project</button>
                <button type="button" class="btn btn-secondary" onclick="closeCreateProjectModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function showCreateProjectModal() {
    document.getElementById('createProjectModal').style.display = 'block';
}

function closeCreateProjectModal() {
    document.getElementById('createProjectModal').style.display = 'none';
    document.getElementById('createProjectForm').reset();
}

function createProject(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    fetch('api/projects/create.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Project created successfully!');
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
    });
}

window.onclick = function(event) {
    const modal = document.getElementById('createProjectModal');
    if (event.target == modal) {
        closeCreateProjectModal();
    }
}
</script>

<style>
.filters-section {
    background: #f5f5f5;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.filters-form {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    align-items: flex-end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.filter-group label {
    font-size: 12px;
    font-weight: 600;
    color: #555;
}

.filter-group input,
.filter-group select {
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.stats-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
}

.stat-value {
    font-size: 32px;
    font-weight: bold;
    color: #007bff;
}

.stat-label {
    font-size: 14px;
    color: #666;
    margin-top: 5px;
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.4);
}

.modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 30px;
    border: 1px solid #888;
    border-radius: 8px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover {
    color: #000;
}

.badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.badge-quoted {
    background: #fff3cd;
    color: #856404;
}

.badge-scheduled {
    background: #d1ecf1;
    color: #0c5460;
}

.badge-in_progress {
    background: #cce5ff;
    color: #004085;
}

.badge-completed {
    background: #d4edda;
    color: #155724;
}

.badge-cancelled {
    background: #f8d7da;
    color: #721c24;
}

.badge-on_hold {
    background: #e2e3e5;
    color: #383d41;
}

.badge-info {
    background: #d1ecf1;
    color: #0c5460;
}
</style>
