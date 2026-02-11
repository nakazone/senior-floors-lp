/**
 * Senior Floors System — Node.js API for Railway
 * Receives leads from LP (Vercel), CRM APIs (leads list/get/update), db-check
 * Admin panel with authentication
 */
import 'dotenv/config';
import express from 'express';
import cors from 'cors';
import session from 'express-session';
import path from 'path';
import { fileURLToPath } from 'url';
import { handleReceiveLead } from './routes/receiveLead.js';
import { handleDbCheck } from './routes/dbCheck.js';
import { listLeads, getLead, updateLead } from './routes/leads.js';
import { login, logout, checkSession } from './routes/auth.js';
import { requireAuth } from './middleware/auth.js';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const PORT = Number(process.env.PORT) || 3000;

// Session configuration
app.use(session({
  secret: process.env.SESSION_SECRET || 'senior-floors-secret-change-in-production',
  resave: false,
  saveUninitialized: false,
  cookie: {
    secure: process.env.NODE_ENV === 'production',
    httpOnly: true,
    maxAge: 24 * 60 * 60 * 1000 // 24 hours
  }
}));

app.use(cors({ origin: true, credentials: true }));
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Serve static files (admin panel)
app.use(express.static(path.join(__dirname, 'public')));

app.options('*', (req, res) => {
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization');
  res.sendStatus(204);
});

// Root route - redirect to admin or show API info
app.get('/', (req, res) => {
  if (req.session && req.session.userId) {
    return res.redirect('/dashboard.html');
  }
  res.redirect('/login.html');
});

// Authentication routes (public)
app.post('/api/auth/login', login);
app.post('/api/auth/logout', logout);
app.get('/api/auth/session', checkSession);

// Public API routes (LP can call these)
app.get('/api/db-check', handleDbCheck);
app.post('/api/receive-lead', handleReceiveLead);
app.get('/api/health', (req, res) => {
  res.json({ ok: true, service: 'senior-floors-system', time: new Date().toISOString() });
});

// Protected API routes (require authentication)
app.get('/api/leads', requireAuth, listLeads);
app.get('/api/leads/:id', requireAuth, getLead);
app.put('/api/leads/:id', requireAuth, updateLead);

// Compatibility: system.php?api=receive-lead
app.all('/system.php', (req, res) => {
  if (req.query.api === 'receive-lead' && req.method === 'POST') return handleReceiveLead(req, res);
  if (req.query.api === 'db-check' && req.method === 'GET') return handleDbCheck(req, res);
  res.status(404).json({ error: 'Not found' });
});

app.listen(PORT, '0.0.0.0', () => {
  console.log(`Senior Floors System running on port ${PORT}`);
  console.log('  Admin Panel: http://localhost:' + PORT);
  console.log('  POST /api/receive-lead — receive lead from LP');
  console.log('  GET  /api/db-check, /api/leads, /api/leads/:id');
});
