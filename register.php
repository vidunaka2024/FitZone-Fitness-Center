<?php
// FitZone Fitness Center - Register Page
session_start();
define('FITZONE_ACCESS', true);
require_once 'php/config/database.php';
require_once 'php/includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

include 'php/includes/header.php';
?>
    
    <main class="auth-main">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <img src="images/logo.png" alt="FitZone Logo" class="auth-logo">
                    <h1>Join FitZone</h1>
                    <p>Start your fitness journey today</p>
                </div>

                <form class="auth-form" id="register-form" action="php/auth/register.php" method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" required>
                            <span class="error-message" id="first_name-error"></span>
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" required>
                            <span class="error-message" id="last_name-error"></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required>
                        <span class="error-message" id="email-error"></span>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" required>
                        <span class="error-message" id="phone-error"></span>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="birth_date">Date of Birth</label>
                            <input type="date" id="birth_date" name="birth_date" required>
                            <span class="error-message" id="birth_date-error"></span>
                        </div>
                        
                        <div class="form-group">
                            <label for="gender">Gender</label>
                            <select id="gender" name="gender" required>
                                <option value="">Select gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                                <option value="prefer_not_to_say">Prefer not to say</option>
                            </select>
                            <span class="error-message" id="gender-error"></span>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Password</label>
                            <div class="password-input">
                                <input type="password" id="password" name="password" required>
                                <button type="button" class="toggle-password" onclick="togglePassword('password')">
                                    <span class="show-text">Show</span>
                                    <span class="hide-text" style="display: none;">Hide</span>
                                </button>
                            </div>
                            <span class="error-message" id="password-error"></span>
                        </div>
                        
                        <div class="form-group">
                            <label for="password_confirm">Confirm Password</label>
                            <div class="password-input">
                                <input type="password" id="password_confirm" name="password_confirm" required>
                                <button type="button" class="toggle-password" onclick="togglePassword('password_confirm')">
                                    <span class="show-text">Show</span>
                                    <span class="hide-text" style="display: none;">Hide</span>
                                </button>
                            </div>
                            <span class="error-message" id="password_confirm-error"></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="membership_plan">Membership Plan</label>
                        <select id="membership_plan" name="membership_plan" required>
                            <option value="">Choose your plan</option>
                            <option value="basic">Basic - $29/month</option>
                            <option value="premium">Premium - $49/month</option>
                            <option value="vip">VIP - $79/month</option>
                        </select>
                        <span class="error-message" id="membership_plan-error"></span>
                    </div>

                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">I agree to the <a href="terms.php" target="_blank">Terms of Service</a> and <a href="privacy.php" target="_blank">Privacy Policy</a></label>
                        <span class="error-message" id="terms-error"></span>
                    </div>

                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="newsletter" name="newsletter">
                        <label for="newsletter">Subscribe to our newsletter for fitness tips and updates</label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-full">
                        Create Account
                        <div class="btn-loader"></div>
                    </button>

                    <div class="auth-links">
                        <p>Already have an account? <a href="login.php">Sign in here</a></p>
                    </div>
                </form>

                <!-- Demo accounts info -->
                <div class="demo-info">
                    <h3>Demo Accounts (For Testing)</h3>
                    <div class="demo-accounts">
                        <div class="demo-account">
                            <strong>Admin:</strong> admin@fitzonecenter.com / admin123
                        </div>
                        <div class="demo-account">
                            <strong>Member:</strong> member@example.com / member123
                        </div>
                        <div class="demo-account">
                            <strong>Trainer:</strong> trainer@example.com / trainer123
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Include form validation JavaScript -->
    <script src="js/form-validation.js"></script>
    
    <!-- Page-specific JavaScript -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize form validation for register form
        if (typeof FormValidator !== 'undefined') {
            new FormValidator('register-form');
        }
        
        // Password strength indicator
        const passwordInput = document.getElementById('password');
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const strength = checkPasswordStrength(this.value);
                updatePasswordStrengthIndicator(strength);
            });
        }
    });

    function checkPasswordStrength(password) {
        let strength = 0;
        
        if (password.length >= 8) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;
        
        return strength;
    }

    function updatePasswordStrengthIndicator(strength) {
        // Implementation for password strength indicator
        // This would update a visual indicator showing password strength
    }

    function togglePassword(fieldId) {
        const field = document.getElementById(fieldId);
        const button = field.nextElementSibling;
        const showText = button.querySelector('.show-text');
        const hideText = button.querySelector('.hide-text');
        
        if (field.type === 'password') {
            field.type = 'text';
            showText.style.display = 'none';
            hideText.style.display = 'inline';
        } else {
            field.type = 'password';
            showText.style.display = 'inline';
            hideText.style.display = 'none';
        }
    }
    </script>

<?php include 'php/includes/footer.php'; ?>