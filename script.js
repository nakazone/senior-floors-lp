/**
 * Senior Floors Landing Page - JavaScript
 * Conversion-optimized interactions and form handling
 */

(function() {
    'use strict';

    // ============================================
    // Mobile Menu Toggle
    // ============================================
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const nav = document.getElementById('nav');
    
    if (mobileMenuToggle && nav) {
        mobileMenuToggle.addEventListener('click', function() {
            mobileMenuToggle.classList.toggle('active');
            nav.classList.toggle('active');
        });

        // Close menu when clicking on a nav link
        const navLinks = nav.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                mobileMenuToggle.classList.remove('active');
                nav.classList.remove('active');
            });
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!nav.contains(e.target) && !mobileMenuToggle.contains(e.target)) {
                mobileMenuToggle.classList.remove('active');
                nav.classList.remove('active');
            }
        });
    }

    // ============================================
    // Header Scroll Effect (optional - adds shadow on scroll)
    // ============================================
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

    // ============================================
    // Sticky Mobile CTA - Shows on scroll down
    // ============================================
    const stickyCta = document.getElementById('stickyCta');
    let lastScrollY = window.scrollY;
    let isScrollingDown = false;

    function handleStickyCta() {
        // Only show on mobile devices (viewport width < 1024px)
        if (window.innerWidth >= 1024) {
            stickyCta.style.display = 'none';
            return;
        }

        const currentScrollY = window.scrollY;
        const scrollThreshold = 300; // Show after scrolling 300px

        // Determine scroll direction
        isScrollingDown = currentScrollY > lastScrollY;
        lastScrollY = currentScrollY;

        // Show sticky CTA when scrolled down past threshold
        if (currentScrollY > scrollThreshold && isScrollingDown) {
            stickyCta.style.display = 'flex';
        } else if (currentScrollY < scrollThreshold) {
            stickyCta.style.display = 'none';
        }
    }

    // Throttle scroll events for performance
    let scrollTimeout;
    window.addEventListener('scroll', function() {
        if (scrollTimeout) {
            window.cancelAnimationFrame(scrollTimeout);
        }
        scrollTimeout = window.requestAnimationFrame(handleStickyCta);
    });

    // Handle window resize
    window.addEventListener('resize', function() {
        handleStickyCta();
    });

    // Initial check
    handleStickyCta();

    // ============================================
    // Smooth Scroll for Anchor Links
    // ============================================
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            
            // Skip if it's just "#"
            if (href === '#' || href === '') {
                return;
            }

            const target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                
                // Calculate offset for sticky header
                const headerHeight = window.innerWidth < 768 ? 70 : 80;
                const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - headerHeight;

                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });

                // Hide sticky CTA when navigating to contact form
                if (href === '#contact' && stickyCta) {
                    stickyCta.style.display = 'none';
                }
            }
        });
    });

    // ============================================
    // Hero Form Handling
    // ============================================
    const heroForm = document.getElementById('heroForm');
    
    if (heroForm) {
        // Form validation and submission
        heroForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Get form data
            const formData = new FormData(heroForm);
            const data = {
                name: formData.get('name'),
                phone: formData.get('phone'),
                email: formData.get('email'),
                zipcode: formData.get('zipcode'),
                message: formData.get('message') || ''
            };

            // Validate required fields
            if (!validateForm(data)) {
                return;
            }

            // Show loading state
            const submitButton = heroForm.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.textContent = 'Sending...';

            // Simulate form submission (replace with actual API call)
            setTimeout(() => {
                // Success state
                showFormMessage('success', 'Thank you! We\'ll contact you within 24 hours.', heroForm);
                heroForm.reset();
                submitButton.disabled = false;
                submitButton.textContent = originalButtonText;

                // Track conversion
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'form_submission', {
                        'event_category': 'Contact',
                        'event_label': 'Hero Form'
                    });
                }
            }, 1000);
        });

        // Real-time validation feedback
        const requiredFields = heroForm.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            field.addEventListener('blur', function() {
                validateField(this);
            });

            field.addEventListener('input', function() {
                if (this.classList.contains('error')) {
                    this.classList.remove('error');
                    const errorMsg = this.parentElement.querySelector('.error-message');
                    if (errorMsg) {
                        errorMsg.remove();
                    }
                }
            });
        });
    }

    // ============================================
    // Contact Form Handling
    // ============================================
    const contactForm = document.getElementById('contactForm');
    
    if (contactForm) {
        // Form validation and submission
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Get form data
            const formData = new FormData(contactForm);
            const data = {
                name: formData.get('name'),
                phone: formData.get('phone'),
                email: formData.get('email'),
                zipcode: formData.get('zipcode'),
                message: formData.get('message')
            };

            // Validate required fields
            if (!validateForm(data)) {
                return;
            }

            // Show loading state
            const submitButton = contactForm.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.textContent = 'Sending...';

            // Simulate form submission (replace with actual API call)
            // In production, this would send data to your backend/email service
            setTimeout(() => {
                // Success state
                showFormMessage('success', 'Thank you! We\'ll contact you within 24 hours.');
                contactForm.reset();
                submitButton.disabled = false;
                submitButton.textContent = originalButtonText;

                // Track conversion (Google Analytics, Facebook Pixel, etc.)
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'form_submission', {
                        'event_category': 'Contact',
                        'event_label': 'Contact Form'
                    });
                }

                // Scroll to top of form to show success message
                contactForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 1000);
        });

        // Real-time validation feedback
        const requiredFields = contactForm.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            field.addEventListener('blur', function() {
                validateField(this);
            });

            field.addEventListener('input', function() {
                // Clear error state on input
                if (this.classList.contains('error')) {
                    this.classList.remove('error');
                    const errorMsg = this.parentElement.querySelector('.error-message');
                    if (errorMsg) {
                        errorMsg.remove();
                    }
                }
            });
        });
    }

    // Form validation function
    function validateForm(data) {
        let isValid = true;

        // Validate name
        if (!data.name || data.name.trim().length < 2) {
            showFieldError('name', 'Please enter your full name');
            isValid = false;
        }

        // Validate phone
        const phoneRegex = /^[\d\s\-\+\(\)]+$/;
        if (!data.phone || !phoneRegex.test(data.phone) || data.phone.replace(/\D/g, '').length < 10) {
            showFieldError('phone', 'Please enter a valid phone number');
            isValid = false;
        }

        // Validate email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!data.email || !emailRegex.test(data.email)) {
            showFieldError('email', 'Please enter a valid email address');
            isValid = false;
        }

        // Validate zipcode
        const zipcodeRegex = /^\d{5}(-\d{4})?$/;
        if (!data.zipcode || !zipcodeRegex.test(data.zipcode)) {
            // Try to find the zipcode field (could be hero-zipcode or zipcode)
            const zipcodeField = document.getElementById('hero-zipcode') || document.getElementById('zipcode');
            if (zipcodeField) {
                showFieldError(zipcodeField.id, 'Please enter a valid zip code (5 digits)');
            }
            isValid = false;
        }

        return isValid;
    }

    // Validate individual field
    function validateField(field) {
        const value = field.value.trim();
        let isValid = true;
        let errorMessage = '';

        if (field.hasAttribute('required') && !value) {
            isValid = false;
            errorMessage = 'This field is required';
        } else if (field.type === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                errorMessage = 'Please enter a valid email address';
            }
        } else if (field.type === 'tel' && value) {
            const phoneRegex = /^[\d\s\-\+\(\)]+$/;
            if (!phoneRegex.test(value) || value.replace(/\D/g, '').length < 10) {
                isValid = false;
                errorMessage = 'Please enter a valid phone number';
            }
        } else if (field.name === 'zipcode' && value) {
            const zipcodeRegex = /^\d{5}(-\d{4})?$/;
            if (!zipcodeRegex.test(value)) {
                isValid = false;
                errorMessage = 'Please enter a valid zip code (5 digits)';
            }
        }

        if (!isValid) {
            showFieldError(field.id, errorMessage);
        } else {
            clearFieldError(field.id);
        }

        return isValid;
    }

    // Show field error
    function showFieldError(fieldId, message) {
        const field = document.getElementById(fieldId);
        if (!field) return;

        field.classList.add('error');
        
        // Remove existing error message
        const existingError = field.parentElement.querySelector('.error-message');
        if (existingError) {
            existingError.remove();
        }

        // Add error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = message;
        errorDiv.style.color = '#dc3545';
        errorDiv.style.fontSize = '0.875rem';
        errorDiv.style.marginTop = '0.25rem';
        field.parentElement.appendChild(errorDiv);
    }

    // Clear field error
    function clearFieldError(fieldId) {
        const field = document.getElementById(fieldId);
        if (field) {
            field.classList.remove('error');
            const errorMsg = field.parentElement.querySelector('.error-message');
            if (errorMsg) {
                errorMsg.remove();
            }
        }
    }

    // Show form success/error message
    function showFormMessage(type, message, formElement) {
        // Use contactForm as default if formElement not provided
        const targetForm = formElement || contactForm;
        if (!targetForm) return;

        // Remove existing message
        const existingMsg = targetForm.parentElement.querySelector('.form-message');
        if (existingMsg) {
            existingMsg.remove();
        }

        // Create message element
        const messageDiv = document.createElement('div');
        messageDiv.className = `form-message form-message-${type}`;
        messageDiv.textContent = message;
        messageDiv.style.padding = '1rem';
        messageDiv.style.marginBottom = '1rem';
        messageDiv.style.borderRadius = '6px';
        messageDiv.style.textAlign = 'center';
        messageDiv.style.fontWeight = '500';

        if (type === 'success') {
            messageDiv.style.backgroundColor = '#d4edda';
            messageDiv.style.color = '#155724';
            messageDiv.style.border = '1px solid #c3e6cb';
        } else {
            messageDiv.style.backgroundColor = '#f8d7da';
            messageDiv.style.color = '#721c24';
            messageDiv.style.border = '1px solid #f5c6cb';
        }

        // Insert before form
        targetForm.parentElement.insertBefore(messageDiv, targetForm);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            messageDiv.remove();
        }, 5000);
    }

    // ============================================
    // Phone Number Formatting (Optional Enhancement)
    // ============================================
    function formatPhoneInput(input) {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            
            // Format as (XXX) XXX-XXXX
            if (value.length > 0) {
                if (value.length <= 3) {
                    value = `(${value}`;
                } else if (value.length <= 6) {
                    value = `(${value.slice(0, 3)}) ${value.slice(3)}`;
                } else {
                    value = `(${value.slice(0, 3)}) ${value.slice(3, 6)}-${value.slice(6, 10)}`;
                }
            }
            
            e.target.value = value;
        });
    }

    // Apply to both hero and contact forms
    const phoneInput = document.getElementById('phone');
    const heroPhoneInput = document.getElementById('hero-phone');
    
    if (phoneInput) {
        formatPhoneInput(phoneInput);
    }
    
    if (heroPhoneInput) {
        formatPhoneInput(heroPhoneInput);
    }

    // ============================================
    // Intersection Observer for Animations (Optional)
    // Fade in elements as they come into view
    // ============================================
    if ('IntersectionObserver' in window) {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe cards and sections for fade-in effect
        const animatedElements = document.querySelectorAll('.service-card, .testimonial-card, .benefit-item, .process-step');
        animatedElements.forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });
    }

    // ============================================
    // Track CTA Clicks for Analytics
    // ============================================
    document.querySelectorAll('.btn-primary, .btn-secondary').forEach(button => {
        button.addEventListener('click', function() {
            const buttonText = this.textContent.trim();
            const buttonType = this.classList.contains('btn-primary') ? 'Primary' : 'Secondary';
            
            // Track in Google Analytics if available
            if (typeof gtag !== 'undefined') {
                gtag('event', 'cta_click', {
                    'event_category': 'Engagement',
                    'event_label': `${buttonType} - ${buttonText}`,
                    'value': 1
                });
            }

            // Track phone clicks separately
            if (this.href && this.href.startsWith('tel:')) {
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'phone_click', {
                        'event_category': 'Contact',
                        'event_label': 'Phone Call CTA'
                    });
                }
            }
        });
    });

    // ============================================
    // Performance: Lazy load images if added later
    // ============================================
    if ('loading' in HTMLImageElement.prototype) {
        const images = document.querySelectorAll('img[data-src]');
        images.forEach(img => {
            img.src = img.dataset.src;
        });
    }

    // ============================================
    // Gallery Slider
    // ============================================
    const gallerySlider = document.getElementById('gallerySlider');
    const galleryItems = document.querySelectorAll('.gallery-item');
    const galleryPrev = document.getElementById('galleryPrev');
    const galleryNext = document.getElementById('galleryNext');
    const galleryDescriptions = document.querySelectorAll('.gallery-description');
    let currentGalleryIndex = 0;

    function showGalleryImage(index) {
        // Remove active class from all items and descriptions
        galleryItems.forEach(item => item.classList.remove('active'));
        galleryDescriptions.forEach(desc => desc.classList.remove('active'));

        // Add active class to current item and description
        if (galleryItems[index]) {
            galleryItems[index].classList.add('active');
        }
        if (galleryDescriptions[index]) {
            galleryDescriptions[index].classList.add('active');
        }

        currentGalleryIndex = index;
    }

    function nextGalleryImage() {
        const nextIndex = (currentGalleryIndex + 1) % galleryItems.length;
        showGalleryImage(nextIndex);
    }

    function prevGalleryImage() {
        const prevIndex = (currentGalleryIndex - 1 + galleryItems.length) % galleryItems.length;
        showGalleryImage(prevIndex);
    }

    if (galleryNext && galleryPrev && galleryItems.length > 0) {
        galleryNext.addEventListener('click', nextGalleryImage);
        galleryPrev.addEventListener('click', prevGalleryImage);
    }

    console.log('Senior Floors landing page initialized');
})();
