# Senior Floors - High-Conversion Landing Page

A production-ready, conversion-optimized landing page for Senior Floors, designed specifically for Google Ads traffic.

## Features

- **Mobile-First Design**: Fully responsive across all devices
- **Fast Load Speed**: Optimized HTML, CSS, and JavaScript
- **Conversion-Focused**: Multiple strategic CTAs throughout the page
- **Trust Signals**: Testimonials, ratings, and credentials prominently displayed
- **SEO-Friendly**: Semantic HTML structure with proper meta tags
- **Accessibility**: WCAG-compliant with keyboard navigation support
- **Form Validation**: Real-time validation with user-friendly error messages
- **Sticky Mobile CTA**: Always-visible call-to-action on mobile devices

## File Structure

```
.
├── index.html      # Main HTML structure
├── styles.css      # All styling (mobile-first, responsive)
├── script.js       # Interactive features and form handling
├── server.py       # Python HTTP server (optional)
├── start-server.sh # Bash script to start local server
├── package.json    # Node.js server configuration (optional)
└── README.md       # This file
```

## Local Development Server

### Quick Start (Recommended)

**Option 1: Use the bash script (easiest)**
```bash
./start-server.sh
```

**Option 2: Use Python server**
```bash
python3 server.py
# or
python3 -m http.server 8000
```

**Option 3: Use Node.js (if you have Node installed)**
```bash
npm start
# or
npx http-server -p 8000 -o
```

All methods will start a server at `http://localhost:8000` and automatically open it in your browser.

## Setup Instructions

1. **Basic Setup**: Simply open `index.html` in a web browser or serve via a web server.

2. **For Production Deployment**:
   - Update the phone number `(555) 123-4567` throughout the HTML
   - Update the email address `info@seniorfloors.com` in the footer
   - Replace placeholder content with actual business information
   - Connect the contact form to your backend/email service

3. **Form Submission**: 
   - Currently uses a simulated submission (see `script.js` line ~60)
   - Replace the `setTimeout` block with actual API call to your backend
   - Example integration points:
     ```javascript
     // Replace this section in script.js:
     fetch('/api/contact', {
         method: 'POST',
         headers: { 'Content-Type': 'application/json' },
         body: JSON.stringify(data)
     })
     .then(response => response.json())
     .then(result => {
         // Handle success
     })
     .catch(error => {
         // Handle error
     });
     ```

## Customization

### Colors
Edit CSS variables in `styles.css`:
```css
:root {
    --primary-color: #1a5f3f;    /* Main brand color */
    --secondary-color: #d4af37;  /* Accent/CTA color */
    /* ... other variables */
}
```

### Content
- Update testimonials in the Social Proof section
- Modify service descriptions in the Services section
- Adjust process steps in the Process section
- Update trust badges and credentials

### Phone Number
Search and replace `(555) 123-4567` with your actual phone number.

## Performance Optimizations

- **Minimal Dependencies**: Only uses Google Fonts (Inter) - can be self-hosted for better performance
- **Optimized CSS**: Mobile-first approach reduces initial load
- **Throttled Scroll Events**: Uses `requestAnimationFrame` for smooth performance
- **Lazy Loading Ready**: Structure supports lazy loading if images are added later

## Analytics Integration

The page includes Google Analytics event tracking hooks. To enable:

1. Add Google Analytics script to `<head>`:
```html
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=GA_MEASUREMENT_ID"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'GA_MEASUREMENT_ID');
</script>
```

2. Events are automatically tracked:
   - Form submissions
   - CTA button clicks
   - Phone number clicks

## SEO Considerations

- Semantic HTML5 structure
- Proper heading hierarchy (H1 ? H2 ? H3)
- Meta description and keywords
- Alt text ready for images (add when images are included)
- Schema.org markup can be added for local business

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Conversion Optimization Features

1. **Above-the-Fold Value Proposition**: Clear headline and subheadline immediately visible
2. **Multiple CTAs**: Primary and secondary CTAs repeated strategically
3. **Trust Elements**: Licensed, insured, experience badges in hero
4. **Social Proof**: Testimonials and ratings early in the page
5. **Low-Friction Form**: Simple, clean contact form with clear value proposition
6. **Urgency Elements**: "Free estimate", "No obligation" messaging
7. **Mobile Sticky CTA**: Always-visible action buttons on mobile

## Next Steps for Production

1. **Add Background Image**: Replace the Unsplash placeholder in `styles.css` (line ~153) with your own hardwood flooring image. Update the `background-image` URL in the `.hero` class.
2. **Add Real Images**: Replace emoji icons with actual service images
2. **Backend Integration**: Connect form to email service (SendGrid, Mailgun, etc.) or CRM
3. **A/B Testing**: Test different headlines, CTAs, and form layouts
4. **Heatmap Tracking**: Add Hotjar or similar to understand user behavior
5. **Schema Markup**: Add LocalBusiness schema for better local SEO
6. **Page Speed**: Run through PageSpeed Insights and optimize further
7. **SSL Certificate**: Ensure HTTPS for security and SEO
8. **CDN**: Use a CDN for faster global delivery

## License

This landing page template is ready for commercial use. Customize as needed for your business.
