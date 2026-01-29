<?php
/**
 * Users Module - User Management System with Individual Permissions
 */

require_once __DIR__ . '/../config/database.php';

// Load permissions if available
if (file_exists(__DIR__ . '/../config/permissions.php')) {
    require_once __DIR__ . '/../config/permissions.php';
}

session_start();

// Verificar autenticação básica
if (!isset($_SESSION['admin_authenticated'])) {
    header('Location: system.php');
    exit;
}

// Verificar permissão para ver o módulo (admin ou users.view)
$has_permission = true;
$role = trim((string)($_SESSION['admin_role'] ?? ''));
$admin_user_id = $_SESSION['admin_user_id'] ?? null;
if ($role === 'admin' || $admin_user_id === null) {
    $has_permission = true;
} elseif (function_exists('hasPermission') && $admin_user_id) {
    $has_permission = hasPermission($admin_user_id, 'users.view');
} else {
    $has_permission = false;
}

if (!$has_permission) {
    header('Location: ?module=dashboard&error=no_permission');
    exit;
}

$users = [];
$permissions_grouped = getAllPermissionsGrouped();

if (isDatabaseConfigured()) {
    try {
        $pdo = getDBConnection();
        
        // Get all users
        $stmt = $pdo->query("
            SELECT 
                u.*,
                COUNT(DISTINCT up.id) as permission_count,
                u.last_login
            FROM users u
            LEFT JOIN user_permissions up ON up.user_id = u.id AND up.granted = 1
            GROUP BY u.id
            ORDER BY u.name ASC
        ");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get permissions for each user (if function exists)
        if (function_exists('getUserPermissions')) {
            foreach ($users as &$user) {
                $user['permissions'] = getUserPermissions($user['id']);
            }
        } else {
            foreach ($users as &$user) {
                $user['permissions'] = [];
            }
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>
<?php
$can_create_user = ($role === 'admin' || $admin_user_id === null);
if (!$can_create_user && function_exists('hasPermission') && $admin_user_id) {
    $can_create_user = hasPermission($admin_user_id, 'users.create');
}
?>
<div class="module-header">
    <h2>Users Management</h2>
    <div class="module-actions">
        <?php if ($can_create_user): ?>
            <button class="btn btn-primary" onclick="showCreateUserModal()">+ New User</button>
        <?php endif; ?>
    </div>
</div>

<!-- Stats -->
<div class="stats-section">
    <div class="stat-card">
        <div class="stat-value"><?php echo count($users); ?></div>
        <div class="stat-label">Total Users</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo count(array_filter($users, fn($u) => $u['is_active'])); ?></div>
        <div class="stat-label">Active</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo count(array_filter($users, fn($u) => $u['role'] === 'admin')); ?></div>
        <div class="stat-label">Admins</div>
    </div>
</div>

<!-- Users Table -->
<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Role</th>
                <th>Permissions</th>
                <th>Status</th>
                <th>Last Login</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr>
                    <td colspan="9" class="text-center">No users found</td>
                </tr>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td>
                            <a href="?module=user-detail&id=<?php echo $user['id']; ?>" class="link-primary">
                                <?php echo htmlspecialchars($user['name']); ?>
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                        <td>
                            <span class="badge badge-role badge-<?php echo $user['role']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            $perm_count = $user['role'] === 'admin' ? 'All' : ($user['permission_count'] ?? count($user['permissions'] ?? []));
                            echo $perm_count . ' permission' . ($perm_count != 1 ? 's' : '');
                            ?>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td>
                            <?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?>
                        </td>
                        <td>
                            <a href="?module=user-detail&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">Manage</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Create User Modal -->
<?php 
if ($can_create_user): 
?>
<div id="createUserModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeCreateUserModal()">&times;</span>
        <h3>Create New User</h3>
        <form id="createUserForm" onsubmit="createUser(event)">
            <div class="form-group">
                <label>Name *</label>
                <input type="text" name="name" required>
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="tel" name="phone">
            </div>
            <div class="form-group">
                <label>Role *</label>
                <select name="role" required>
                    <option value="sales_rep">Sales Rep</option>
                    <option value="project_manager">Project Manager</option>
                    <option value="support">Support</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="form-group">
                <label>Password *</label>
                <input type="password" name="password" required minlength="6">
                <small>Minimum 6 characters</small>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="is_active">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Create User</button>
                <button type="button" class="btn btn-secondary" onclick="closeCreateUserModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
function showCreateUserModal() {
    document.getElementById('createUserModal').style.display = 'block';
}

function closeCreateUserModal() {
    document.getElementById('createUserModal').style.display = 'none';
    document.getElementById('createUserForm').reset();
}

function createUser(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const permCheckboxes = form.querySelectorAll('input[name="create_permission[]"]:checked');
    permCheckboxes.forEach((cb, i) => { formData.append('permissions[' + i + ']', cb.value); });
    // Base path para API (funciona em subpasta ex: /lp/system.php -> /lp/api/...)
    const path = document.location.pathname || '/';
    const base = path.replace(/\/?system\.php.*$/i, '') || '/';
    const apiUrl = (base.endsWith('/') ? base : base + '/') + 'api/users/create.php';
    fetch(apiUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('User created successfully!');
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
    const modal = document.getElementById('createUserModal');
    if (event.target == modal) {
        closeCreateUserModal();
    }
}
</script>

<style>
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

.badge-role {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.badge-admin {
    background: #dc3545;
    color: white;
}

.badge-sales_rep {
    background: #007bff;
    color: white;
}

.badge-project_manager {
    background: #28a745;
    color: white;
}

.badge-support {
    background: #6c757d;
    color: white;
}

.create-permissions-section { margin-top: 16px; }
.form-hint { font-size: 12px; color: #64748b; margin-bottom: 10px; }
.create-permissions-list { max-height: 220px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; background: #f8fafc; }
.create-perm-group { margin-bottom: 12px; }
.create-perm-group strong { display: block; margin-bottom: 6px; color: #1a2036; font-size: 12px; text-transform: uppercase; }
.create-perm-item { display: block; padding: 4px 0; font-size: 13px; cursor: pointer; }
.create-perm-item input { margin-right: 8px; }
</style>
