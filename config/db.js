/**
 * Database config - MySQL (Railway addon or external)
 */
import mysql from 'mysql2/promise';

let pool = null;

function isDatabaseConfigured() {
  const user = process.env.DB_USER || '';
  const pass = process.env.DB_PASS || '';
  const name = process.env.DB_NAME || '';
  const noPlaceholder = (s) => s && !/SEU_USUARIO|SUA_SENHA|your_db/i.test(s);
  return noPlaceholder(user) && noPlaceholder(pass) && noPlaceholder(name);
}

async function getDBConnection() {
  if (!isDatabaseConfigured()) return null;
  if (pool) return pool;
  try {
    pool = mysql.createPool({
      host: process.env.DB_HOST || 'localhost',
      user: process.env.DB_USER,
      password: process.env.DB_PASS,
      database: process.env.DB_NAME,
      charset: 'utf8mb4',
      waitForConnections: true,
      connectionLimit: 10,
      queueLimit: 0,
    });
    return pool;
  } catch (e) {
    console.error('DB connection error:', e.message);
    return null;
  }
}

export { isDatabaseConfigured, getDBConnection };
