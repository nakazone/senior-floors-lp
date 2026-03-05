/**
 * GET /api/validate-zip?zip=80202
 * Retorna se o ZIP está dentro de 50 milhas de Denver (para restringir envio do form na LP).
 */

import { isZipWithin50MilesOfDenver } from '../utils/denver-radius.js';

export default async function handler(req, res) {
  res.setHeader('Content-Type', 'application/json; charset=UTF-8');
  res.setHeader('Access-Control-Allow-Origin', '*');
  if (req.method === 'OPTIONS') {
    res.setHeader('Access-Control-Allow-Methods', 'GET, OPTIONS');
    return res.status(200).end();
  }
  if (req.method !== 'GET') {
    return res.status(405).json({ ok: false, error: 'Method not allowed' });
  }
  const zip = (req.query.zip || '').trim().replace(/\D/g, '').slice(0, 5);
  if (zip.length < 5) {
    return res.status(200).json({
      ok: true,
      inRange: false,
      message: 'Please enter a valid 5-digit US zip code.',
    });
  }
  const result = await isZipWithin50MilesOfDenver(zip);
  const message = result.inRange
    ? null
    : result.error || `We currently serve only areas within ${50} miles of Denver, CO. Your location is about ${result.distanceMiles ?? '?'} miles away.`;
  return res.status(200).json({
    ok: true,
    inRange: result.inRange,
    distanceMiles: result.distanceMiles,
    message,
  });
}
