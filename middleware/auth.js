/**
 * Authentication middleware - protect routes
 */
export function requireAuth(req, res, next) {
  if (req.session && req.session.userId) {
    return next();
  }
  
  // Se for requisição API, retornar JSON
  if (req.path.startsWith('/api/')) {
    return res.status(401).json({ success: false, error: 'Authentication required' });
  }
  
  // Se for página web, redirecionar para login
  res.redirect('/login.html');
}

export function requireRole(...roles) {
  return (req, res, next) => {
    if (!req.session || !req.session.userId) {
      if (req.path.startsWith('/api/')) {
        return res.status(401).json({ success: false, error: 'Authentication required' });
      }
      return res.redirect('/login.html');
    }
    
    if (!roles.includes(req.session.userRole)) {
      if (req.path.startsWith('/api/')) {
        return res.status(403).json({ success: false, error: 'Insufficient permissions' });
      }
      return res.status(403).send('Access denied');
    }
    
    next();
  };
}
