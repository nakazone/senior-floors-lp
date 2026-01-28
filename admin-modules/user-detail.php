<?php
/**
 * User Detail Module - Manage User and Permissions
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

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id <= 0) {
    echo '<div class="error-message">Invalid user ID</div>';
    exit;
}

// Verificar permissão (se sistema de permissões estiver disponível)
$has_permission = true;
if (function_exists('hasPermission') && isset($_SESSION['admin_user_id'])) {
    $has_permission = hasPermission($_SESSION['admin_user_id'], 'users.view');
} elseif (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'admin') {
    $has_permission = true;
}

if (!$has_permission) {
    header('Location: ?module=users&error=no_permission');
    exit;
}

$user = null;
$user_permissions = [];
$all_permissions_grouped = getAllPermissionsGrouped();

if (isDatabaseConfigured()) {
    try {
        $pdo = getDBConnection();
        
        // Get user
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo '<div class="error-message">User not found</div>';
            exit;
        }
        
        // Get user permissions (if function exists)
        if (function_exists('getUserPermissions')) {
            $user_permissions = getUserPermissions($user_id);
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>
<div class="module-header">
    <h2>User Management</h2>
    <div class="module-actions">
        <a href="?module=users" class="btn btn-secondary">← Back to Users</a>
    </div>
</div>

<?php if ($user): ?>
    <div class="detail-container">
        <!-- User Info -->
        <div class="detail-section">
            <h3>User Information</h3>
            <?php if (hasPermission($_SESSION['admin_user_id'], 'users.edit')): ?>
                <form id="editUserForm" onsubmit="updateUser(event, <?php echo $user_id; ?>)">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Name:</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        <div class="detail-item">
                            <label>Email:</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="detail-item">
                            <label>Phone:</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                        <div class="detail-item">
                            <label>Role:</label>
                            <select name="role" required>
                                <option value="sales_rep" <?php echo $user['role'] === 'sales_rep' ? 'selected' : ''; ?>>Sales Rep</option>
                                <option value="project_manager" <?php echo $user['role'] === 'project_manager' ? 'selected' : ''; ?>>Project Manager</option>
                                <option value="support" <?php echo $user['role'] === 'support' ? 'selected' : ''; ?>>Support</option>
                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                        <div class="detail-item">
                            <label>Status:</label>
                            <select name="is_active">
                                <option value="1" <?php echo $user['is_active'] ? 'selected' : ''; ?>>Active</option>
                                <option value="0" <?php echo !$user['is_active'] ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="detail-item">
                            <label>New Password:</label>
                            <input type="password" name="password" placeholder="Leave empty to keep current">
                            <small>Minimum 6 characters</small>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Name:</label>
                        <span><?php echo htmlspecialchars($user['name']); ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Email:</label>
                        <span><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Role:</label>
                        <span class="badge badge-role badge-<?php echo $user['role']; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                        </span>
                    </div>
                    <div class="detail-item">
                        <label>Status:</label>
                        <span class="badge badge-<?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                            <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Permissions Management -->
        <?php 
        $can_manage_permissions = true;
        if (function_exists('hasPermission') && isset($_SESSION['admin_user_id'])) {
            $can_manage_permissions = hasPermission($_SESSION['admin_user_id'], 'users.manage_permissions');
        } elseif (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] !== 'admin') {
            $can_manage_permissions = false;
        }
        if ($can_manage_permissions): 
        ?>
        <div class="detail-section">
            <h3>Individual Permissions</h3>
            <p class="section-description">
                <?php if ($user['role'] === 'admin'): ?>
                    <strong>Admin users have all permissions automatically.</strong>
                <?php else: ?>
                    Select individual permissions for this user. Permissions are granular and can be customized per user.
                <?php endif; ?>
            </p>
            
            <?php if ($user['role'] !== 'admin'): ?>
                <div class="permissions-container">
                    <?php foreach ($all_permissions_grouped as $group => $permissions): ?>
                        <div class="permission-group">
                            <h4 class="permission-group-title">
                                <?php echo ucfirst(str_replace('_', ' ', $group)); ?>
                            </h4>
                            <div class="permission-list">
                                <?php foreach ($permissions as $perm): ?>
                                    <div class="permission-item">
                                        <label class="permission-checkbox">
                                            <input 
                                                type="checkbox" 
                                                class="permission-checkbox-input"
                                                data-permission="<?php echo htmlspecialchars($perm['permission_key']); ?>"
                                                <?php echo in_array($perm['permission_key'], $user_permissions) ? 'checked' : ''; ?>
                                                onchange="togglePermission(<?php echo $user_id; ?>, '<?php echo htmlspecialchars($perm['permission_key']); ?>', this.checked)"
                                            >
                                            <span class="permission-name"><?php echo htmlspecialchars($perm['permission_name']); ?></span>
                                            <?php if ($perm['description']): ?>
                                                <small class="permission-desc"><?php echo htmlspecialchars($perm['description']); ?></small>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="permission-actions">
                    <button onclick="saveAllPermissions(<?php echo $user_id; ?>)" class="btn btn-primary">Save All Permissions</button>
                    <button onclick="selectAllPermissions()" class="btn btn-secondary">Select All</button>
                    <button onclick="deselectAllPermissions()" class="btn btn-secondary">Deselect All</button>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<script>
function updateUser(e, userId) {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('user_id', userId);
    
    fetch('api/users/update.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('User updated successfully!');
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
    });
}

function togglePermission(userId, permissionKey, granted) {
    const action = granted ? 'grant' : 'revoke';
    const formData = new FormData();
    formData.append('user_id', userId);
    formData.append('action', action);
    formData.append('permission_key', permissionKey);
    
    fetch('api/users/permissions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            alert('Error: ' + data.message);
            // Revert checkbox
            const checkbox = document.querySelector(`[data-permission="${permissionKey}"]`);
            if (checkbox) checkbox.checked = !granted;
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
        // Revert checkbox
        const checkbox = document.querySelector(`[data-permission="${permissionKey}"]`);
        if (checkbox) checkbox.checked = !granted;
    });
}

function saveAllPermissions(userId) {
    const checkboxes = document.querySelectorAll('.permission-checkbox-input');
    const permissions = [];
    
    checkboxes.forEach(checkbox => {
        if (checkbox.checked) {
            permissions.push(checkbox.dataset.permission);
        }
    });
    
    const formData = new FormData();
    formData.append('user_id', userId);
    formData.append('action', 'set_all');
    // Enviar como array (FormData suporta arrays)
    permissions.forEach((perm, index) => {
        formData.append(`permissions[${index}]`, perm);
    });
    
    fetch('api/users/permissions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('All permissions saved successfully!');
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
    });
}

function selectAllPermissions() {
    document.querySelectorAll('.permission-checkbox-input').forEach(cb => cb.checked = true);
}

function deselectAllPermissions() {
    document.querySelectorAll('.permission-checkbox-input').forEach(cb => cb.checked = false);
}
</script>

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

.section-description {
    color: #666;
    margin-bottom: 20px;
    padding: 10px;
    background: #f9f9f9;
    border-radius: 4px;
}

.permissions-container {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.permission-group {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    background: #fafafa;
}

.permission-group-title {
    margin: 0 0 15px 0;
    color: #333;
    font-size: 18px;
    font-weight: 600;
}

.permission-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 12px;
}

.permission-item {
    background: white;
    padding: 12px;
    border-radius: 6px;
    border: 1px solid #e0e0e0;
}

.permission-checkbox {
    display: flex;
    flex-direction: column;
    gap: 4px;
    cursor: pointer;
}

.permission-checkbox-input {
    margin-right: 8px;
    cursor: pointer;
}

.permission-name {
    font-weight: 500;
    color: #333;
    font-size: 14px;
}

.permission-desc {
    color: #666;
    font-size: 12px;
    display: block;
    margin-top: 4px;
}

.permission-actions {
    margin-top: 20px;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
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

.detail-item label {
    font-weight: 600;
    color: #666;
    font-size: 14px;
}

.detail-item input,
.detail-item select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.form-actions {
    margin-top: 20px;
}
</style>
