/**
 * API Routes para Interactions (chamadas, emails, WhatsApp, visitas)
 * GET, POST /api/leads/:leadId/interactions
 */

import { getDBConnection } from '../config/db.js';

export async function listInteractions(req, res) {
  const leadId = parseInt(req.params.leadId);
  const page = parseInt(req.query.page) || 1;
  const limit = parseInt(req.query.limit) || 20;
  const offset = (page - 1) * limit;

  if (!leadId || isNaN(leadId)) {
    return res.status(400).json({ success: false, error: 'Invalid lead ID' });
  }

  try {
    const pool = await getDBConnection();
    
    // Contar total
    const [countResult] = await pool.execute(
      'SELECT COUNT(*) as total FROM interactions WHERE lead_id = ?',
      [leadId]
    );
    const total = countResult[0].total;

    // Buscar interações
    const [rows] = await pool.execute(
      `SELECT i.*, u.name as user_name, u.email as user_email
       FROM interactions i
       LEFT JOIN users u ON i.user_id = u.id
       WHERE i.lead_id = ?
       ORDER BY i.created_at DESC
       LIMIT ? OFFSET ?`,
      [leadId, limit, offset]
    );

    return res.json({
      success: true,
      data: rows,
      pagination: {
        page,
        limit,
        total,
        totalPages: Math.ceil(total / limit)
      }
    });
  } catch (error) {
    console.error('Error listing interactions:', error);
    return res.status(500).json({ success: false, error: error.message });
  }
}

export async function createInteraction(req, res) {
  const leadId = parseInt(req.params.leadId);
  const userId = req.session?.user?.id;

  if (!leadId || isNaN(leadId)) {
    return res.status(400).json({ success: false, error: 'Invalid lead ID' });
  }

  const body = req.body || {};
  const type = body.type;
  const notes = body.notes ?? null;

  if (!type) {
    return res.status(400).json({ success: false, error: 'Type is required' });
  }

  try {
    const pool = await getDBConnection();
    // INSERT apenas colunas que existem na tabela (compatível com schema mínimo: lead_id, user_id, type, notes)
    const [result] = await pool.execute(
      `INSERT INTO interactions (lead_id, user_id, type, notes) VALUES (?, ?, ?, ?)`,
      [leadId, userId ?? null, type, notes]
    );

    // Buscar criado
    const [created] = await pool.execute(
      `SELECT i.*, u.name as user_name, u.email as user_email
       FROM interactions i
       LEFT JOIN users u ON i.user_id = u.id
       WHERE i.id = ?`,
      [result.insertId]
    );

    return res.status(201).json({ success: true, data: created[0] });
  } catch (error) {
    console.error('Error creating interaction:', error);
    return res.status(500).json({ success: false, error: error.message });
  }
}
