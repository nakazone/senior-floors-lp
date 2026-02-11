/**
 * Authentication routes - Login, Logout, Session
 */
import bcrypt from 'bcryptjs';
import { getDBConnection } from '../config/db.js';

export async function login(req, res) {
  const { email, password } = req.body;

  if (!email || !password) {
    return res.status(400).json({ success: false, error: 'Email and password required' });
  }

  try {
    const pool = await getDBConnection();
    if (!pool) {
      return res.status(503).json({ success: false, error: 'Database not available' });
    }

    // Buscar usuário (tentar password_hash ou password)
    const [users] = await pool.query(
      `SELECT id, name, email, password_hash, password, role, is_active 
       FROM users 
       WHERE email = ? AND is_active = 1 
       LIMIT 1`,
      [email.toLowerCase().trim()]
    );

    if (users.length === 0) {
      return res.status(401).json({ success: false, error: 'Invalid email or password' });
    }

    const user = users[0];
    const storedPassword = user.password_hash || user.password;

    if (!storedPassword) {
      // Usuário sem senha - permitir login se for admin (primeira vez)
      if (user.role === 'admin') {
        req.session.userId = user.id;
        req.session.userEmail = user.email;
        req.session.userRole = user.role;
        req.session.userName = user.name;
        
        return res.json({
          success: true,
          user: {
            id: user.id,
            name: user.name,
            email: user.email,
            role: user.role
          },
          message: 'Logged in (no password set - please set one)'
        });
      }
      return res.status(401).json({ success: false, error: 'Password not set for this user' });
    }

    // Verificar senha (bcrypt)
    const valid = await bcrypt.compare(password, storedPassword);
    if (!valid) {
      return res.status(401).json({ success: false, error: 'Invalid email or password' });
    }

    // Criar sessão
    req.session.userId = user.id;
    req.session.userEmail = user.email;
    req.session.userRole = user.role;
    req.session.userName = user.name;

    // Atualizar last_login
    try {
      await pool.query(
        `UPDATE users SET last_login = NOW() WHERE id = ?`,
        [user.id]
      );
    } catch (e) {
      // Ignorar erro se coluna não existir
    }

    res.json({
      success: true,
      user: {
        id: user.id,
        name: user.name,
        email: user.email,
        role: user.role
      }
    });
  } catch (error) {
    console.error('Login error:', error);
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
}

export async function logout(req, res) {
  req.session.destroy((err) => {
    if (err) {
      return res.status(500).json({ success: false, error: 'Could not logout' });
    }
    res.json({ success: true, message: 'Logged out' });
  });
}

export async function checkSession(req, res) {
  if (req.session.userId) {
    res.json({
      success: true,
      authenticated: true,
      user: {
        id: req.session.userId,
        email: req.session.userEmail,
        role: req.session.userRole,
        name: req.session.userName
      }
    });
  } else {
    res.json({
      success: true,
      authenticated: false
    });
  }
}
