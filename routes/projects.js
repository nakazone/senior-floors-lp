/**
 * Projects API - Projects management
 */
import { getDBConnection } from '../config/db.js';

export async function listProjects(req, res) {
  try {
    const pool = await getDBConnection();
    if (!pool) {
      return res.status(503).json({ success: false, error: 'Database not available' });
    }

    const page = Math.max(1, parseInt(req.query.page, 10) || 1);
    const limit = Math.min(100, Math.max(1, parseInt(req.query.limit, 10) || 20));
    const offset = (page - 1) * limit;
    const status = req.query.status || null;
    const customerId = req.query.customer_id || null;

    let whereClause = '1=1';
    const params = [];

    if (status) {
      whereClause += ' AND status = ?';
      params.push(status);
    }
    if (customerId) {
      whereClause += ' AND customer_id = ?';
      params.push(customerId);
    }

    const [rows] = await pool.query(
      `SELECT p.*, 
              c.name as customer_name, c.email as customer_email, c.phone as customer_phone
       FROM projects p
       LEFT JOIN customers c ON p.customer_id = c.id
       WHERE ${whereClause}
       ORDER BY p.created_at DESC 
       LIMIT ? OFFSET ?`,
      [...params, limit, offset]
    );

    const [[{ total }]] = await pool.query(
      `SELECT COUNT(*) as total FROM projects WHERE ${whereClause}`,
      params
    );

    res.json({ success: true, data: rows, total, page, limit });
  } catch (error) {
    console.error('List projects error:', error);
    res.status(500).json({ success: false, error: error.message });
  }
}

export async function getProject(req, res) {
  try {
    const pool = await getDBConnection();
    if (!pool) {
      return res.status(503).json({ success: false, error: 'Database not available' });
    }

    const [rows] = await pool.query(
      `SELECT p.*, 
              c.name as customer_name, c.email as customer_email, c.phone as customer_phone
       FROM projects p
       LEFT JOIN customers c ON p.customer_id = c.id
       WHERE p.id = ?`,
      [req.params.id]
    );

    if (rows.length === 0) {
      return res.status(404).json({ success: false, error: 'Project not found' });
    }

    res.json({ success: true, data: rows[0] });
  } catch (error) {
    console.error('Get project error:', error);
    res.status(500).json({ success: false, error: error.message });
  }
}

export async function createProject(req, res) {
  try {
    const pool = await getDBConnection();
    if (!pool) {
      return res.status(503).json({ success: false, error: 'Database not available' });
    }

    const { customer_id, lead_id, name, project_type, status, address, city, state, zipcode,
            estimated_start_date, estimated_end_date, estimated_cost, owner_id, notes } = req.body;

    if (!customer_id || !name) {
      return res.status(400).json({ success: false, error: 'Customer ID and name are required' });
    }

    const [result] = await pool.execute(
      `INSERT INTO projects (customer_id, lead_id, name, project_type, status, address, city, state, zipcode,
                            estimated_start_date, estimated_end_date, estimated_cost, owner_id, notes)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
      [customer_id, lead_id || null, name, project_type || 'installation', status || 'quoted',
       address || null, city || null, state || null, zipcode || null,
       estimated_start_date || null, estimated_end_date || null, estimated_cost || null,
       owner_id || null, notes || null]
    );

    res.status(201).json({ success: true, data: { id: result.insertId }, message: 'Project created' });
  } catch (error) {
    console.error('Create project error:', error);
    res.status(500).json({ success: false, error: error.message });
  }
}

export async function updateProject(req, res) {
  try {
    const pool = await getDBConnection();
    if (!pool) {
      return res.status(503).json({ success: false, error: 'Database not available' });
    }

    const updates = [];
    const values = [];
    const allowedFields = ['name', 'project_type', 'status', 'address', 'city', 'state', 'zipcode',
                          'estimated_start_date', 'estimated_end_date', 'actual_start_date', 'actual_end_date',
                          'estimated_cost', 'actual_cost', 'owner_id', 'notes', 'post_service_status'];

    for (const field of allowedFields) {
      if (req.body[field] !== undefined) {
        updates.push(`${field} = ?`);
        values.push(req.body[field]);
      }
    }

    if (updates.length === 0) {
      return res.status(400).json({ success: false, error: 'No fields to update' });
    }

    values.push(req.params.id);
    await pool.execute(
      `UPDATE projects SET ${updates.join(', ')} WHERE id = ?`,
      values
    );

    res.json({ success: true, message: 'Project updated' });
  } catch (error) {
    console.error('Update project error:', error);
    res.status(500).json({ success: false, error: error.message });
  }
}
