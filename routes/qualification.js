/**
 * API Routes para Lead Qualification
 * GET, POST, PUT /api/leads/:leadId/qualification
 */

import { getDBConnection } from '../config/db.js';

export async function getQualification(req, res) {
  const leadId = parseInt(req.params.leadId);
  
  if (!leadId || isNaN(leadId)) {
    return res.status(400).json({ success: false, error: 'Invalid lead ID' });
  }

  try {
    const pool = await getDBConnection();
    const [rows] = await pool.execute(
      `SELECT q.*, u.name as qualified_by_name 
       FROM lead_qualification q
       LEFT JOIN users u ON q.qualified_by = u.id
       WHERE q.lead_id = ?`,
      [leadId]
    );

    if (rows.length === 0) {
      return res.status(404).json({ success: false, error: 'Qualification not found' });
    }

    return res.json({ success: true, data: rows[0] });
  } catch (error) {
    console.error('Error getting qualification:', error);
    return res.status(500).json({ success: false, error: error.message });
  }
}

export async function createOrUpdateQualification(req, res) {
  const leadId = parseInt(req.params.leadId);
  const userId = req.session?.user?.id;
  
  if (!leadId || isNaN(leadId)) {
    return res.status(400).json({ success: false, error: 'Invalid lead ID' });
  }

  const {
    property_type,
    service_type,
    estimated_area,
    estimated_budget,
    urgency,
    decision_maker,
    decision_timeline,
    payment_type,
    score,
    qualification_notes
  } = req.body;

  try {
    const pool = await getDBConnection();
    
    // Verificar se já existe
    const [existing] = await pool.execute(
      'SELECT id FROM lead_qualification WHERE lead_id = ?',
      [leadId]
    );

    if (existing.length > 0) {
      // Update
      await pool.execute(
        `UPDATE lead_qualification SET
          property_type = ?, service_type = ?, estimated_area = ?,
          estimated_budget = ?, urgency = ?, decision_maker = ?,
          decision_timeline = ?, payment_type = ?, score = ?,
          qualification_notes = ?, qualified_by = ?, qualified_at = NOW()
        WHERE lead_id = ?`,
        [
          property_type, service_type, estimated_area,
          estimated_budget, urgency, decision_maker,
          decision_timeline, payment_type, score,
          qualification_notes, userId, leadId
        ]
      );
    } else {
      // Insert
      await pool.execute(
        `INSERT INTO lead_qualification 
        (lead_id, property_type, service_type, estimated_area, estimated_budget,
         urgency, decision_maker, decision_timeline, payment_type, score,
         qualification_notes, qualified_by, qualified_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())`,
        [
          leadId, property_type, service_type, estimated_area,
          estimated_budget, urgency, decision_maker, decision_timeline,
          payment_type, score, qualification_notes, userId
        ]
      );
    }

    // Buscar atualizado
    const [updated] = await pool.execute(
      `SELECT q.*, u.name as qualified_by_name 
       FROM lead_qualification q
       LEFT JOIN users u ON q.qualified_by = u.id
       WHERE q.lead_id = ?`,
      [leadId]
    );

    return res.json({ success: true, data: updated[0] });
  } catch (error) {
    console.error('Error saving qualification:', error);
    return res.status(500).json({ success: false, error: error.message });
  }
}
