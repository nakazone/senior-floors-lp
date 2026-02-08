/**
 * GET /api/db-check — same as system.php?api=db-check
 */
import { getDBConnection, isDatabaseConfigured } from '../config/db.js';

export async function handleDbCheck(req, res) {
  const config_loaded = isDatabaseConfigured();
  const out = {
    config_loaded,
    config_file_used: process.env.DB_NAME ? '.env' : null,
    db_name: process.env.DB_NAME || null,
    database_configured: config_loaded && isDatabaseConfigured(),
    connection_ok: false,
    table_leads_exists: false,
    hint: '',
    api_version: 'v2-node',
  };
  if (!config_loaded) {
    out.hint = 'Create server/.env from env.example and set DB_HOST, DB_NAME, DB_USER, DB_PASS (no placeholders).';
  } else {
    try {
      const pool = await getDBConnection();
      out.connection_ok = !!pool;
      if (pool) {
        const [t] = await pool.query("SHOW TABLES LIKE 'leads'");
        out.table_leads_exists = t && t.length > 0;
        if (!out.table_leads_exists) out.hint = "Table 'leads' does not exist. Run database/schema-v3-completo.sql in MySQL.";
      } else {
        out.hint = 'Failed to connect to MySQL. Check DB_* in .env.';
      }
    } catch (e) {
      out.connection_ok = false;
      out.hint = e.message;
    }
  }
  res.setHeader('Content-Type', 'application/json; charset=UTF-8');
  res.json(out);
}
