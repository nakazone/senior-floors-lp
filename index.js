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
import { listCustomers, getCustomer, createCustomer, updateCustomer } from './routes/customers.js';
import { listQuotes, getQuote, createQuote, updateQuote } from './routes/quotes.js';
import { listProjects, getProject, createProject, updateProject } from './routes/projects.js';
import { listVisits, getVisit, createVisit, updateVisit } from './routes/visits.js';
import { listActivities, createActivity } from './routes/activities.js';
import { listContracts, getContract, createContract, updateContract } from './routes/contracts.js';
import { listUsers, getUser, createUser, updateUser } from './routes/users.js';
import { getDashboardStats } from './routes/dashboard.js';
import { getQualification, createOrUpdateQualification } from './routes/qualification.js';
import { listInteractions, createInteraction } from './routes/interactions.js';
import { getMeasurement, createOrUpdateMeasurement } from './routes/measurements.js';
import { listProposals, getProposal, createProposal, updateProposal } from './routes/proposals.js';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const PORT = Number(process.env.PORT) || 3000;

// Session configuration
app.use(session({
  secret: process.env.SESSION_SECRET || 'senior-floors-secret-change-in-production',
  resave: false,
  saveUninitialized: false,
  name: 'seniorfloors.sid', // Nome customizado para evitar conflitos
  cookie: {
    secure: false, // Railway pode usar HTTP, então false é mais seguro
    httpOnly: true,
    sameSite: 'lax', // Permite cookies em navegação cross-site
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

// Dashboard
app.get('/api/dashboard/stats', requireAuth, getDashboardStats);

// Leads
app.get('/api/leads', requireAuth, listLeads);
app.get('/api/leads/:id', requireAuth, getLead);
app.put('/api/leads/:id', requireAuth, updateLead);

// Lead Qualification
app.get('/api/leads/:leadId/qualification', requireAuth, getQualification);
app.post('/api/leads/:leadId/qualification', requireAuth, createOrUpdateQualification);
app.put('/api/leads/:leadId/qualification', requireAuth, createOrUpdateQualification);

// Lead Interactions
app.get('/api/leads/:leadId/interactions', requireAuth, listInteractions);
app.post('/api/leads/:leadId/interactions', requireAuth, createInteraction);

// Proposals
app.get('/api/leads/:leadId/proposals', requireAuth, listProposals);
app.get('/api/proposals/:proposalId', requireAuth, getProposal);
app.post('/api/leads/:leadId/proposals', requireAuth, createProposal);
app.put('/api/proposals/:proposalId', requireAuth, updateProposal);

// Customers
app.get('/api/customers', requireAuth, listCustomers);
app.get('/api/customers/:id', requireAuth, getCustomer);
app.post('/api/customers', requireAuth, createCustomer);
app.put('/api/customers/:id', requireAuth, updateCustomer);

// Quotes
app.get('/api/quotes', requireAuth, listQuotes);
app.get('/api/quotes/:id', requireAuth, getQuote);
app.post('/api/quotes', requireAuth, createQuote);
app.put('/api/quotes/:id', requireAuth, updateQuote);

// Projects
app.get('/api/projects', requireAuth, listProjects);
app.get('/api/projects/:id', requireAuth, getProject);
app.post('/api/projects', requireAuth, createProject);
app.put('/api/projects/:id', requireAuth, updateProject);

// Visits/Schedule
app.get('/api/visits', requireAuth, listVisits);
app.get('/api/visits/:id', requireAuth, getVisit);
app.post('/api/visits', requireAuth, createVisit);
app.put('/api/visits/:id', requireAuth, updateVisit);

// Measurements (from visits)
app.get('/api/visits/:visitId/measurement', requireAuth, getMeasurement);
app.post('/api/visits/:visitId/measurement', requireAuth, createOrUpdateMeasurement);
app.put('/api/visits/:visitId/measurement', requireAuth, createOrUpdateMeasurement);

// Activities
app.get('/api/activities', requireAuth, listActivities);
app.post('/api/activities', requireAuth, createActivity);

// Contracts/Financeiro
app.get('/api/contracts', requireAuth, listContracts);
app.get('/api/contracts/:id', requireAuth, getContract);
app.post('/api/contracts', requireAuth, createContract);
app.put('/api/contracts/:id', requireAuth, updateContract);

// Users
app.get('/api/users', requireAuth, listUsers);
app.get('/api/users/:id', requireAuth, getUser);
app.post('/api/users', requireAuth, createUser);
app.put('/api/users/:id', requireAuth, updateUser);

// Compatibility: system.php?api=receive-lead
app.all('/system.php', (req, res) => {
  if (req.query.api === 'receive-lead' && req.method === 'POST') return handleReceiveLead(req, res);
  if (req.query.api === 'db-check' && req.method === 'GET') return handleDbCheck(req, res);
  res.status(404).json({ error: 'Not found' });
});

// Error handling middleware
app.use((err, req, res, next) => {
  console.error('Unhandled error:', err);
  console.error('Stack:', err.stack);
  res.status(500).json({ 
    success: false, 
    error: 'Internal server error',
    message: process.env.NODE_ENV === 'development' ? err.message : undefined
  });
});

// 404 handler
app.use((req, res) => {
  if (req.path.startsWith('/api/')) {
    res.status(404).json({ success: false, error: 'Not found' });
  } else {
    res.status(404).send('Page not found');
  }
});

app.listen(PORT, '0.0.0.0', () => {
  console.log(`Senior Floors System running on port ${PORT}`);
  console.log('  Admin Panel: http://localhost:' + PORT);
  console.log('\n  API Endpoints:');
  console.log('  Dashboard: GET /api/dashboard/stats');
  console.log('  Leads: GET /api/leads, GET /api/leads/:id, PUT /api/leads/:id');
  console.log('  Customers: GET /api/customers, POST /api/customers, PUT /api/customers/:id');
  console.log('  Quotes: GET /api/quotes, POST /api/quotes, PUT /api/quotes/:id');
  console.log('  Projects: GET /api/projects, POST /api/projects, PUT /api/projects/:id');
  console.log('  Visits: GET /api/visits, POST /api/visits, PUT /api/visits/:id');
  console.log('  Activities: GET /api/activities, POST /api/activities');
  console.log('  Contracts: GET /api/contracts, POST /api/contracts, PUT /api/contracts/:id');
  console.log('  Users: GET /api/users, POST /api/users, PUT /api/users/:id');
});
