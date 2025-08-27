// FitZone Fitness Center - Form Validation

(function() {
    'use strict';

    // Initialize form validation when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded, initializing form validation...');
        initializeFormValidation();
    });

    function initializeFormValidation() {
        console.log('Initializing form validation...');
        // Get all forms on the page
        const forms = document.querySelectorAll('form');
        console.log('Found', forms.length, 'forms');
        
        forms.forEach(form => {
            console.log('Setting up validation for form:', form.id);
            setupFormValidation(form);
        });

        // Setup password strength indicator
        setupPasswordStrength();

        // Setup real-time validation
        setupRealTimeValidation();
    }

    function setupFormValidation(form) {
        console.log('Adding submit event listener to form:', form.id);
        form.addEventListener('submit', function(e) {
            console.log('Form submit event triggered for:', this.id);
            e.preventDefault();
            
            if (validateForm(this)) {
                console.log('Validation passed, proceeding to form submission');
                handleFormSubmission(this);
            } else {
                console.log('Validation failed, not submitting form');
            }
        });

        // Add reset functionality
        const resetBtn = form.querySelector('button[type="reset"]');
        if (resetBtn) {
            resetBtn.addEventListener('click', function(e) {
                e.preventDefault();
                resetForm(form);
            });
        }
    }

    function validateForm(form) {
        console.log('Validating form:', form.id);
        let isValid = true;
        const formData = new FormData(form);
        
        // Clear all previous errors
        clearFormErrors(form);

        // Get all required fields
        const requiredFields = form.querySelectorAll('[required]');
        console.log('Found', requiredFields.length, 'required fields');
        
        // Validate each required field
        requiredFields.forEach(field => {
            console.log('Validating field:', field.name, 'Value:', field.value);
            const fieldValid = validateField(field);
            console.log('Field validation result for', field.name, ':', fieldValid);
            if (!fieldValid) {
                isValid = false;
            }
        });

        // Additional form-specific validations
        const formId = form.id;
        
        switch(formId) {
            case 'register-form':
                isValid = validateRegistrationForm(form) && isValid;
                break;
            case 'login-form':
                isValid = validateLoginForm(form) && isValid;
                break;
            case 'contact-form':
                isValid = validateContactForm(form) && isValid;
                break;
            default:
                break;
        }

        console.log('Form validation result:', isValid);
        return isValid;
    }

    function validateField(field) {
        const value = field.value.trim();
        const type = field.type;
        const name = field.name;
        const required = field.hasAttribute('required');
        
        let isValid = true;
        let errorMessage = '';

        // Clear previous errors
        clearFieldError(field);

        // Skip validation if field is not visible or disabled
        if (field.offsetParent === null || field.disabled) {
            return true;
        }

        // Required field validation
        if (required && !value) {
            isValid = false;
            errorMessage = getRequiredMessage(field);
        }
        // Email validation
        else if (type === 'email' && value) {
            if (!isValidEmail(value)) {
                isValid = false;
                errorMessage = 'Please enter a valid email address.';
            }
        }
        // Phone validation
        else if (type === 'tel' && value) {
            if (!isValidPhone(value)) {
                isValid = false;
                errorMessage = 'Please enter a valid phone number.';
            }
        }
        // Password validation (only for registration, not login)
        else if (type === 'password' && value && name === 'password') {
            // Skip password strength validation for login forms
            const form = field.form;
            if (form && form.id !== 'login-form') {
                const passwordValidation = validatePassword(value);
                if (!passwordValidation.isValid) {
                    isValid = false;
                    errorMessage = passwordValidation.message;
                }
            }
        }
        // Confirm password validation
        else if (name === 'confirm_password' && value) {
            const passwordField = field.form.querySelector('input[name="password"]');
            if (passwordField && value !== passwordField.value) {
                isValid = false;
                errorMessage = 'Passwords do not match.';
            }
        }
        // Date validation
        else if (type === 'date' && value) {
            if (!isValidDate(value, field)) {
                isValid = false;
                errorMessage = getDateErrorMessage(field);
            }
        }
        // URL validation
        else if (type === 'url' && value) {
            if (!isValidURL(value)) {
                isValid = false;
                errorMessage = 'Please enter a valid URL.';
            }
        }
        // Custom validations based on field name
        else if (value) {
            switch(name) {
                case 'first_name':
                case 'last_name':
                    if (!isValidName(value)) {
                        isValid = false;
                        errorMessage = 'Name should only contain letters and spaces.';
                    }
                    break;
                case 'membership_plan':
                    if (!['basic', 'premium', 'elite'].includes(value)) {
                        isValid = false;
                        errorMessage = 'Please select a valid membership plan.';
                    }
                    break;
            }
        }

        // Show error if validation failed
        if (!isValid) {
            showFieldError(field, errorMessage);
        }

        return isValid;
    }

    function validateRegistrationForm(form) {
        let isValid = true;
        
        // Check terms checkbox
        const termsCheckbox = form.querySelector('input[name="terms"]');
        if (termsCheckbox && !termsCheckbox.checked) {
            showFieldError(termsCheckbox, 'You must agree to the Terms of Service.');
            isValid = false;
        }

        // Validate age (must be 16 or older)
        const birthDateField = form.querySelector('input[name="birth_date"]');
        if (birthDateField && birthDateField.value) {
            const age = calculateAge(new Date(birthDateField.value));
            if (age < 16) {
                showFieldError(birthDateField, 'You must be at least 16 years old to register.');
                isValid = false;
            }
        }

        return isValid;
    }

    function validateLoginForm(form) {
        // Additional login-specific validations can be added here
        return true;
    }

    function validateContactForm(form) {
        let isValid = true;
        
        // Validate message length
        const messageField = form.querySelector('textarea[name="message"]');
        if (messageField && messageField.value.trim().length < 10) {
            showFieldError(messageField, 'Message must be at least 10 characters long.');
            isValid = false;
        }

        // Check terms checkbox
        const termsCheckbox = form.querySelector('input[name="terms"]');
        if (termsCheckbox && !termsCheckbox.checked) {
            showFieldError(termsCheckbox, 'You must agree to the Terms of Service and Privacy Policy.');
            isValid = false;
        }

        return isValid;
    }

    // Real-time validation setup
    function setupRealTimeValidation() {
        const inputs = document.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            // Validate on blur (when field loses focus)
            input.addEventListener('blur', function() {
                if (this.value.trim() || this.hasAttribute('required')) {
                    validateField(this);
                }
            });

            // Validate on input for fields that have been marked as invalid
            input.addEventListener('input', function() {
                if (this.classList.contains('error')) {
                    validateField(this);
                }
            });

            // Special handling for password fields
            if (input.type === 'password' && input.name === 'password') {
                input.addEventListener('input', function() {
                    updatePasswordStrength(this.value);
                });
            }

            // Special handling for confirm password
            if (input.name === 'confirm_password') {
                input.addEventListener('input', function() {
                    if (this.value.trim()) {
                        validateField(this);
                    }
                });
            }
        });

        // Phone number formatting
        const phoneInputs = document.querySelectorAll('input[type="tel"]');
        phoneInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                this.value = formatPhoneNumber(this.value);
            });
        });
    }

    // Password strength indicator
    function setupPasswordStrength() {
        const passwordFields = document.querySelectorAll('input[type="password"][name="password"]');
        
        passwordFields.forEach(field => {
            const requirements = field.parentElement.parentElement.querySelector('.password-requirements');
            if (requirements) {
                field.addEventListener('input', function() {
                    updatePasswordRequirements(this.value, requirements);
                });
            }
        });
    }

    function updatePasswordRequirements(password, requirementsContainer) {
        const requirements = {
            'length-check': password.length >= 8,
            'uppercase-check': /[A-Z]/.test(password),
            'lowercase-check': /[a-z]/.test(password),
            'number-check': /\d/.test(password)
        };

        Object.keys(requirements).forEach(id => {
            const element = requirementsContainer.querySelector(`#${id}`);
            if (element) {
                if (requirements[id]) {
                    element.style.color = '#27ae60';
                    element.style.textDecoration = 'line-through';
                } else {
                    element.style.color = '#e74c3c';
                    element.style.textDecoration = 'none';
                }
            }
        });
    }

    function updatePasswordStrength(password) {
        let strength = 0;
        const checks = [
            password.length >= 8,
            /[A-Z]/.test(password),
            /[a-z]/.test(password),
            /\d/.test(password),
            /[^A-Za-z0-9]/.test(password)
        ];

        strength = checks.filter(check => check).length;

        // Update strength indicator if it exists
        const strengthIndicator = document.querySelector('.password-strength');
        if (strengthIndicator) {
            const strengthLabels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
            const strengthColors = ['#e74c3c', '#e67e22', '#f39c12', '#27ae60', '#2ecc71'];
            
            strengthIndicator.textContent = strengthLabels[strength - 1] || 'Very Weak';
            strengthIndicator.style.color = strengthColors[strength - 1] || '#e74c3c';
        }
    }

    // Validation helper functions
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    function isValidPhone(phone) {
        const phoneRegex = /^[\+]?[(]?[\d\s\-\(\)]{10,}$/;
        return phoneRegex.test(phone);
    }

    function isValidName(name) {
        const nameRegex = /^[a-zA-Z\s'-]+$/;
        return nameRegex.test(name) && name.length >= 2;
    }

    function isValidURL(url) {
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    }

    function isValidDate(dateString, field) {
        const date = new Date(dateString);
        const now = new Date();
        
        if (isNaN(date.getTime())) {
            return false;
        }

        // Check if it's a birth date field
        if (field.name === 'birth_date') {
            const minDate = new Date();
            minDate.setFullYear(minDate.getFullYear() - 120); // 120 years ago
            const maxDate = new Date();
            maxDate.setFullYear(maxDate.getFullYear() - 16); // 16 years ago
            
            return date >= minDate && date <= maxDate;
        }

        return true;
    }

    function validatePassword(password) {
        // More reasonable password requirements
        if (password.length < 6) {
            return { isValid: false, message: 'Password must be at least 6 characters long.' };
        }

        // Check for at least one letter and one number, OR strong password
        const hasLetter = /[a-zA-Z]/.test(password);
        const hasNumber = /\d/.test(password);
        const isStrong = password.length >= 8 && /[A-Z]/.test(password) && /[a-z]/.test(password) && /\d/.test(password);

        if (!hasLetter) {
            return { isValid: false, message: 'Password must contain at least one letter.' };
        }

        // For passwords shorter than 8 chars, require a number
        if (password.length < 8 && !hasNumber) {
            return { isValid: false, message: 'Password must contain at least one number.' };
        }

        return { isValid: true, message: '' };
    }

    function calculateAge(birthDate) {
        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        
        return age;
    }

    function formatPhoneNumber(phoneNumber) {
        // Remove all non-digits
        const cleaned = phoneNumber.replace(/\D/g, '');
        
        // Format as (XXX) XXX-XXXX
        if (cleaned.length >= 10) {
            return `(${cleaned.slice(0, 3)}) ${cleaned.slice(3, 6)}-${cleaned.slice(6, 10)}`;
        } else if (cleaned.length >= 6) {
            return `(${cleaned.slice(0, 3)}) ${cleaned.slice(3, 6)}-${cleaned.slice(6)}`;
        } else if (cleaned.length >= 3) {
            return `(${cleaned.slice(0, 3)}) ${cleaned.slice(3)}`;
        }
        
        return cleaned;
    }

    // Error display functions
    function showFieldError(field, message) {
        field.classList.add('error');
        
        const errorElement = field.parentElement.querySelector('.error-message');
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }

        // Add shake animation
        field.classList.add('shake');
        setTimeout(() => field.classList.remove('shake'), 600);
    }

    function clearFieldError(field) {
        field.classList.remove('error');
        
        const errorElement = field.parentElement.querySelector('.error-message');
        if (errorElement) {
            errorElement.textContent = '';
            errorElement.style.display = 'none';
        }
    }

    function clearFormErrors(form) {
        const errorFields = form.querySelectorAll('.error');
        errorFields.forEach(field => clearFieldError(field));
        
        const formResult = form.querySelector('.form-result');
        if (formResult) {
            formResult.style.display = 'none';
        }
    }

    function getRequiredMessage(field) {
        const fieldName = field.name.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
        const fieldType = field.type;
        
        switch(fieldType) {
            case 'email':
                return 'Email address is required.';
            case 'password':
                return 'Password is required.';
            case 'tel':
                return 'Phone number is required.';
            case 'checkbox':
                return 'This field must be checked.';
            default:
                return `${fieldName} is required.`;
        }
    }

    function getDateErrorMessage(field) {
        if (field.name === 'birth_date') {
            return 'Please enter a valid birth date. You must be at least 16 years old.';
        }
        return 'Please enter a valid date.';
    }

    function resetForm(form) {
        // Clear all field values
        form.reset();
        
        // Clear all errors
        clearFormErrors(form);
        
        // Reset password requirements if present
        const passwordRequirements = form.querySelector('.password-requirements');
        if (passwordRequirements) {
            const checks = passwordRequirements.querySelectorAll('li');
            checks.forEach(check => {
                check.style.color = '#6c757d';
                check.style.textDecoration = 'none';
            });
        }

        // Show success message
        if (window.FitZone && window.FitZone.showNotification) {
            window.FitZone.showNotification('Form has been reset.', 'info');
        }
    }

    function handleFormSubmission(form) {
        console.log('Form submission started for:', form.id);
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        
        // Set loading state
        if (window.FitZone && window.FitZone.setLoadingState) {
            window.FitZone.setLoadingState(submitBtn, true);
        } else {
            // Fallback loading state
            const btnText = submitBtn.querySelector('.btn-text');
            const btnLoading = submitBtn.querySelector('.btn-loading');
            if (btnText && btnLoading) {
                btnText.style.display = 'none';
                btnLoading.style.display = 'inline';
            }
            submitBtn.disabled = true;
        }

        // Determine form action
        const action = form.getAttribute('action');
        const method = form.getAttribute('method') || 'GET';
        
        console.log('Submitting to:', action, 'Method:', method);

        // Submit form via AJAX
        fetch(action, {
            method: method,
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            console.log('Response received:', response);
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.errors) {
                console.log('Server validation errors:', data.errors);
            }
            handleFormResponse(form, data);
        })
        .catch(error => {
            console.error('Form submission error:', error);
            showFormError(form, 'An error occurred while submitting the form. Please try again.');
        })
        .finally(() => {
            // Remove loading state
            if (window.FitZone && window.FitZone.setLoadingState) {
                window.FitZone.setLoadingState(submitBtn, false);
            } else {
                // Fallback loading state reset
                const btnText = submitBtn.querySelector('.btn-text');
                const btnLoading = submitBtn.querySelector('.btn-loading');
                if (btnText && btnLoading) {
                    btnText.style.display = 'inline';
                    btnLoading.style.display = 'none';
                }
                submitBtn.disabled = false;
            }
        });
    }

    function handleFormResponse(form, response) {
        console.log('Handling form response:', response);
        const formResult = form.querySelector('.form-result');
        
        if (response.success) {
            showFormSuccess(form, response.message);
            
            // Reset form on successful submission (except login form)
            if (form.id !== 'login-form') {
                setTimeout(() => resetForm(form), 2000);
            } else {
                // Redirect on successful login
                console.log('Login successful, checking redirect...');
                console.log('Response.redirect:', response.redirect);
                console.log('Response.data:', response.data);
                if (response.redirect) {
                    console.log('Redirecting to:', response.redirect);
                    window.location.href = response.redirect;
                } else if (response.data && response.data.redirect) {
                    console.log('Redirecting to data.redirect:', response.data.redirect);
                    window.location.href = response.data.redirect;
                } else {
                    console.log('No redirect URL found, redirecting to dashboard.php');
                    window.location.href = 'dashboard.php';
                }
            }
        } else {
            if (response.field_errors) {
                // Show field-specific errors
                Object.keys(response.field_errors).forEach(fieldName => {
                    const field = form.querySelector(`[name="${fieldName}"]`);
                    if (field) {
                        showFieldError(field, response.field_errors[fieldName]);
                    }
                });
            }
            
            if (response.message) {
                showFormError(form, response.message);
            }
        }
    }

    function showFormSuccess(form, message) {
        const formResult = form.querySelector('.form-result');
        const successElement = form.querySelector('#success-message');
        
        if (formResult && successElement) {
            successElement.querySelector('p').textContent = message;
            formResult.style.display = 'block';
            successElement.style.display = 'block';
            
            const errorElement = form.querySelector('#error-message');
            if (errorElement) {
                errorElement.style.display = 'none';
            }
        }

        if (window.FitZone && window.FitZone.showNotification) {
            window.FitZone.showNotification(message, 'success');
        }
    }

    function showFormError(form, message) {
        const formResult = form.querySelector('.form-result');
        const errorElement = form.querySelector('#error-message, #login-error');
        
        if (formResult && errorElement) {
            if (errorElement.tagName === 'DIV') {
                errorElement.querySelector('p').textContent = message;
            } else {
                errorElement.textContent = message;
            }
            formResult.style.display = 'block';
            errorElement.style.display = 'block';
            
            const successElement = form.querySelector('#success-message');
            if (successElement) {
                successElement.style.display = 'none';
            }
        }

        if (window.FitZone && window.FitZone.showNotification) {
            window.FitZone.showNotification(message, 'error');
        }
    }

    // Add shake animation CSS
    const style = document.createElement('style');
    style.textContent = `
        .shake {
            animation: shake 0.6s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .error {
            border-color: #e74c3c !important;
            background-color: #fff5f5 !important;
        }

        .error:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
        }
    `;
    document.head.appendChild(style);

})();