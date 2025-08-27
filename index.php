<?php 
// FitZone Fitness Center - Home Page - Redirect to Login
session_start();
define('FITZONE_ACCESS', true);
require_once 'php/config/database.php';
require_once 'php/includes/functions.php';

// Redirect to login page
header('Location: login.php');
exit;
?>
    
        <section class="hero">
            <div class="hero-content">
                <h1>Transform Your Body, Transform Your Life</h1>
                <p>Join FitZone and discover the best version of yourself with our state-of-the-art facilities and expert trainers.</p>
                <div class="hero-buttons">
                    <a href="membership.php" class="btn btn-primary">Join Now</a>
                    <a href="classes.php" class="btn btn-secondary">View Classes</a>
                </div>
            </div>
            <div class="hero-image">
                <img src="images/hero-banner.jpg" alt="FitZone Fitness Center">
            </div>
        </section>

        <section class="features">
            <div class="container">
                <h2>Why Choose FitZone?</h2>
                <div class="features-grid">
                    <div class="feature">
                        <h3>Expert Trainers</h3>
                        <p>Certified professionals to guide your fitness journey</p>
                    </div>
                    <div class="feature">
                        <h3>Modern Equipment</h3>
                        <p>State-of-the-art machines and facilities</p>
                    </div>
                    <div class="feature">
                        <h3>Flexible Hours</h3>
                        <p>Open 24/7 to fit your busy schedule</p>
                    </div>
                    <div class="feature">
                        <h3>Group Classes</h3>
                        <p>Variety of fitness classes for all levels</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="quick-links">
            <div class="container">
                <h2>Quick Access</h2>
                <div class="links-grid">
                    <a href="classes.php" class="quick-link">
                        <h3>Classes</h3>
                        <p>Browse our fitness programs</p>
                    </a>
                    <a href="trainers.php" class="quick-link">
                        <h3>Trainers</h3>
                        <p>Meet our expert team</p>
                    </a>
                    <a href="membership.php" class="quick-link">
                        <h3>Membership</h3>
                        <p>Choose your plan</p>
                    </a>
                    <a href="contact.php" class="quick-link">
                        <h3>Contact</h3>
                        <p>Get in touch</p>
                    </a>
                </div>
            </div>
        </section>

<?php include 'php/includes/footer.php'; ?>