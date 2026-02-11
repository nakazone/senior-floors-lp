/**
 * Senior Floors Backend — Node.js (replaces PHP send-lead.php + system.php API)
 * Run: npm install && npm start
 * Env: copy env.example to .env and set DB_*, optional SMTP_*, PORT
 */
import 'dotenv/config';
import path from 'path';
import { fileURLToPath } from 'url';
import express from 'express';
import cors from 'cors';
import { handleReceiveLead } from './routes/receiveLead.js';
import { handleSendLead } from './routes/sendLead.js';
import { handleDbCheck } from './routes/dbCheck.js';
import { listLeads, getLead } from './routes/leads.js';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const app = express();
const PORT = Number(process.env.PORT) || 3000;

app.use(cors({ origin: true, credentials: false }));
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// OPTIONS for CORS preflight
app.options('*', (req, res) => {
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'POST, GET, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Accept');
  res.sendStatus(204);
});

// — API (same contract as PHP)
app.get('/api/db-check', handleDbCheck);
app.post('/api/receive-lead', handleReceiveLead);
app.post('/send-lead', handleSendLead);
app.post('/send-lead.php', handleSendLead); // URL compatibility with LP
app.get('/api/leads', listLeads);
app.get('/api/leads/:id', getLead);

// Compatibility: system.php?api=receive-lead (query param)
app.all('/system.php', (req, res, next) => {
  if (req.query.api === 'receive-lead' && req.method === 'POST') return handleReceiveLead(req, res);
  if (req.query.api === 'db-check' && req.method === 'GET') return handleDbCheck(req, res);
  res.status(404).json({ error: 'Not found', hint: 'Use /api/receive-lead or /api/db-check' });
});

// Health
app.get('/api/health', (req, res) => {
  res.json({ ok: true, service: 'senior-floors-node', time: new Date().toISOString() });
});

// Serve static LP from project root (index.html, assets, script.js, styles.css)
app.use(express.static(path.join(__dirname, '..'), { index: ['index.html'] }));

app.listen(PORT, () => {
  console.log(`Senior Floors server running at http://localhost:${PORT}`);
  console.log('  POST /send-lead  — form submit (LP)');
  console.log('  POST /api/receive-lead — save lead to DB');
  console.log('  GET  /api/db-check — DB status');
});
