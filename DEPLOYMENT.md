# Deployment Guide for Senior Floors Landing Page

This is a static HTML/CSS/JavaScript site that can be deployed to various platforms. Choose the option that works best for you.

## 🚀 Quick Deploy Options

### Option 1: Netlify (Recommended - Easiest)

**Method A: Drag & Drop (Fastest)**
1. Go to [https://app.netlify.com](https://app.netlify.com)
2. Sign up/login (free)
3. Drag and drop your entire project folder onto the Netlify dashboard
4. Your site will be live in seconds with a URL like `your-site-name.netlify.app`
5. You can add a custom domain later

**Method B: Git-based (Recommended for updates)**
1. Push your code to GitHub:
   ```bash
   git remote add origin https://github.com/yourusername/senior-floors-landing.git
   git push -u origin main
   ```
2. Go to [https://app.netlify.com](https://app.netlify.com)
3. Click "Add new site" → "Import an existing project"
4. Connect your GitHub repository
5. Netlify will auto-detect settings (publish directory: root, build command: none)
6. Click "Deploy site"
7. Your site is live! Every git push will auto-deploy

**Custom Domain:**
- In Netlify dashboard → Site settings → Domain management
- Add your custom domain (e.g., seniorfloors.com)
- Follow DNS instructions

---

### Option 2: Vercel (Also Great for Static Sites)

1. Install Vercel CLI:
   ```bash
   npm i -g vercel
   ```
2. In your project directory:
   ```bash
   vercel
   ```
3. Follow the prompts (no build command needed)
4. Your site will be live at `your-site-name.vercel.app`

**Or use GitHub:**
1. Push to GitHub
2. Go to [https://vercel.com](https://vercel.com)
3. Import your GitHub repository
4. Deploy automatically

---

### Option 3: GitHub Pages (Free with GitHub)

1. Push your code to GitHub:
   ```bash
   git remote add origin https://github.com/yourusername/senior-floors-landing.git
   git push -u origin main
   ```
2. Go to your repository on GitHub
3. Settings → Pages
4. Source: Deploy from a branch → main branch → / (root)
5. Save
6. Your site will be at `yourusername.github.io/senior-floors-landing`

---

### Option 4: Cloudflare Pages (Fast & Free)

1. Push to GitHub
2. Go to [https://dash.cloudflare.com](https://dash.cloudflare.com)
3. Pages → Create a project
4. Connect GitHub repository
5. Build settings:
   - Framework preset: None
   - Build command: (leave empty)
   - Build output directory: /
6. Deploy
7. Your site will be at `your-project.pages.dev`

---

## 📋 Pre-Deployment Checklist

- [x] Git repository initialized
- [x] All files committed
- [ ] Test site locally (`npm start`)
- [ ] Verify all images load correctly
- [ ] Check mobile responsiveness
- [ ] Test contact forms (if applicable)
- [ ] Verify phone numbers and links work

## 🔧 Local Testing

Before deploying, test locally:
```bash
npm start
```
Then visit `http://localhost:8000`

## 🌐 Custom Domain Setup

After deploying, you can add a custom domain:

1. **Netlify/Vercel**: Add domain in dashboard → DNS settings
2. **Update DNS records** at your domain registrar:
   - Add CNAME or A record pointing to your hosting provider
3. **SSL Certificate**: Automatically provided by most platforms

## 📊 Performance Tips

- All images are already optimized
- CSS and JS are minified-ready
- Fonts are loaded via Google Fonts CDN
- Site is mobile-responsive

## 🆘 Need Help?

- **Netlify Docs**: https://docs.netlify.com
- **Vercel Docs**: https://vercel.com/docs
- **GitHub Pages Docs**: https://docs.github.com/pages

---

**Recommended**: Start with **Netlify drag & drop** for the fastest deployment, then switch to Git-based deployment for easier updates.
