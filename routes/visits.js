/**
 * Visits/Schedule API - Site visits and scheduling
 */
import { getDBConnection } from '../config/db.js';

/** Aceita datetime-local (…T…) ou MySQL */
function normalizeScheduledAt(v) {
  if (v === undefined || v === null) return null;
  let s = typeof v === 'string' ? v.trim() : String(v);
  if (!s) return null;
  if (s.includes('T')) {
    s = s.replace('T', ' ');
    if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/.test(s)) s += ':00';
  }
  return s;
}

function isUnknownColumnError(err) {
  return err && (err.code === 'ER_BAD_FIELD_ERROR' || (err.message && /Unknown column/i.test(err.message)));
}

export async function listVisits(req, res) {
  try {
    const pool = await getDBConnection();
    if (!pool) {
      return res.status(503).json({ success: false, error: 'Database not available' });
    }

    const page = Math.max(1, parseInt(req.query.page, 10) || 1);
    const limit = Math.min(100, Math.max(1, parseInt(req.query.limit, 10) || 20));
    const offset = (page - 1) * limit;
    const status = req.query.status || null;
    const sellerId = req.query.seller_id || null;
    const leadId = req.query.lead_id || null;
    const dateFrom = req.query.date_from || null;
    const dateTo = req.query.date_to || null;

    let whereClause = '1=1';
    const params = [];

    if (status) {
      whereClause += ' AND v.status = ?';
      params.push(status);
    }
    if (sellerId) {
      whereClause += ' AND v.assigned_to = ?';
      params.push(sellerId);
    }
    if (leadId) {
      whereClause += ' AND v.lead_id = ?';
      params.push(leadId);
    }
    if (dateFrom) {
      whereClause += ' AND v.scheduled_at >= ?';
      params.push(dateFrom);
    }
    if (dateTo) {
      whereClause += ' AND v.scheduled_at <= ?';
      params.push(dateTo);
    }

    const [rows] = await pool.query(
      `SELECT v.*, 
              l.name as lead_name, l.email as lead_email, l.phone as lead_phone,
              u.name as assigned_to_name
       FROM visits v
       LEFT JOIN leads l ON v.lead_id = l.id
       LEFT JOIN users u ON v.assigned_to = u.id
       WHERE ${whereClause}
       ORDER BY v.scheduled_at ASC 
       LIMIT ? OFFSET ?`,
      [...params, limit, offset]
    );

    const [[{ total }]] = await pool.query(
      `SELECT COUNT(*) as total FROM visits v WHERE ${whereClause}`,
      params
    );

    res.json({ success: true, data: rows, total, page, limit });
  } catch (error) {
    console.error('List visits error:', error);
    res.status(500).json({ success: false, error: error.message });
  }
}

export async function getVisit(req, res) {
  try {
    const pool = await getDBConnection();
    if (!pool) {
      return res.status(503).json({ success: false, error: 'Database not available' });
    }

    const [rows] = await pool.query('SELECT * FROM visits WHERE id = ?', [req.params.id]);
    if (rows.length === 0) {
      return res.status(404).json({ success: false, error: 'Visit not found' });
    }

    res.json({ success: true, data: rows[0] });
  } catch (error) {
    console.error('Get visit error:', error);
    res.status(500).json({ success: false, error: error.message });
  }
}

