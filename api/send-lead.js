/**
 * Vercel Serverless: POST /api/send-lead
 * Recebe envio dos formulários da LP (Hero e Contact), valida (incl. raio 50 mi de Denver), envia para SYSTEM_API_URL e opcionalmente por email (SMTP).
 */

import { isZipWithin50MilesOfDenver } from '../utils/denver-radius.js';
 *
 * SMTP (receber leads por email): configure no Vercel (Settings → Environment Variables):
 *   SMTP_HOST     - servidor (ex: smtp.gmail.com)
 *   SMTP_PORT     - porta (ex: 587)
 *   SMTP_USER     - usuário / email de login
 *   SMTP_PASS     - senha ou App Password (Gmail: use App Password)
 *   SMTP_FROM_NAME  - nome no "De:" (opcional)
 *   SMTP_FROM_EMAIL - email no "De:" (opcional; default: SMTP_USER)
 *   SMTP_TO_EMAIL   - email que recebe os leads (opcional; default: SMTP_FROM_EMAIL)
 *   SMTP_SECURE   - "true" para porta 465 (opcional)
 */
function parseBody(req) {
  if (req.body && typeof req.body === 'object' && (req.body.name != null || req.body.email != null)) {
    return req.body;
  }
  let raw = req.body;
  if (typeof raw === 'string' && raw.length > 0) {
    const params = new URLSearchParams(raw);
    const o = {};
    for (const [k, v] of params.entries()) o[k] = v;
    return o;
  }
  if (Buffer.isBuffer(raw) && raw.length > 0) {
    raw = raw.toString('utf8');
    const params = new URLSearchParams(raw);
    const o = {};
    for (const [k, v] of params.entries()) o[k] = v;
    return o;
  }
  return {};
}

function csvEscape(s) {
  return `"${String(s).replace(/"/g, '""')}"`;
}

