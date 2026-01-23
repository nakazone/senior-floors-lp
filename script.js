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
        mobileMenuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            const isActive = mobileMenuToggle.classList.toggle('active');
            nav.classList.toggle('active', isActive);
            
            // Prevent body scroll when menu is open
            if (isActive) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        });

        // Close menu when clicking on a nav link
        const navLinks = nav.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                mobileMenuToggle.classList.remove('active');
                nav.classList.remove('active');
                document.body.style.overflow = '';
            });
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (nav.classList.contains('active') && 
                !nav.contains(e.target) && 
                !mobileMenuToggle.contains(e.target)) {
                mobileMenuToggle.classList.remove('active');
                nav.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
        
        // Close menu on window resize (if resizing to desktop)
        window.addEventListener('resize', function() {
            if (window.innerWidth > 767) {
                mobileMenuToggle.classList.remove('active');
                nav.classList.remove('active');
                document.body.style.overflow = '';
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
    // Hero Form Handling (Using same code as test-form.html)
    // ============================================
    const heroForm = document.getElementById('heroForm');
    const heroSuccessMessage = document.getElementById('heroSuccessMessage');
    const heroErrorMessage = document.getElementById('heroErrorMessage');
    
    if (heroForm) {
        const submitBtn = heroForm.querySelector('button[type="submit"]');
        
        // Hide messages initially
        if (heroSuccessMessage) heroSuccessMessage.classList.remove('show');
        if (heroErrorMessage) heroErrorMessage.classList.remove('show');
        
        // Hide all error messages on page load
        const heroErrorMessages = heroForm.querySelectorAll('.error-message');
        heroErrorMessages.forEach(errorMsg => {
            errorMsg.classList.remove('show');
        });
        
        // Remove error styling on input
        const inputs = heroForm.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('input', () => {
                input.classList.remove('error');
                const errorDiv = document.getElementById(input.id + 'Error');
                if (errorDiv) {
                    errorDiv.classList.remove('show');
                }
            });
        });

        // Handle form submission - support both click and touch events
        const handleFormSubmit = async (e) => {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            console.log('Hero form submitted - Device:', /Mobile|Android|iPhone|iPad/.test(navigator.userAgent) ? 'Mobile' : 'Desktop');
            console.log('Hero form submit event:', e ? e.type : 'manual');

            // Hide previous messages
            if (heroSuccessMessage) heroSuccessMessage.classList.remove('show');
            if (heroErrorMessage) heroErrorMessage.classList.remove('show');

            // Remove all error styling
            inputs.forEach(input => {
                input.classList.remove('error');
                const errorDiv = document.getElementById(input.id + 'Error');
                if (errorDiv) {
                    errorDiv.classList.remove('show');
                }
            });

            // Get form data - read directly from inputs for better mobile compatibility
            const nameInput = document.getElementById('hero-name');
            const emailInput = document.getElementById('hero-email');
            const phoneInput = document.getElementById('hero-phone');
            const zipcodeInput = document.getElementById('hero-zipcode');

            const name = (nameInput ? nameInput.value : '').trim();
            const email = (emailInput ? emailInput.value : '').trim();
            const phone = (phoneInput ? phoneInput.value : '').trim();
            const zipcode = (zipcodeInput ? zipcodeInput.value : '').trim();

            // Create FormData
            const formData = new FormData(heroForm);
            formData.append('form-name', 'hero-form');

            // Validate
            let hasErrors = false;

            // Name validation
            if (!name || name.length < 2) {
                if (nameInput) nameInput.classList.add('error');
                const errorDiv = document.getElementById('hero-nameError');
                if (errorDiv) errorDiv.classList.add('show');
                hasErrors = true;
            }

            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!email || !emailRegex.test(email)) {
                if (emailInput) emailInput.classList.add('error');
                const errorDiv = document.getElementById('hero-emailError');
                if (errorDiv) errorDiv.classList.add('show');
                hasErrors = true;
            }

            // Phone validation
            if (!phone || phone.replace(/\D/g, '').length < 10) {
                if (phoneInput) phoneInput.classList.add('error');
                const errorDiv = document.getElementById('hero-phoneError');
                if (errorDiv) errorDiv.classList.add('show');
                hasErrors = true;
            }

            // Zipcode validation - more flexible for mobile
            const zipcodeClean = zipcode.replace(/\D/g, ''); // Remove all non-digits
            if (!zipcodeClean || zipcodeClean.length < 5 || zipcodeClean.length > 9) {
                if (zipcodeInput) zipcodeInput.classList.add('error');
                const errorDiv = document.getElementById('hero-zipcodeError');
                if (errorDiv) errorDiv.classList.add('show');
                hasErrors = true;
            }

            if (hasErrors) {
                // Scroll to first error on mobile
                if (/Mobile|Android|iPhone|iPad/.test(navigator.userAgent)) {
                    const firstError = heroForm.querySelector('.error');
                    if (firstError) {
                        setTimeout(() => {
                            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            firstError.focus();
                        }, 100);
                    }
                }
                return;
            }

            // Disable submit button and show loading
            submitBtn.disabled = true;
            submitBtn.style.pointerEvents = 'none';
            const originalButtonText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="loading"></span>Submitting...';

            try {
                // Use fetch with better mobile support
                const fetchOptions = {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json'
                    }
                };

                // Add timeout for mobile networks (if supported)
                let timeoutId;
                if (typeof AbortController !== 'undefined') {
                    const controller = new AbortController();
                    timeoutId = setTimeout(() => controller.abort(), 30000);
                    fetchOptions.signal = controller.signal;
                }

                const response = await fetch('send-lead.php', fetchOptions);
                
                // Clear timeout if request succeeded
                if (timeoutId) clearTimeout(timeoutId);

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const text = await response.text();
                let data;

                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('JSON parse error:', e, 'Response:', text);
                    // If response is not JSON, check status
                    if (response.status === 404) {
                        throw new Error('Form handler not found (404). Please check if send-lead.php is uploaded correctly.');
                    } else if (response.status === 500) {
                        throw new Error('Server error (500). Please check PHP configuration or contact support.');
                    } else {
                        throw new Error('Unexpected response from server. Please try again.');
                    }
                }

                if (data.success) {
                    // Success!
                    if (heroSuccessMessage) heroSuccessMessage.classList.add('show');
                    heroForm.reset();
                    
                    // Scroll to show success message - better mobile handling
                    if (/Mobile|Android|iPhone|iPad/.test(navigator.userAgent)) {
                        // On mobile, scroll to success message after a delay to let keyboard close
                        setTimeout(() => {
                            if (heroSuccessMessage) {
                                heroSuccessMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            }
                        }, 300);
                    } else {
                        heroForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                    
                    // Track conversion
                    if (typeof gtag !== 'undefined') {
                        gtag('event', 'form_submission', {
                            'event_category': 'Contact',
                            'event_label': 'Hero Form'
                        });
                    }
                } else {
                    // Show error message
                    if (heroErrorMessage) {
                        heroErrorMessage.textContent = data.message || 'There was an error submitting the form. Please try again.';
                        heroErrorMessage.classList.add('show');
                    }
                }
            } catch (error) {
                // Clear timeout on error
                if (typeof timeoutId !== 'undefined' && timeoutId) clearTimeout(timeoutId);
                
                console.error('Form submission error:', error);
                
                // Better error messages for common issues
                let errorMsg = error.message;
                
                if (error.name === 'AbortError' || error.message.includes('timeout') || error.message.includes('aborted')) {
                    errorMsg = 'Request timed out. Please check your connection and try again.';
                } else if (error.message.includes('Failed to fetch') || error.message === 'Failed to fetch') {
                    errorMsg = 'Failed to connect to server. Please check your internet connection or call us at (720) 751-9813.';
                } else if (error.message.includes('404')) {
                    errorMsg = 'PHP handler not found. Make sure send-lead.php is in the same directory.';
                } else if (error.message.includes('500')) {
                    errorMsg = 'Server error. Check PHP configuration or contact support.';
                } else if (!errorMsg || errorMsg === 'Failed to fetch') {
                    errorMsg = 'There was an error submitting the form. Please try again.';
                }
                
                if (heroErrorMessage) {
                    heroErrorMessage.textContent = errorMsg;
                    heroErrorMessage.classList.add('show');
                }
            } finally {
                // Re-enable submit button
                submitBtn.disabled = false;
                submitBtn.style.pointerEvents = 'auto';
                submitBtn.innerHTML = originalButtonText;
            }
        };

        // Add multiple event listeners for better mobile support
        heroForm.addEventListener('submit', handleFormSubmit, { passive: false });
        
        // Also handle button events directly (for mobile compatibility)
        if (submitBtn) {
            // Prevent default form submission on button click
            submitBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Hero form button clicked');
                if (!submitBtn.disabled) {
                    handleFormSubmit(e);
                }
            }, { passive: false });
            
            // Touch events for better mobile support
            submitBtn.addEventListener('touchstart', function(e) {
                e.preventDefault();
                console.log('Hero form button touchstart');
            }, { passive: false });
            
            submitBtn.addEventListener('touchend', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Hero form button touchend');
                if (!submitBtn.disabled) {
                    handleFormSubmit(e);
                }
            }, { passive: false });
        }
    }

    // ============================================
    // Contact Form Handling (Using same code as test-form.html)
    // ============================================
    const contactForm = document.getElementById('contactForm');
    const contactSuccessMessage = document.getElementById('contactSuccessMessage');
    const contactErrorMessage = document.getElementById('contactErrorMessage');
    
    if (contactForm) {
        const submitBtn = contactForm.querySelector('button[type="submit"]');
        
        // Hide messages initially
        if (contactSuccessMessage) contactSuccessMessage.classList.remove('show');
        if (contactErrorMessage) contactErrorMessage.classList.remove('show');
        
        // Hide all error messages on page load
        const contactErrorMessages = contactForm.querySelectorAll('.error-message');
        contactErrorMessages.forEach(errorMsg => {
            errorMsg.classList.remove('show');
        });
        
        // Remove error styling on input
        const inputs = contactForm.querySelectorAll('input, textarea');
        inputs.forEach(input => {
            input.addEventListener('input', () => {
                input.classList.remove('error');
                const errorDiv = document.getElementById(input.id + 'Error');
                if (errorDiv) {
                    errorDiv.classList.remove('show');
                }
            });
        });

        // Handle contact form submission - support both click and touch events
        const handleContactFormSubmit = async (e) => {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            console.log('Contact form submitted - Device:', /Mobile|Android|iPhone|iPad/.test(navigator.userAgent) ? 'Mobile' : 'Desktop');
            console.log('Contact form submit event:', e ? e.type : 'manual');

            // Hide previous messages
            if (contactSuccessMessage) contactSuccessMessage.classList.remove('show');
            if (contactErrorMessage) contactErrorMessage.classList.remove('show');

            // Remove all error styling
            inputs.forEach(input => {
                input.classList.remove('error');
                const errorDiv = document.getElementById(input.id + 'Error');
                if (errorDiv) {
                    errorDiv.classList.remove('show');
                }
            });

            // Get form data - read directly from inputs for better mobile compatibility
            const nameInput = document.getElementById('name');
            const emailInput = document.getElementById('email');
            const phoneInput = document.getElementById('phone');
            const zipcodeInput = document.getElementById('zipcode');

            const name = (nameInput ? nameInput.value : '').trim();
            const email = (emailInput ? emailInput.value : '').trim();
            const phone = (phoneInput ? phoneInput.value : '').trim();
            const zipcode = (zipcodeInput ? zipcodeInput.value : '').trim();

            // Create FormData
            const formData = new FormData(contactForm);
            formData.append('form-name', 'contact-form');

            // Validate
            let hasErrors = false;

            // Name validation
            if (!name || name.length < 2) {
                if (nameInput) nameInput.classList.add('error');
                const errorDiv = document.getElementById('nameError');
                if (errorDiv) errorDiv.classList.add('show');
                hasErrors = true;
            }

            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!email || !emailRegex.test(email)) {
                if (emailInput) emailInput.classList.add('error');
                const errorDiv = document.getElementById('emailError');
                if (errorDiv) errorDiv.classList.add('show');
                hasErrors = true;
            }

            // Phone validation
            if (!phone || phone.replace(/\D/g, '').length < 10) {
                if (phoneInput) phoneInput.classList.add('error');
                const errorDiv = document.getElementById('phoneError');
                if (errorDiv) errorDiv.classList.add('show');
                hasErrors = true;
            }

            // Zipcode validation - more flexible for mobile
            const zipcodeClean = zipcode.replace(/\D/g, ''); // Remove all non-digits
            if (!zipcodeClean || zipcodeClean.length < 5 || zipcodeClean.length > 9) {
                if (zipcodeInput) zipcodeInput.classList.add('error');
                const errorDiv = document.getElementById('zipcodeError');
                if (errorDiv) errorDiv.classList.add('show');
                hasErrors = true;
            }

            if (hasErrors) {
                // Scroll to first error on mobile
                if (/Mobile|Android|iPhone|iPad/.test(navigator.userAgent)) {
                    const firstError = contactForm.querySelector('.error');
                    if (firstError) {
                        setTimeout(() => {
                            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            firstError.focus();
                        }, 100);
                    }
                }
                return;
            }

            // Disable submit button and show loading
            submitBtn.disabled = true;
            submitBtn.style.pointerEvents = 'none';
            const originalButtonText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="loading"></span>Submitting...';

            try {
                // Use fetch with better mobile support
                const fetchOptions = {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json'
                    }
                };

                // Add timeout for mobile networks (if supported)
                let timeoutId;
                if (typeof AbortController !== 'undefined') {
                    const controller = new AbortController();
                    timeoutId = setTimeout(() => controller.abort(), 30000);
                    fetchOptions.signal = controller.signal;
                }

                const response = await fetch('send-lead.php', fetchOptions);
                
                // Clear timeout if request succeeded
                if (timeoutId) clearTimeout(timeoutId);

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const text = await response.text();
                let data;

                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('JSON parse error:', e, 'Response:', text);
                    // If response is not JSON, check status
                    if (response.status === 404) {
                        throw new Error('Form handler not found (404). Please check if send-lead.php is uploaded correctly.');
                    } else if (response.status === 500) {
                        throw new Error('Server error (500). Please check PHP configuration or contact support.');
                    } else {
                        throw new Error('Unexpected response from server. Please try again.');
                    }
                }

                if (data.success) {
                    // Success!
                    if (contactSuccessMessage) contactSuccessMessage.classList.add('show');
                    contactForm.reset();
                    
                    // Scroll to show success message - better mobile handling
                    if (/Mobile|Android|iPhone|iPad/.test(navigator.userAgent)) {
                        // On mobile, scroll to success message after a delay to let keyboard close
                        setTimeout(() => {
                            if (contactSuccessMessage) {
                                contactSuccessMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            }
                        }, 300);
                    } else {
                        contactForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                    
                    // Track conversion
                    if (typeof gtag !== 'undefined') {
                        gtag('event', 'form_submission', {
                            'event_category': 'Contact',
                            'event_label': 'Contact Form'
                        });
                    }
                } else {
                    // Show error message
                    if (contactErrorMessage) {
                        contactErrorMessage.textContent = data.message || 'There was an error submitting the form. Please try again.';
                        contactErrorMessage.classList.add('show');
                    }
                }
            } catch (error) {
                // Clear timeout on error
                if (typeof timeoutId !== 'undefined' && timeoutId) clearTimeout(timeoutId);
                
                console.error('Form submission error:', error);
                
                // Better error messages for common issues
                let errorMsg = error.message;
                
                if (error.name === 'AbortError' || error.message.includes('timeout') || error.message.includes('aborted')) {
                    errorMsg = 'Request timed out. Please check your connection and try again.';
                } else if (error.message.includes('Failed to fetch') || error.message === 'Failed to fetch') {
                    errorMsg = 'Failed to connect to server. Please check your internet connection or call us at (720) 751-9813.';
                } else if (error.message.includes('404')) {
                    errorMsg = 'PHP handler not found. Make sure send-lead.php is in the same directory.';
                } else if (error.message.includes('500')) {
                    errorMsg = 'Server error. Check PHP configuration or contact support.';
                } else if (!errorMsg || errorMsg === 'Failed to fetch') {
                    errorMsg = 'There was an error submitting the form. Please try again.';
                }
                
                if (contactErrorMessage) {
                    contactErrorMessage.textContent = errorMsg;
                    contactErrorMessage.classList.add('show');
                }
            } finally {
                // Re-enable submit button
                submitBtn.disabled = false;
                submitBtn.style.pointerEvents = 'auto';
                submitBtn.innerHTML = originalButtonText;
            }
        };

        // Add multiple event listeners for better mobile support
        contactForm.addEventListener('submit', handleContactFormSubmit, { passive: false });
        
        // Also handle button events directly (for mobile compatibility)
        if (submitBtn) {
            // Prevent default form submission on button click
            submitBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Contact form button clicked');
                if (!submitBtn.disabled) {
                    handleContactFormSubmit(e);
                }
            }, { passive: false });
            
            // Touch events for better mobile support
            submitBtn.addEventListener('touchstart', function(e) {
                e.preventDefault();
                console.log('Contact form button touchstart');
            }, { passive: false });
            
            submitBtn.addEventListener('touchend', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Contact form button touchend');
                if (!submitBtn.disabled) {
                    handleContactFormSubmit(e);
                }
            }, { passive: false });
        }
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
    console.log('Hero form found:', !!heroForm);
    console.log('Contact form found:', !!contactForm);

})();
