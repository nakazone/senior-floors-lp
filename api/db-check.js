/**
 * Vercel serverless: GET /api/db-check — DB status
 */
import 'dotenv/config';
import { handleDbCheck } from '../server/routes/dbCheck.js';

export default async function handler(req, res) {
  res.setHeader('Access-Control-Allow-Origin', '*');
  if (req.method !== 'GET') return res.status(405).json({ error: 'Method not allowed' });
  return handleDbCheck(req, res);
}
