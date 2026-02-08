/**
 * POST /send-lead — same as send-lead.php: validate, CSV, optional email, then save to DB (via receive-lead or direct)
 */
import { getDBConnection, isDatabaseConfigured } from '../config/db.js';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import nodemailer from 'nodemailer';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const LOG_DIR = process.env.VERCEL ? '/tmp' : path.resolve(__dirname, '..');
const LEAD_LOG_FILE = path.join(LOG_DIR, 'lead-db-save.log');

function writeLeadLog(msg) {
  const line = `${new Date().toISOString().slice(0, 19).replace('T', ' ')} | ${msg}\n`;
  try { fs.appendFileSync(LEAD_LOG_FILE, line); } catch (_) {}
}

function getBody(req) {
  if (req.body && typeof req.body === 'object' && (req.body.name || req.body.email)) return req.body;
  return {};
}

function csvEscape(s) {
  return `"${String(s).replace(/"/g, '""')}"`;
}

export async function handleSendLead(req, res) {
  const post = getBody(req);
  const form_name = (post['form-name'] || post.formName || 'contact-form').trim();
  let name = (post.name || '').trim();
  let phone = (post.phone || '').trim();
  let email = (post.email || '').trim();
  let zipcode = (post.zipcode || '').trim();
  let message = (post.message || '').trim();

  writeLeadLog(`send-lead called | POST keys: ${Object.keys(post).join(', ')}`);

  const errors = [];
  if (!name || name.length < 2) errors.push('Name is required and must be at least 2 characters');
  if (!phone) errors.push('Phone number is required');
  if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) errors.push('Valid email address is required');
  const zipDigits = (zipcode || '').replace(/\D/g, '');
  if (!zipDigits || zipDigits.length < 5) errors.push('Valid 5-digit US zip code is required');
  if (errors.length > 0) {
    writeLeadLog('Validation failed: ' + errors.join('; '));
    return res.status(400).json({ success: false, message: errors.join(', ') });
  }

  zipcode = zipDigits.slice(0, 5);
  writeLeadLog(`LP received | form=${form_name} | name=${name.slice(0, 30)} | email=${email.slice(0, 40)}`);

  let db_saved = false;
  let lead_id = null;
  let system_sent = false;
  let system_database_saved = null;
  let system_error = '';

  // 1) Try local DB save (same as receive-lead logic inline, or call receive-lead internally)
  if (isDatabaseConfigured()) {
    try {
      const pool = await getDBConnection();
      if (pool) {
        const [tables] = await pool.query("SHOW TABLES LIKE 'leads'");
        if (tables && tables.length > 0) {
          const source = form_name === 'hero-form' ? 'LP-Hero' : 'LP-Contact';
          const ip_address = req.ip || req.connection?.remoteAddress || null;
          const [result] = await pool.execute(
            `INSERT INTO leads (name, email, phone, zipcode, message, source, form_type, status, priority, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
            [name, email, phone, zipcode, message, source, form_name, 'new', 'medium', ip_address]
          );
          lead_id = result.insertId;
          db_saved = true;
          writeLeadLog(`Lead saved to database | ID: ${lead_id}`);
        }
      }
    } catch (e) {
      writeLeadLog('INSERT error: ' + e.message);
    }
  }

  // 2) CSV backup (on Vercel uses /tmp; optional)
  const csvDir = LOG_DIR;
  const csvPath = process.env.LEADS_CSV_PATH ? path.resolve(LOG_DIR, process.env.LEADS_CSV_PATH) : path.join(LOG_DIR, 'leads.csv');
  const csvLine = [new Date().toISOString().slice(0, 19).replace('T', ' '), form_name, name, phone, email, zipcode, (message || '').replace(/\r?\n/g, ' ')];
  let csv_saved = false;
  try {
    if (!fs.existsSync(csvDir)) fs.mkdirSync(csvDir, { recursive: true });
    if (!fs.existsSync(csvPath)) fs.writeFileSync(csvPath, 'Date,Form,Name,Phone,Email,ZipCode,Message\n');
    fs.appendFileSync(csvPath, csvLine.map(csvEscape).join(',') + '\n');
    csv_saved = true;
  } catch (e) {
    writeLeadLog('CSV write failed: ' + e.message);
  }

  // 3) Optional: call internal receive-lead (if we didn't save locally, e.g. different server)
  const systemUrl = process.env.SYSTEM_API_URL;
  if (!db_saved && systemUrl) {
    try {
      const base = systemUrl.replace(/\/$/, '');
      const url = `${base}/api/receive-lead`;
      const body = new URLSearchParams({
        'form-name': form_name,
        name,
        phone,
        email,
        zipcode,
        message: message || '',
      }).toString();
      const r = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', Accept: 'application/json' },
        body,
      });
      if (r.ok) {
        system_sent = true;
        const data = await r.json();
        system_database_saved = data.database_saved;
        if (data.database_saved && data.lead_id) {
          db_saved = true;
          lead_id = data.lead_id;
          writeLeadLog(`Lead saved via system API | ID: ${lead_id}`);
        }
      } else {
        system_error = `HTTP ${r.status}`;
      }
    } catch (e) {
      system_error = e.message || 'Request failed';
    }
  } else if (db_saved) {
    system_sent = true;
    system_database_saved = true;
  }

  // 4) Optional: email via Nodemailer
  let mail_sent = false;
  if (process.env.SMTP_PASS && process.env.SMTP_PASS !== 'YOUR_APP_PASSWORD_HERE') {
    try {
      const transport = nodemailer.createTransport({
        host: process.env.SMTP_HOST || 'smtp.gmail.com',
        port: Number(process.env.SMTP_PORT) || 587,
        secure: false,
        auth: { user: process.env.SMTP_USER, pass: process.env.SMTP_PASS },
      });
      await transport.sendMail({
        from: `"${process.env.SMTP_FROM_NAME || 'Senior Floors'}" <${process.env.SMTP_FROM_EMAIL}>`,
        to: process.env.SMTP_TO_EMAIL || process.env.SMTP_FROM_EMAIL,
        subject: `New Lead from Senior Floors - ${form_name === 'hero-form' ? 'Hero' : 'Contact'}`,
        text: `Form: ${form_name}\nName: ${name}\nPhone: ${phone}\nEmail: ${email}\nZip: ${zipcode}\nMessage: ${message}`,
        replyTo: `${name} <${email}>`,
      });
      mail_sent = true;
    } catch (e) {
      writeLeadLog('Email failed: ' + e.message);
    }
  }

  const response = {
    success: true,
    message: "Thank you! We'll contact you within 24 hours.",
    email_sent: mail_sent,
    system_sent,
    system_database_saved,
    database_saved: db_saved,
    csv_saved,
    lead_id,
    timestamp: new Date().toISOString().slice(0, 19).replace('T', ' '),
    system_api_version: 'receive-lead-node',
  };
  if (system_error) response.system_error = system_error;
  res.setHeader('Content-Type', 'application/json; charset=UTF-8');
  res.status(200).json(response);
}
