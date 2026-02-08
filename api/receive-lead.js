/**
 * Vercel serverless: POST /api/receive-lead — save lead to DB
 */
import 'dotenv/config';
import { handleReceiveLead } from '../server/routes/receiveLead.js';

export const config = { api: { bodyParser: true } };

export default async function handler(req, res) {
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Accept');
  if (req.method === 'OPTIONS') return res.status(204).end();
  if (req.method !== 'POST') return res.status(405).json({ success: false, message: 'Method not allowed' });
  return handleReceiveLead(req, res);
}
