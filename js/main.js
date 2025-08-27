// FitZone Fitness Center - Main JavaScript File

(function() {
    'use strict';

    // Global variables
    let isLoading = false;

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        initializeApp();
    });

    function initializeApp() {
        initializeNavigation();
        initializeScrollEffects();
        initializeLazyLoading();
        initializeFormEnhancements();
        initializeAnimations();
        initializeAccessibility();
    }

    // Navigation functionality
    function initializeNavigation() {
        const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
        const mainNav = document.querySelector('.main-nav');
        const searchToggle = document.querySelector('.search-toggle');

        // Mobile menu toggle
        if (mobileMenuToggle && mainNav) {
            mobileMenuToggle.addEventListener('click', function() {
                mainNav.classList.toggle('active');
                mobileMenuToggle.setAttribute('aria-expanded', 
                    mainNav.classList.contains('active') ? 'true' : 'false'
                );
            });

            // Close mobile menu when clicking outside
            document.addEventListener('click', function(e) {
                if (!mainNav.contains(e.target) && !mobileMenuToggle.contains(e.target)) {
                    mainNav.classList.remove('active');
                    mobileMenuToggle.setAttribute('aria-expanded', 'false');
                }
            });

            // Close mobile menu when window is resized to desktop
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    mainNav.classList.remove('active');
                    mobileMenuToggle.setAttribute('aria-expanded', 'false');
                }
            });
        }

        // Search toggle functionality
        if (searchToggle) {
            searchToggle.addEventListener('click', function() {
                showSearchModal();
            });
        }

        // Smooth scrolling for anchor links
        const anchorLinks = document.querySelectorAll('a[href^="#"]');
        anchorLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href === '#') return;

                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }

    // Scroll effects
    function initializeScrollEffects() {
        let lastScrollTop = 0;
        const header = document.querySelector('header');

        window.addEventListener('scroll', throttle(function() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            // Header show/hide on scroll
            if (header) {
                if (scrollTop > lastScrollTop && scrollTop > 100) {
                    header.style.transform = 'translateY(-100%)';
                } else {
                    header.style.transform = 'translateY(0)';
                }
                
                // Add shadow when scrolled
                if (scrollTop > 10) {
                    header.classList.add('scrolled');
                } else {
                    header.classList.remove('scrolled');
                }
            }

            lastScrollTop = scrollTop;

            // Reveal animations
            animateOnScroll();
        }, 10));

        // Back to top button
        const backToTopBtn = document.createElement('button');
        backToTopBtn.innerHTML = '↑';
        backToTopBtn.className = 'back-to-top';
        backToTopBtn.setAttribute('aria-label', 'Back to top');
        backToTopBtn.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
            font-size: 1.2rem;
        `;

        document.body.appendChild(backToTopBtn);

        backToTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Show/hide back to top button
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTopBtn.style.opacity = '1';
                backToTopBtn.style.visibility = 'visible';
            } else {
                backToTopBtn.style.opacity = '0';
                backToTopBtn.style.visibility = 'hidden';
            }
        });
    }

    // Lazy loading for images
    function initializeLazyLoading() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        img.classList.add('loaded');
                        observer.unobserve(img);
                    }
                });
            });

            const lazyImages = document.querySelectorAll('img[data-src]');
            lazyImages.forEach(img => imageObserver.observe(img));
        }
    }

    // Form enhancements
    function initializeFormEnhancements() {
        const forms = document.querySelectorAll('form');
        
        forms.forEach(form => {
            // Add loading states
            form.addEventListener('submit', function(e) {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn && !isLoading) {
                    setLoadingState(submitBtn, true);
                }
            });

            // Real-time validation feedback
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    validateField(this);
                });

                input.addEventListener('input', function() {
                    if (this.classList.contains('error')) {
                        validateField(this);
                    }
                });
            });
        });

        // Password toggle functionality
        window.togglePassword = function(fieldId) {
            const field = document.getElementById(fieldId);
            const toggleBtn = field.nextElementSibling.querySelector('.toggle-password');
            const showText = toggleBtn.querySelector('.show-text');
            const hideText = toggleBtn.querySelector('.hide-text');

            if (field.type === 'password') {
                field.type = 'text';
                showText.style.display = 'none';
                hideText.style.display = 'inline';
            } else {
                field.type = 'password';
                showText.style.display = 'inline';
                hideText.style.display = 'none';
            }
        };
    }

    // Animations
    function initializeAnimations() {
        // Animate elements on scroll
        animateOnScroll();

        // Hero animation
        const heroContent = document.querySelector('.hero-content');
        if (heroContent) {
            heroContent.style.opacity = '0';
            heroContent.style.transform = 'translateY(30px)';
            
            setTimeout(() => {
                heroContent.style.transition = 'all 0.8s ease-out';
                heroContent.style.opacity = '1';
                heroContent.style.transform = 'translateY(0)';
            }, 200);
        }

        // Stagger animations for grid items
        const gridItems = document.querySelectorAll('.features-grid .feature, .links-grid .quick-link');
        gridItems.forEach((item, index) => {
            item.style.opacity = '0';
            item.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                item.style.transition = 'all 0.6s ease-out';
                item.style.opacity = '1';
                item.style.transform = 'translateY(0)';
            }, 100 * (index + 1));
        });
    }

    // Accessibility enhancements
    function initializeAccessibility() {
        // Skip to main content link
        const skipLink = document.createElement('a');
        skipLink.href = '#main';
        skipLink.textContent = 'Skip to main content';
        skipLink.className = 'skip-link';
        skipLink.style.cssText = `
            position: absolute;
            top: -40px;
            left: 6px;
            background: #e74c3c;
            color: white;
            padding: 8px;
            text-decoration: none;
            border-radius: 4px;
            z-index: 1001;
            transition: top 0.3s ease;
        `;
        
        skipLink.addEventListener('focus', function() {
            this.style.top = '6px';
        });
        
        skipLink.addEventListener('blur', function() {
            this.style.top = '-40px';
        });
        
        document.body.prepend(skipLink);

        // Keyboard navigation for custom elements
        const customButtons = document.querySelectorAll('.quick-link, .class-card, .trainer-card');
        customButtons.forEach(btn => {
            btn.setAttribute('tabindex', '0');
            btn.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.click();
                }
            });
        });

        // Focus management for modals
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModals();
            }
        });
    }

    // Utility functions
    function throttle(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    function animateOnScroll() {
        const elements = document.querySelectorAll('.animate-on-scroll');
        elements.forEach(el => {
            const elementTop = el.getBoundingClientRect().top;
            const elementVisible = 150;

            if (elementTop < window.innerHeight - elementVisible) {
                el.classList.add('animated');
            }
        });
    }

    function validateField(field) {
        const value = field.value.trim();
        const type = field.type;
        const required = field.hasAttribute('required');
        let isValid = true;
        let errorMessage = '';

        // Clear previous errors
        field.classList.remove('error');
        const errorElement = field.parentElement.querySelector('.error-message');
        if (errorElement) {
            errorElement.textContent = '';
        }

        // Required field validation
        if (required && !value) {
            isValid = false;
            errorMessage = 'This field is required.';
        }
        // Email validation
        else if (type === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                errorMessage = 'Please enter a valid email address.';
            }
        }
        // Phone validation
        else if (type === 'tel' && value) {
            const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
            if (!phoneRegex.test(value.replace(/\D/g, ''))) {
                isValid = false;
                errorMessage = 'Please enter a valid phone number.';
            }
        }
        // Password validation
        else if (type === 'password' && value && field.name === 'password') {
            if (value.length < 8) {
                isValid = false;
                errorMessage = 'Password must be at least 8 characters long.';
            } else if (!/(?=.*[a-z])/.test(value)) {
                isValid = false;
                errorMessage = 'Password must contain at least one lowercase letter.';
            } else if (!/(?=.*[A-Z])/.test(value)) {
                isValid = false;
                errorMessage = 'Password must contain at least one uppercase letter.';
            } else if (!/(?=.*\d)/.test(value)) {
                isValid = false;
                errorMessage = 'Password must contain at least one number.';
            }
        }
        // Confirm password validation
        else if (field.name === 'confirm_password' && value) {
            const passwordField = document.querySelector('input[name="password"]');
            if (passwordField && value !== passwordField.value) {
                isValid = false;
                errorMessage = 'Passwords do not match.';
            }
        }

        // Apply validation result
        if (!isValid) {
            field.classList.add('error');
            if (errorElement) {
                errorElement.textContent = errorMessage;
            }
        }

        return isValid;
    }

    function setLoadingState(button, loading) {
        const btnText = button.querySelector('.btn-text');
        const btnLoading = button.querySelector('.btn-loading');
        
        if (loading) {
            isLoading = true;
            button.disabled = true;
            if (btnText) btnText.style.display = 'none';
            if (btnLoading) btnLoading.style.display = 'inline';
        } else {
            isLoading = false;
            button.disabled = false;
            if (btnText) btnText.style.display = 'inline';
            if (btnLoading) btnLoading.style.display = 'none';
        }
    }

    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 5px;
            color: white;
            z-index: 1002;
            max-width: 300px;
            transform: translateX(100%);
            transition: transform 0.3s ease;
        `;

        // Set background color based on type
        const colors = {
            success: '#27ae60',
            error: '#e74c3c',
            warning: '#f39c12',
            info: '#3498db'
        };
        notification.style.background = colors[type] || colors.info;

        document.body.appendChild(notification);

        // Animate in
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);

        // Auto remove after 5 seconds
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 5000);
    }

    function showSearchModal() {
        // Create search modal if it doesn't exist
        let searchModal = document.getElementById('search-modal');
        if (!searchModal) {
            searchModal = document.createElement('div');
            searchModal.id = 'search-modal';
            searchModal.className = 'search-modal';
            searchModal.innerHTML = `
                <div class="search-modal-content">
                    <div class="search-modal-header">
                        <h2>Search FitZone</h2>
                        <button class="search-modal-close">&times;</button>
                    </div>
                    <div class="search-modal-body">
                        <form class="search-form">
                            <input type="search" placeholder="Search classes, trainers, or content..." autofocus>
                            <button type="submit">Search</button>
                        </form>
                        <div class="search-suggestions">
                            <h3>Popular Searches</h3>
                            <div class="suggestion-tags">
                                <span class="suggestion-tag">Yoga Classes</span>
                                <span class="suggestion-tag">Personal Training</span>
                                <span class="suggestion-tag">Swimming Pool</span>
                                <span class="suggestion-tag">Membership Plans</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            document.body.appendChild(searchModal);

            // Add styles
            const style = document.createElement('style');
            style.textContent = `
                .search-modal {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0,0,0,0.8);
                    z-index: 1003;
                    display: flex;
                    align-items: flex-start;
                    justify-content: center;
                    padding-top: 5%;
                    opacity: 0;
                    visibility: hidden;
                    transition: all 0.3s ease;
                }
                .search-modal.active {
                    opacity: 1;
                    visibility: visible;
                }
                .search-modal-content {
                    background: white;
                    border-radius: 10px;
                    width: 90%;
                    max-width: 600px;
                    transform: translateY(-20px);
                    transition: transform 0.3s ease;
                }
                .search-modal.active .search-modal-content {
                    transform: translateY(0);
                }
                .search-modal-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 1.5rem;
                    border-bottom: 1px solid #eee;
                }
                .search-modal-close {
                    background: none;
                    border: none;
                    font-size: 1.5rem;
                    cursor: pointer;
                    color: #999;
                }
                .search-modal-body {
                    padding: 1.5rem;
                }
                .search-form {
                    display: flex;
                    gap: 1rem;
                    margin-bottom: 2rem;
                }
                .search-form input {
                    flex: 1;
                    padding: 12px;
                    border: 1px solid #ddd;
                    border-radius: 5px;
                    font-size: 1rem;
                }
                .search-suggestions h3 {
                    margin-bottom: 1rem;
                    color: #333;
                }
                .suggestion-tags {
                    display: flex;
                    gap: 0.5rem;
                    flex-wrap: wrap;
                }
                .suggestion-tag {
                    background: #f8f9fa;
                    padding: 0.5rem 1rem;
                    border-radius: 20px;
                    cursor: pointer;
                    transition: background 0.3s ease;
                }
                .suggestion-tag:hover {
                    background: #e74c3c;
                    color: white;
                }
            `;
            document.head.appendChild(style);

            // Add event listeners
            const closeBtn = searchModal.querySelector('.search-modal-close');
            closeBtn.addEventListener('click', () => {
                searchModal.classList.remove('active');
            });

            searchModal.addEventListener('click', (e) => {
                if (e.target === searchModal) {
                    searchModal.classList.remove('active');
                }
            });

            const suggestionTags = searchModal.querySelectorAll('.suggestion-tag');
            suggestionTags.forEach(tag => {
                tag.addEventListener('click', () => {
                    const searchInput = searchModal.querySelector('input[type="search"]');
                    searchInput.value = tag.textContent;
                    searchInput.focus();
                });
            });
        }

        searchModal.classList.add('active');
        const searchInput = searchModal.querySelector('input[type="search"]');
        setTimeout(() => searchInput.focus(), 100);
    }

    function closeModals() {
        const modals = document.querySelectorAll('.search-modal, .modal');
        modals.forEach(modal => {
            modal.classList.remove('active');
        });
    }

    // Export useful functions to global scope
    window.FitZone = {
        showNotification,
        setLoadingState,
        validateField,
        showSearchModal,
        closeModals
    };

})();