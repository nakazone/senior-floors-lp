<?php
/**
 * Customers Module - Customer Management System
 */

require_once __DIR__ . '/../config/database.php';

$CUSTOMERS_PER_PAGE = 25;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$customer_type_filter = isset($_GET['customer_type']) ? trim($_GET['customer_type']) : '';
$owner_filter = isset($_GET['owner_id']) ? (int)$_GET['owner_id'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$customers = [];
$total_customers = 0;
$total_pages = 1;

if (isDatabaseConfigured()) {
    try {
        $pdo = getDBConnection();
        
        // Build query
        $where = [];
        $params = [];
        
        if ($status_filter) {
            $where[] = "c.status = ?";
            $params[] = $status_filter;
        }
        
        if ($customer_type_filter) {
            $where[] = "c.customer_type = ?";
            $params[] = $customer_type_filter;
        }
        
        if ($owner_filter > 0) {
            $where[] = "c.owner_id = ?";
            $params[] = $owner_filter;
        }
        
        if ($search) {
            $where[] = "(c.name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)";
            $search_param = "%$search%";
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
        }
        
        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Get total count
        $count_sql = "SELECT COUNT(*) as total FROM customers c $where_clause";
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute($params);
        $total_customers = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        $total_pages = ceil($total_customers / $CUSTOMERS_PER_PAGE);
        
        // Get customers with project count
        $offset = ($page - 1) * $CUSTOMERS_PER_PAGE;
        $sql = "
            SELECT 
                c.*,
                COUNT(DISTINCT p.id) as project_count,
                u.name as owner_name
            FROM customers c
            LEFT JOIN projects p ON p.customer_id = c.id
            LEFT JOIN users u ON u.id = c.owner_id
            $where_clause
            GROUP BY c.id
            ORDER BY c.created_at DESC
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $CUSTOMERS_PER_PAGE;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get users for owner filter
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
    <h2>Customers Management</h2>
    <div class="module-actions">
        <button class="btn btn-primary" onclick="showCreateCustomerModal()">+ New Customer</button>
    </div>
</div>

<!-- Filters -->
<div class="filters-section">
    <form method="GET" class="filters-form">
        <input type="hidden" name="module" value="customers">
        
        <div class="filter-group">
            <label>Search:</label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name, email, phone...">
        </div>
        
        <div class="filter-group">
            <label>Status:</label>
            <select name="status">
                <option value="">All</option>
                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                <option value="archived" <?php echo $status_filter === 'archived' ? 'selected' : ''; ?>>Archived</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label>Customer Type:</label>
            <select name="customer_type">
                <option value="">All</option>
                <option value="residential" <?php echo $customer_type_filter === 'residential' ? 'selected' : ''; ?>>Residential</option>
                <option value="commercial" <?php echo $customer_type_filter === 'commercial' ? 'selected' : ''; ?>>Commercial</option>
                <option value="property_manager" <?php echo $customer_type_filter === 'property_manager' ? 'selected' : ''; ?>>Property Manager</option>
                <option value="investor" <?php echo $customer_type_filter === 'investor' ? 'selected' : ''; ?>>Investor</option>
                <option value="builder" <?php echo $customer_type_filter === 'builder' ? 'selected' : ''; ?>>Builder</option>
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
        <a href="?module=customers" class="btn btn-link">Clear</a>
    </form>
</div>

<!-- Stats -->
<div class="stats-section">
    <div class="stat-card">
        <div class="stat-value"><?php echo $total_customers; ?></div>
        <div class="stat-label">Total Customers</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo count(array_filter($customers, fn($c) => $c['status'] === 'active')); ?></div>
        <div class="stat-label">Active</div>
    </div>
</div>

<!-- Customers Table -->
<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Type</th>
                <th>Status</th>
                <th>Owner</th>
                <th>Projects</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($customers)): ?>
                <tr>
                    <td colspan="10" class="text-center">No customers found</td>
                </tr>
            <?php else: ?>
                <?php foreach ($customers as $customer): ?>
                    <tr>
                        <td><?php echo $customer['id']; ?></td>
                        <td>
                            <a href="?module=customer-detail&id=<?php echo $customer['id']; ?>" class="link-primary">
                                <?php echo htmlspecialchars($customer['name']); ?>
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars($customer['email']); ?></td>
                        <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                        <td>
                            <span class="badge badge-type"><?php echo ucfirst(str_replace('_', ' ', $customer['customer_type'])); ?></span>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $customer['status']; ?>">
                                <?php echo ucfirst($customer['status']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($customer['owner_name'] ?? 'Unassigned'); ?></td>
                        <td><?php echo $customer['project_count']; ?></td>
                        <td><?php echo date('M d, Y', strtotime($customer['created_at'])); ?></td>
                        <td>
                            <a href="?module=customer-detail&id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-primary">View</a>
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
            <a href="?module=customers&page=<?php echo $page - 1; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $customer_type_filter ? '&customer_type=' . $customer_type_filter : ''; ?><?php echo $owner_filter ? '&owner_id=' . $owner_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-sm">Previous</a>
        <?php endif; ?>
        
        <span>Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
        
        <?php if ($page < $total_pages): ?>
            <a href="?module=customers&page=<?php echo $page + 1; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $customer_type_filter ? '&customer_type=' . $customer_type_filter : ''; ?><?php echo $owner_filter ? '&owner_id=' . $owner_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-sm">Next</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Create Customer Modal -->
<div id="createCustomerModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeCreateCustomerModal()">&times;</span>
        <h3>Create New Customer</h3>
        <form id="createCustomerForm" onsubmit="createCustomer(event)">
            <div class="form-group">
                <label>Name *</label>
                <input type="text" name="name" required>
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Phone *</label>
                <input type="tel" name="phone" required>
            </div>
            <div class="form-group">
                <label>Customer Type</label>
                <select name="customer_type">
                    <option value="residential">Residential</option>
                    <option value="commercial">Commercial</option>
                    <option value="property_manager">Property Manager</option>
                    <option value="investor">Investor</option>
                    <option value="builder">Builder</option>
                </select>
            </div>
            <div class="form-group">
                <label>Address</label>
                <input type="text" name="address">
            </div>
            <div class="form-group">
                <label>City</label>
                <input type="text" name="city">
            </div>
            <div class="form-group">
                <label>State</label>
                <input type="text" name="state">
            </div>
            <div class="form-group">
                <label>Zip Code</label>
                <input type="text" name="zipcode">
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
                <button type="submit" class="btn btn-primary">Create Customer</button>
                <button type="button" class="btn btn-secondary" onclick="closeCreateCustomerModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function showCreateCustomerModal() {
    document.getElementById('createCustomerModal').style.display = 'block';
}

function closeCreateCustomerModal() {
    document.getElementById('createCustomerModal').style.display = 'none';
    document.getElementById('createCustomerForm').reset();
}

function createCustomer(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    fetch('api/customers/create.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Customer created successfully!');
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
    });
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('createCustomerModal');
    if (event.target == modal) {
        closeCreateCustomerModal();
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

.badge-type {
    background: #e7f3ff;
    color: #0066cc;
}

.badge-active {
    background: #d4edda;
    color: #155724;
}

.badge-inactive {
    background: #fff3cd;
    color: #856404;
}

.badge-archived {
    background: #f8d7da;
    color: #721c24;
}
</style>
