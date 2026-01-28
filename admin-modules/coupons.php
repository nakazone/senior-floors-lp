<?php
/**
 * Coupons Module - Coupon Management System
 */

require_once __DIR__ . '/../config/database.php';

$coupons = [];
$total_uses = 0;

if (isDatabaseConfigured()) {
    try {
        $pdo = getDBConnection();
        
        // Get all coupons
        $stmt = $pdo->query("SELECT * FROM coupons ORDER BY created_at DESC");
        $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total uses
        $uses_stmt = $pdo->query("SELECT SUM(used_count) as total FROM coupons");
        $total_uses = $uses_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>
<div class="module-header">
    <h2>Coupons Management</h2>
    <div class="module-actions">
        <button class="btn btn-primary" onclick="showCreateCouponModal()">+ New Coupon</button>
    </div>
</div>

<!-- Stats -->
<div class="stats-section">
    <div class="stat-card">
        <div class="stat-value"><?php echo count($coupons); ?></div>
        <div class="stat-label">Total Coupons</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo count(array_filter($coupons, fn($c) => $c['is_active'])); ?></div>
        <div class="stat-label">Active</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $total_uses; ?></div>
        <div class="stat-label">Total Uses</div>
    </div>
</div>

<!-- Coupons Table -->
<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Discount</th>
                <th>Type</th>
                <th>Used</th>
                <th>Max Uses</th>
                <th>Valid From</th>
                <th>Valid Until</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($coupons)): ?>
                <tr>
                    <td colspan="10" class="text-center">No coupons found</td>
                </tr>
            <?php else: ?>
                <?php foreach ($coupons as $coupon): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($coupon['code']); ?></strong></td>
                        <td><?php echo htmlspecialchars($coupon['name'] ?? '-'); ?></td>
                        <td>
                            <?php 
                            if ($coupon['discount_type'] === 'percentage') {
                                echo number_format($coupon['discount_value'], 0) . '%';
                            } else {
                                echo '$' . number_format($coupon['discount_value'], 2);
                            }
                            ?>
                        </td>
                        <td><?php echo ucfirst($coupon['discount_type']); ?></td>
                        <td><?php echo $coupon['used_count']; ?></td>
                        <td><?php echo $coupon['max_uses'] ? $coupon['max_uses'] : '∞'; ?></td>
                        <td><?php echo $coupon['valid_from'] ? date('M d, Y', strtotime($coupon['valid_from'])) : '-'; ?></td>
                        <td><?php echo $coupon['valid_until'] ? date('M d, Y', strtotime($coupon['valid_until'])) : '-'; ?></td>
                        <td>
                            <span class="badge badge-<?php echo $coupon['is_active'] ? 'active' : 'inactive'; ?>">
                                <?php echo $coupon['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td>
                            <button onclick="toggleCouponStatus(<?php echo $coupon['id']; ?>, <?php echo $coupon['is_active'] ? 0 : 1; ?>)" class="btn btn-sm btn-secondary">
                                <?php echo $coupon['is_active'] ? 'Deactivate' : 'Activate'; ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Create Coupon Modal -->
<div id="createCouponModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeCreateCouponModal()">&times;</span>
        <h3>Create New Coupon</h3>
        <form id="createCouponForm" onsubmit="createCoupon(event)">
            <div class="form-group">
                <label>Coupon Code *</label>
                <input type="text" name="code" required placeholder="WELCOME10" style="text-transform: uppercase;">
                <small>Will be converted to uppercase</small>
            </div>
            <div class="form-group">
                <label>Coupon Name</label>
                <input type="text" name="name" placeholder="Welcome Discount">
            </div>
            <div class="form-group">
                <label>Discount Type</label>
                <select name="discount_type" id="discountType" onchange="updateDiscountPlaceholder()">
                    <option value="percentage">Percentage (%)</option>
                    <option value="fixed">Fixed Amount ($)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Discount Value *</label>
                <input type="number" name="discount_value" id="discountValue" required step="0.01" min="0.01" placeholder="10">
                <small id="discountHint">Enter percentage (0-100)</small>
            </div>
            <div class="form-group">
                <label>Max Uses</label>
                <input type="number" name="max_uses" min="1" placeholder="Leave empty for unlimited">
            </div>
            <div class="form-group">
                <label>Valid From</label>
                <input type="date" name="valid_from">
            </div>
            <div class="form-group">
                <label>Valid Until</label>
                <input type="date" name="valid_until">
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="is_active">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Create Coupon</button>
                <button type="button" class="btn btn-secondary" onclick="closeCreateCouponModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function showCreateCouponModal() {
    document.getElementById('createCouponModal').style.display = 'block';
}

function closeCreateCouponModal() {
    document.getElementById('createCouponModal').style.display = 'none';
    document.getElementById('createCouponForm').reset();
}

function updateDiscountPlaceholder() {
    const type = document.getElementById('discountType').value;
    const input = document.getElementById('discountValue');
    const hint = document.getElementById('discountHint');
    
    if (type === 'percentage') {
        input.setAttribute('max', '100');
        input.placeholder = '10';
        hint.textContent = 'Enter percentage (0-100)';
    } else {
        input.removeAttribute('max');
        input.placeholder = '50.00';
        hint.textContent = 'Enter fixed amount in dollars';
    }
}

function createCoupon(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    // Convert code to uppercase
    const code = formData.get('code').toUpperCase();
    formData.set('code', code);
    
    fetch('api/coupons/create.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Coupon created successfully!');
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
    });
}

function toggleCouponStatus(couponId, newStatus) {
    if (!confirm('Are you sure you want to ' + (newStatus ? 'activate' : 'deactivate') + ' this coupon?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('coupon_id', couponId);
    formData.append('is_active', newStatus);
    
    fetch('api/coupons/update.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

window.onclick = function(event) {
    const modal = document.getElementById('createCouponModal');
    if (event.target == modal) {
        closeCreateCouponModal();
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

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #333;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.form-group small {
    display: block;
    margin-top: 5px;
    color: #666;
    font-size: 12px;
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.badge-active {
    background: #d4edda;
    color: #155724;
}

.badge-inactive {
    background: #f8d7da;
    color: #721c24;
}
</style>