export default async function handler(req, res) {
  res.setHeader('Content-Type', 'application/json; charset=UTF-8');
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Accept');
  res.setHeader('Access-Control-Max-Age', '86400');

  if (req.method === 'OPTIONS') {
    return res.status(200).end();
  }

  if (req.method !== 'POST') {
    return res.status(405).json({ success: false, message: 'Method not allowed' });
  }

  const post = parseBody(req);
  const form_name = (post['form-name'] || post.formName || 'contact-form').trim();
  let name = (post.name || '').trim();
  let phone = (post.phone || '').trim();
  let email = (post.email || '').trim();
  let project_type = (post.project_type || post.projectType || '').trim();
  let zipcode = (post.zipcode || '').trim();
  let message = (post.message || '').trim();

  const errors = [];
  if (!name || name.length < 2) errors.push('Name is required and must be at least 2 characters');
  if (!phone) errors.push('Phone number is required');
  if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) errors.push('Valid email address is required');
  const zipDigits = (zipcode || '').replace(/\D/g, '');
  if (!zipDigits || zipDigits.length < 5) errors.push('Valid 5-digit US zip code is required');
  if (errors.length > 0) {
    return res.status(400).json({ success: false, message: errors.join(', ') });
  }

  zipcode = zipDigits.slice(0, 5);

  const radiusCheck = await isZipWithin50MilesOfDenver(zipcode);
  if (!radiusCheck.inRange) {
    const msg =
      radiusCheck.error ||
      `We currently serve only areas within 50 miles of Denver, CO. Your location is about ${radiusCheck.distanceMiles ?? '?'} miles away.`;
    return res.status(400).json({ success: false, message: msg, zip_out_of_range: true });
  }

  let system_sent = false;
  let system_database_saved = false;
  let system_error = '';
  let lead_id = null;
  const systemUrl = (process.env.SYSTEM_API_URL || '').trim().replace(/\/$/, '');

  if (systemUrl) {
    try {
      const url = `${systemUrl}/api/receive-lead`;
      const body = new URLSearchParams({
        'form-name': form_name,
        name,
        phone,
        email,
        ...(project_type ? { project_type } : {}),
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
        system_database_saved = data.database_saved === true;
        if (data.lead_id) lead_id = data.lead_id;
      } else {
        const errorText = await r.text();
        system_error = `HTTP ${r.status}: ${errorText.substring(0, 150)}`;
      }
    } catch (e) {
      system_error = e.message || 'Request failed';
    }
  }

  let csv_saved = false;
  try {
    const fs = await import('fs');
    const path = await import('path');
    const csvDir = '/tmp';
    const csvPath = path.join(csvDir, 'leads.csv');
    const csvLine = [new Date().toISOString().slice(0, 19).replace('T', ' '), form_name, name, phone, email, project_type || '', zipcode, (message || '').replace(/\r?\n/g, ' ')];
    if (!fs.existsSync(csvPath)) {
      fs.writeFileSync(csvPath, 'Date,Form,Name,Phone,Email,ProjectType,ZipCode,Message\n');
    }
    fs.appendFileSync(csvPath, csvLine.map(csvEscape).join(',') + '\n');
    csv_saved = true;
  } catch (_) {}

  let email_sent = false;
  let email_error = null;
  const smtpHost = (process.env.SMTP_HOST || '').trim();
  const smtpUser = (process.env.SMTP_USER || '').trim();
  const smtpPass = (process.env.SMTP_PASS || '').trim().replace(/\s+/g, '');
  const smtpConfigured = smtpHost && smtpUser && smtpPass && smtpPass !== 'YOUR_APP_PASSWORD_HERE';
  if (smtpConfigured) {
    try {
      const nodemailer = (await import('nodemailer')).default;
      const port = Number(process.env.SMTP_PORT) || 587;
      const secure = process.env.SMTP_SECURE === 'true';
      const transport = nodemailer.createTransport({
        host: smtpHost,
        port,
        secure,
        auth: { user: smtpUser, pass: smtpPass },
        ...(port === 465 ? {} : { requireTLS: true }),
      });
      const fromName = (process.env.SMTP_FROM_NAME || 'Senior Floors LP').trim();
      const fromEmail = (process.env.SMTP_FROM_EMAIL || smtpUser).trim();
      const toEmail = (process.env.SMTP_TO_EMAIL || fromEmail).trim();
      const formLabel = form_name === 'hero-form' ? 'Hero Form' : 'Contact Form';
      await transport.sendMail({
        from: `"${fromName}" <${fromEmail}>`,
        to: toEmail,
        replyTo: `${name} <${email}>`,
        subject: `New Lead - ${formLabel} - ${name}`,
        text: `New Lead\n\nForm: ${form_name}\nName: ${name}\nPhone: ${phone}\nEmail: ${email}\nProject Type: ${project_type || '(not specified)'}\nZip: ${zipcode}\n\nMessage:\n${message || '(none)'}\n\n---\n${new Date().toLocaleString()}`,
        html: `<h2>New Lead - ${formLabel}</h2><p><strong>Name:</strong> ${name}</p><p><strong>Phone:</strong> ${phone}</p><p><strong>Email:</strong> <a href="mailto:${email}">${email}</a></p><p><strong>Project Type:</strong> ${project_type || '(not specified)'}</p><p><strong>Zip:</strong> ${zipcode}</p>${message ? `<p><strong>Message:</strong><br>${String(message).replace(/\n/g, '<br>')}</p>` : ''}<hr><p><small>${new Date().toLocaleString()}</small></p>`,
      });
      email_sent = true;
    } catch (e) {
      email_error = e.code || e.message || String(e);
    }
  } else if (process.env.SMTP_HOST || process.env.SMTP_USER) {
    email_error = 'SMTP not fully configured (need SMTP_HOST, SMTP_USER, SMTP_PASS)';
  }

  const response = {
    success: true,
    message: "Thank you! We'll contact you within 24 hours.",
    email_sent,
    system_sent,
    system_database_saved,
    database_saved: system_database_saved,
    csv_saved,
    lead_id,
    timestamp: new Date().toISOString().slice(0, 19).replace('T', ' '),
  };
  if (system_error) response.system_error = system_error;
  if (email_error) response.email_error = email_error;
  return res.status(200).json(response);
}
