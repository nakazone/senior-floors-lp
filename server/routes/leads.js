/**
 * Leads API — start of CRM port from PHP (api/leads/*)
 * GET /api/leads — list (paginated), same DB as PHP
 */
import { getDBConnection, isDatabaseConfigured } from '../config/db.js';

export async function listLeads(req, res) {
  if (!isDatabaseConfigured()) {
    return res.status(503).json({ error: 'Database not configured' });
  }
  try {
    const pool = await getDBConnection();
    const page = Math.max(1, parseInt(req.query.page, 10) || 1);
    const limit = Math.min(100, Math.max(1, parseInt(req.query.limit, 10) || 20));
    const offset = (page - 1) * limit;
    const [rows] = await pool.query(
      'SELECT id, name, email, phone, zipcode, source, form_type, status, priority, created_at FROM leads ORDER BY created_at DESC LIMIT ? OFFSET ?',
      [limit, offset]
    );
    const [[{ total }]] = await pool.query('SELECT COUNT(*) as total FROM leads');
    res.json({ success: true, data: rows, total, page, limit });
  } catch (e) {
    res.status(500).json({ error: e.message });
  }
}

export async function getLead(req, res) {
  if (!isDatabaseConfigured()) {
    return res.status(503).json({ error: 'Database not configured' });
  }
  const id = parseInt(req.params.id, 10);
  if (!id) return res.status(400).json({ error: 'Invalid id' });
  try {
    const pool = await getDBConnection();
    const [rows] = await pool.query('SELECT * FROM leads WHERE id = ?', [id]);
    if (!rows.length) return res.status(404).json({ error: 'Lead not found' });
    res.json({ success: true, data: rows[0] });
  } catch (e) {
    res.status(500).json({ error: e.message });
  }
}
