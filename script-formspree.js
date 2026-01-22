/**
 * Senior Floors Landing Page - JavaScript with Formspree
 * 
 * INSTRUCTIONS:
 * 1. Go to https://formspree.io and create a free account
 * 2. Create a new form
 * 3. Set email to: leads@senior-floors.com
 * 4. Copy your Formspree endpoint (e.g., https://formspree.io/f/YOUR_FORM_ID)
 * 5. Replace 'YOUR_FORMSPREE_ENDPOINT_HERE' below with your actual endpoint
 * 6. Rename this file to script.js (backup the old one first)
 */

// ============================================
// FORMPREE CONFIGURATION
// ============================================
// Replace this with your Formspree endpoint
const FORMSPREE_ENDPOINT = 'YOUR_FORMSPREE_ENDPOINT_HERE'; // e.g., 'https://formspree.io/f/abc123xyz'

// ============================================
// Rest of the code (same as before)
// ============================================

(function() {
    'use strict';

    // Mobile Menu Toggle
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const nav = document.getElementById('nav');
    
    if (mobileMenuToggle && nav) {
        mobileMenuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            const isActive = mobileMenuToggle.classList.toggle('active');
            nav.classList.toggle('active', isActive);
            
            if (isActive) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        });

        const navLinks = nav.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                mobileMenuToggle.classList.remove('active');
                nav.classList.remove('active');
                document.body.style.overflow = '';
            });
        });

        document.addEventListener('click', function(e) {
            if (nav.classList.contains('active') && 
                !nav.contains(e.target) && 
                !mobileMenuToggle.contains(e.target)) {
                mobileMenuToggle.classList.remove('active');
                nav.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
        
        window.addEventListener('resize', function() {
            if (window.innerWidth > 767) {
                mobileMenuToggle.classList.remove('active');
                nav.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    }

    // Header Scroll Effect
    const header = document.getElementById('header');
    if (header) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                header.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.3)';
            } else {
                header.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.2)';
            }
        });
    }

    // Sticky Mobile CTA
    const stickyCta = document.getElementById('stickyCta');
    let lastScrollY = window.scrollY;
    let isScrollingDown = false;

    function handleStickyCta() {
        if (window.innerWidth >= 1024) {
            stickyCta.style.display = 'none';
            return;
        }

        const currentScrollY = window.scrollY;
        const scrollThreshold = 300;

        isScrollingDown = currentScrollY > lastScrollY;
        lastScrollY = currentScrollY;

        if (currentScrollY > scrollThreshold && isScrollingDown) {
            stickyCta.style.display = 'flex';
        } else if (currentScrollY < scrollThreshold) {
            stickyCta.style.display = 'none';
        }
    }

    let scrollTimeout;
    window.addEventListener('scroll', function() {
        if (scrollTimeout) {
            window.cancelAnimationFrame(scrollTimeout);
        }
        scrollTimeout = window.requestAnimationFrame(handleStickyCta);
    });

    window.addEventListener('resize', function() {
        handleStickyCta();
    });

    handleStickyCta();

    // Smooth Scroll
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            
            if (href === '#' || href === '') {
                return;
            }

            const target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                
                const headerHeight = window.innerWidth < 768 ? 70 : 80;
                const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - headerHeight;

                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });

                if (href === '#contact' && stickyCta) {
                    stickyCta.style.display = 'none';
                }
            }
        });
    });

    // Helper function to submit to Formspree
    function submitToFormspree(formData, formName) {
        // Convert FormData to object for Formspree
        const data = {
            name: formData.get('name'),
            phone: formData.get('phone'),
            email: formData.get('email'),
            zipcode: formData.get('zipcode'),
            message: formData.get('message') || '',
            'form-name': formName,
            '_subject': 'New Lead from Senior Floors - ' + (formName === 'hero-form' ? 'Hero Form' : 'Contact Form')
        };

        return fetch(FORMSPREE_ENDPOINT, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        });
    }

    // Hero Form Handling
    const heroForm = document.getElementById('heroForm');
    
    if (heroForm) {
        heroForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(heroForm);
            const data = {
                name: formData.get('name'),
                phone: formData.get('phone'),
                email: formData.get('email'),
                zipcode: formData.get('zipcode'),
                message: formData.get('message') || ''
            };

            // Validate
            if (!validateForm(data)) {
                return;
            }

            const submitButton = heroForm.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.textContent = 'Sending...';

            submitToFormspree(formData, 'hero-form')
            .then(response => response.json())
            .then(data => {
                if (data.ok || data.success) {
                    showFormMessage('success', 'Thank you! We\'ll contact you within 24 hours.', heroForm);
                    heroForm.reset();
                    
                    if (typeof gtag !== 'undefined') {
                        gtag('event', 'form_submission', {
                            'event_category': 'Contact',
                            'event_label': 'Hero Form'
                        });
                    }
                } else {
                    throw new Error(data.error || 'Form submission failed');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showFormMessage('error', 'Something went wrong. Please try again or call us at (720) 751-9813.', heroForm);
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.textContent = originalButtonText;
            });
        });
    }

    // Contact Form Handling
    const contactForm = document.getElementById('contactForm');
    
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(contactForm);
            const data = {
                name: formData.get('name'),
                phone: formData.get('phone'),
                email: formData.get('email'),
                zipcode: formData.get('zipcode'),
                message: formData.get('message')
            };

            if (!validateForm(data)) {
                return;
            }

            const submitButton = contactForm.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.textContent = 'Sending...';

            submitToFormspree(formData, 'contact-form')
            .then(response => response.json())
            .then(data => {
                if (data.ok || data.success) {
                    showFormMessage('success', 'Thank you! We\'ll contact you within 24 hours.');
                    contactForm.reset();
                    
                    if (typeof gtag !== 'undefined') {
                        gtag('event', 'form_submission', {
                            'event_category': 'Contact',
                            'event_label': 'Contact Form'
                        });
                    }

                    contactForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
                } else {
                    throw new Error(data.error || 'Form submission failed');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showFormMessage('error', 'Something went wrong. Please try again or call us at (720) 751-9813.');
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.textContent = originalButtonText;
            });
        });
    }

    // Include all other functions from original script.js
    // (validateForm, showFormMessage, etc.)
    // ... (copy the rest from the original script.js)

})();