export async function createVisit(req, res) {
  try {
    const pool = await getDBConnection();
    if (!pool) {
      return res.status(503).json({ success: false, error: 'Database not available' });
    }

    const body = req.body || {};
    const scheduledRaw = body.scheduled_at;
    const scheduled_at = normalizeScheduledAt(scheduledRaw) || (typeof scheduledRaw === 'string' ? scheduledRaw.trim() : null);
    if (!scheduled_at) {
      return res.status(400).json({ success: false, error: 'Scheduled date/time is required' });
    }

    const lead_id = body.lead_id != null ? parseInt(body.lead_id, 10) : null;
    if (!lead_id || Number.isNaN(lead_id)) {
      return res.status(400).json({ success: false, error: 'Valid lead_id is required' });
    }

    const line1 = (body.address_line1 && String(body.address_line1).trim()) || '';
    const line2 = (body.address_line2 && String(body.address_line2).trim()) || '';
    const city = (body.city && String(body.city).trim()) || '';
    const state = (body.state && String(body.state).trim()) || null;
    const zipRaw = (body.zipcode && String(body.zipcode).trim()) || '';

    let address = (body.address && String(body.address).trim()) || '';
    if (!address) {
      address = [line1, line2, city, zipRaw].filter(Boolean).join(', ');
    }
    if (!address) {
      address = 'A confirmar';
    }
    address = address.slice(0, 500);

    const notes = body.notes != null && String(body.notes).trim() ? String(body.notes).trim() : null;
    const assignRaw = body.assigned_to !== undefined && body.assigned_to !== '' ? body.assigned_to : body.seller_id;
    let assigned_to = null;
    if (assignRaw !== undefined && assignRaw !== null && assignRaw !== '') {
      const n = parseInt(assignRaw, 10);
      if (!Number.isNaN(n)) assigned_to = n;
    }

    const customer_id = body.customer_id != null ? parseInt(body.customer_id, 10) : null;
    const project_id = body.project_id != null ? parseInt(body.project_id, 10) : null;
    const technician_id = body.technician_id != null ? parseInt(body.technician_id, 10) : null;

    // Schema CRM (assigned_to, address + city/zip columns)
    try {
      const [result] = await pool.execute(
        `INSERT INTO visits (lead_id, scheduled_at, address, address_line2, city, state, zipcode, notes, status, assigned_to)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'scheduled', ?)`,
        [
          lead_id,
          scheduled_at,
          address,
          line2 || null,
          city || null,
          state,
          zipRaw ? zipRaw.slice(0, 10) : null,
          notes,
          assigned_to,
        ]
      );
      return res.status(201).json({ success: true, data: { id: result.insertId }, message: 'Visit scheduled' });
    } catch (err) {
      if (!isUnknownColumnError(err)) {
        console.error('Create visit error:', err);
        return res.status(500).json({ success: false, error: err.message });
      }
    }

    // Legado (seller_id / customer_id / project_id)
    try {
      const [result] = await pool.execute(
        `INSERT INTO visits (lead_id, customer_id, project_id, scheduled_at, seller_id, technician_id, address, notes, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'scheduled')`,
        [
          lead_id,
          Number.isNaN(customer_id) ? null : customer_id,
          Number.isNaN(project_id) ? null : project_id,
          scheduled_at,
          assigned_to,
          Number.isNaN(technician_id) ? null : technician_id,
          address,
          notes,
        ]
      );
      return res.status(201).json({ success: true, data: { id: result.insertId }, message: 'Visit scheduled' });
    } catch (err2) {
      console.error('Create visit error (legacy):', err2);
      return res.status(500).json({ success: false, error: err2.message });
    }
  } catch (error) {
    console.error('Create visit error:', error);
    res.status(500).json({ success: false, error: error.message });
  }
}

export async function updateVisit(req, res) {
  try {
    const pool = await getDBConnection();
    if (!pool) {
      return res.status(503).json({ success: false, error: 'Database not available' });
    }

    const body = req.body || {};
    const updates = [];
    const values = [];

    if (body.scheduled_at !== undefined) {
      const n = normalizeScheduledAt(body.scheduled_at);
      updates.push('scheduled_at = ?');
      values.push(n || body.scheduled_at);
    }
    if (body.ended_at !== undefined) {
      updates.push('ended_at = ?');
      values.push(body.ended_at);
    }
    if (body.address !== undefined) {
      updates.push('address = ?');
      values.push(body.address);
    }
    if (body.notes !== undefined) {
      updates.push('notes = ?');
      values.push(body.notes);
    }
    if (body.status !== undefined) {
      updates.push('status = ?');
      values.push(body.status);
    }

    const assignRaw = body.assigned_to !== undefined ? body.assigned_to : body.seller_id;
    if (assignRaw !== undefined) {
      const v = assignRaw === '' || assignRaw === null ? null : parseInt(assignRaw, 10);
      updates.push('assigned_to = ?');
      values.push(v !== null && !Number.isNaN(v) ? v : null);
    }

    if (updates.length === 0) {
      return res.status(400).json({ success: false, error: 'No fields to update' });
    }

    values.push(req.params.id);

    try {
      await pool.execute(`UPDATE visits SET ${updates.join(', ')} WHERE id = ?`, values);
      return res.json({ success: true, message: 'Visit updated' });
    } catch (err) {
      if (!isUnknownColumnError(err) || !updates.some((u) => u.startsWith('assigned_to'))) {
        console.error('Update visit error:', err);
        return res.status(500).json({ success: false, error: err.message });
      }
    }

    const updatesLegacy = updates.map((u) => (u.startsWith('assigned_to') ? 'seller_id = ?' : u));
    try {
      await pool.execute(`UPDATE visits SET ${updatesLegacy.join(', ')} WHERE id = ?`, values);
      return res.json({ success: true, message: 'Visit updated' });
    } catch (err2) {
      console.error('Update visit error (legacy):', err2);
      return res.status(500).json({ success: false, error: err2.message });
    }
  } catch (error) {
    console.error('Update visit error:', error);
    res.status(500).json({ success: false, error: error.message });
  }
}
