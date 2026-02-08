/**
 * Senior Floors System — Node.js API for Railway
 * Receives leads from LP (Vercel), CRM APIs (leads list/get/update), db-check
 */
import 'dotenv/config';
import express from 'express';
import cors from 'cors';
import { handleReceiveLead } from './routes/receiveLead.js';
import { handleDbCheck } from './routes/dbCheck.js';
import { listLeads, getLead, updateLead } from './routes/leads.js';

const app = express();
const PORT = Number(process.env.PORT) || 3000;

app.use(cors({ origin: true, credentials: false }));
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

app.options('*', (req, res) => {
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization');
  res.sendStatus(204);
});

// — API (LP sends leads here; CRM consumes these)
app.get('/api/db-check', handleDbCheck);
app.post('/api/receive-lead', handleReceiveLead);
app.get('/api/leads', listLeads);
app.get('/api/leads/:id', getLead);
app.put('/api/leads/:id', updateLead);

// Compatibility: system.php?api=receive-lead
app.all('/system.php', (req, res) => {
  if (req.query.api === 'receive-lead' && req.method === 'POST') return handleReceiveLead(req, res);
  if (req.query.api === 'db-check' && req.method === 'GET') return handleDbCheck(req, res);
  res.status(404).json({ error: 'Not found' });
});

app.get('/api/health', (req, res) => {
  res.json({ ok: true, service: 'senior-floors-system', time: new Date().toISOString() });
});

app.listen(PORT, () => {
  console.log(`Senior Floors System running on port ${PORT}`);
  console.log('  POST /api/receive-lead — receive lead from LP');
  console.log('  GET  /api/db-check, /api/leads, /api/leads/:id');
});
