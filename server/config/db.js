/**
 * Database config - same as config/database.php
 * Uses .env: DB_HOST, DB_NAME, DB_USER, DB_PASS
 */
import mysql from 'mysql2/promise';

let pool = null;

function isDatabaseConfigured() {
  const user = process.env.DB_USER || '';
  const pass = process.env.DB_PASS || '';
  const name = process.env.DB_NAME || '';
  const noPlaceholder = (s) => s && !/SEU_USUARIO|SUA_SENHA_AQUI|seu_usuario|sua_senha/i.test(s);
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
