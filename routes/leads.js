/**
 * Leads API — list, get, update (CRM)
 */
import { getDBConnection, isDatabaseConfigured } from '../config/db.js';

export async function listLeads(req, res) {
  if (!isDatabaseConfigured()) return res.status(503).json({ success: false, error: 'Database not configured' });
  try {
    const pool = await getDBConnection();
    const page = Math.max(1, parseInt(req.query.page, 10) || 1);
    const limit = Math.min(100, Math.max(1, parseInt(req.query.limit, 10) || 20));
    const offset = (page - 1) * limit;
    const status = req.query.status || null;
    const ownerId = req.query.owner_id || null;
    const pipelineStageId = req.query.pipeline_stage_id || null;
    
    let whereClause = '1=1';
    const params = [];
    
    if (status) {
      whereClause += ' AND l.status = ?';
      params.push(status);
    }
    if (ownerId) {
      whereClause += ' AND l.owner_id = ?';
      params.push(ownerId);
    }
    if (pipelineStageId) {
      whereClause += ' AND l.pipeline_stage_id = ?';
      params.push(pipelineStageId);
    }
    
    const [rows] = await pool.query(
      `SELECT l.*, u.name as owner_name, ps.name as pipeline_stage_name, ps.slug as pipeline_stage_slug, ps.color as pipeline_stage_color
       FROM leads l
       LEFT JOIN users u ON l.owner_id = u.id
       LEFT JOIN pipeline_stages ps ON l.pipeline_stage_id = ps.id
       WHERE ${whereClause}
       ORDER BY l.created_at DESC LIMIT ? OFFSET ?`,
      [...params, limit, offset]
    );
    const [[{ total }]] = await pool.query(`SELECT COUNT(*) as total FROM leads l WHERE ${whereClause}`, params);
    res.json({ success: true, data: rows, total, page, limit });
  } catch (e) {
    res.status(500).json({ success: false, error: e.message });
  }
}

export async function getLead(req, res) {
  if (!isDatabaseConfigured()) return res.status(503).json({ error: 'Database not configured' });
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

export async function createLead(req, res) {
  if (!isDatabaseConfigured()) return res.status(503).json({ success: false, error: 'Database not configured' });
  
  const { name, email, phone, zipcode, message, source, form_type, status, priority, owner_id, pipeline_stage_id, estimated_value, notes } = req.body;
  
  // Validation
  if (!name || name.trim().length < 2) {
    return res.status(400).json({ success: false, error: 'Name is required (min 2 characters)' });
  }
  if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    return res.status(400).json({ success: false, error: 'Valid email is required' });
  }
  if (!phone || phone.trim().length < 10) {
    return res.status(400).json({ success: false, error: 'Phone is required' });
  }
  const zipClean = (zipcode || '').replace(/\D/g, '');
  if (!zipClean || zipClean.length < 5) {
    return res.status(400).json({ success: false, error: 'Valid 5-digit zip code is required' });
  }
  
  try {
    const pool = await getDBConnection();
    const userId = req.session?.user?.id;
    
    // Get default pipeline stage if not provided
    let finalPipelineStageId = pipeline_stage_id;
    if (!finalPipelineStageId) {
      const [stages] = await pool.execute(
        "SELECT id FROM pipeline_stages WHERE slug = 'lead_received' ORDER BY order_num LIMIT 1"
      );
      if (stages.length > 0) {
        finalPipelineStageId = stages[0].id;
      }
    }
    
    const [result] = await pool.execute(
      `INSERT INTO leads 
       (name, email, phone, zipcode, message, source, form_type, status, priority, owner_id, pipeline_stage_id, estimated_value, notes, created_at)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())`,
      [
        name.trim(),
        email.trim(),
        phone.trim(),
        zipClean.slice(0, 5),
        message || null,
        source || 'Manual',
        form_type || 'manual',
        status || 'lead_received',
        priority || 'medium',
        owner_id || userId || null,
        finalPipelineStageId,
        estimated_value || null,
        notes || null
      ]
    );
    
    // Get created lead
    const [created] = await pool.execute(
      `SELECT l.*, u.name as owner_name, ps.name as pipeline_stage_name, ps.slug as pipeline_stage_slug
       FROM leads l
       LEFT JOIN users u ON l.owner_id = u.id
       LEFT JOIN pipeline_stages ps ON l.pipeline_stage_id = ps.id
       WHERE l.id = ?`,
      [result.insertId]
    );
    
    return res.status(201).json({ success: true, data: created[0], lead_id: result.insertId });
  } catch (error) {
    console.error('Create lead error:', error);
    return res.status(500).json({ success: false, error: error.message });
  }
}

export async function updateLead(req, res) {
  if (!isDatabaseConfigured()) return res.status(503).json({ error: 'Database not configured' });
  const id = parseInt(req.params.id, 10);
  if (!id) return res.status(400).json({ error: 'Invalid id' });
  const body = req.body || {};
  const allowed = ['name', 'email', 'phone', 'zipcode', 'message', 'status', 'priority', 'owner_id', 'pipeline_stage_id', 'estimated_value', 'notes'];
  const set = [];
  const values = [];
  for (const key of allowed) {
    if (body[key] !== undefined) {
      set.push(`\`${key}\` = ?`);
      values.push(body[key]);
    }
  }
  if (set.length === 0) return res.status(400).json({ error: 'No fields to update' });
  values.push(id);
  try {
    const pool = await getDBConnection();
    await pool.execute(`UPDATE leads SET ${set.join(', ')} WHERE id = ?`, values);
    const [rows] = await pool.query('SELECT * FROM leads WHERE id = ?', [id]);
    res.json({ success: true, data: rows[0] });
  } catch (e) {
    res.status(500).json({ error: e.message });
  }
}
