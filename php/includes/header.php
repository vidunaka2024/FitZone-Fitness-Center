<?php
// FitZone Fitness Center - Header Include
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define access constant
if (!defined('FITZONE_ACCESS')) {
    define('FITZONE_ACCESS', true);
}

// Include necessary files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$user = null;

if ($isLoggedIn) {
    $user = getUserById($_SESSION['user_id']);
}

// Get current page for active navigation
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="FitZone Fitness Center - Your ultimate fitness destination with expert trainers, modern equipment, and comprehensive wellness programs.">
    <meta name="keywords" content="fitness, gym, training, health, wellness, workout, classes, personal training">
    <meta name="author" content="FitZone Fitness Center">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="images/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="images/apple-touch-icon.png">
    
    <!-- Open Graph Tags -->
    <meta property="og:title" content="FitZone Fitness Center">
    <meta property="og:description" content="Transform your body, transform your life. Join FitZone today!">
    <meta property="og:image" content="images/og-image.jpg">
    <meta property="og:url" content="<?php echo getCurrentURL(); ?>">
    <meta property="og:type" content="website">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="FitZone Fitness Center">
    <meta name="twitter:description" content="Transform your body, transform your life. Join FitZone today!">
    <meta name="twitter:image" content="images/twitter-card.jpg">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">
    <?php if ($currentPage === 'dashboard'): ?>
    <link rel="stylesheet" href="css/dashboard.css">
    <?php endif; ?>
    
    <!-- Preload critical resources -->
    <link rel="preload" href="images/logo.png" as="image">
    <link rel="preload" href="css/style.css" as="style">
    
    <title><?php echo getPageTitle($currentPage); ?></title>
</head>
<body<?php echo $currentPage === 'dashboard' ? ' class="dashboard-page"' : ''; ?>>
    <!-- Skip to main content for accessibility -->
    <a href="#main" class="skip-link">Skip to main content</a>
    
    <header id="header" class="site-header">
        <div class="header-container">
            <div class="logo">
                <a href="<?php echo $isLoggedIn ? 'dashboard.php' : 'login.php'; ?>" aria-label="FitZone Fitness Center - Home">
                    <img src="images/logo.png" alt="FitZone Logo" width="40" height="40">
                    <span>FitZone</span>
                </a>
            </div>

            <nav class="main-nav" role="navigation" aria-label="Main navigation">
                <ul>
                    <li><a href="<?php echo $isLoggedIn ? 'dashboard.php' : 'login.php'; ?>" class="<?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>"><?php echo $isLoggedIn ? 'Dashboard' : 'Home'; ?></a></li>
                    <li><a href="about.php" class="<?php echo $currentPage === 'about' ? 'active' : ''; ?>">About</a></li>
                    <li><a href="classes.php" class="<?php echo $currentPage === 'classes' ? 'active' : ''; ?>">Classes</a></li>
                    <li><a href="trainers.php" class="<?php echo $currentPage === 'trainers' ? 'active' : ''; ?>">Trainers</a></li>
                    <li><a href="membership.php" class="<?php echo $currentPage === 'membership' ? 'active' : ''; ?>">Membership</a></li>
                    <li><a href="blog.php" class="<?php echo $currentPage === 'blog' ? 'active' : ''; ?>">Blog</a></li>
                    <li><a href="contact.php" class="<?php echo $currentPage === 'contact' ? 'active' : ''; ?>">Contact</a></li>
                </ul>
            </nav>

            <div class="header-actions">
                <?php if ($isLoggedIn): ?>
                    <div class="user-menu">
                        <button class="user-menu-toggle" aria-expanded="false" aria-haspopup="true">
                            <img src="<?php echo getUserAvatar($user); ?>" alt="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>" class="user-avatar">
                            <span class="user-name"><?php echo htmlspecialchars($user['first_name']); ?></span>
                            <span class="dropdown-arrow">▼</span>
                        </button>
                        <div class="user-dropdown">
                            <div class="user-info">
                                <img src="<?php echo getUserAvatar($user); ?>" alt="Profile" class="dropdown-avatar">
                                <div class="user-details">
                                    <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                    <span class="membership-type"><?php echo ucfirst($user['membership_plan']); ?> Member</span>
                                </div>
                            </div>
                            <hr>
                            <ul class="dropdown-menu">
                                <li><a href="dashboard.php"><span class="icon">📊</span> Dashboard</a></li>
                                <li><a href="profile.php"><span class="icon">👤</span> Profile</a></li>
                                <li><a href="appointments.php"><span class="icon">📅</span> Appointments</a></li>
                                <li><a href="settings.php"><span class="icon">⚙️</span> Settings</a></li>
                                <hr>
                                <li><a href="php/auth/logout.php" class="logout-link"><span class="icon">🚪</span> Logout</a></li>
                            </ul>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn btn-secondary">Login</a>
                    <a href="register.php" class="btn btn-primary">Join Now</a>
                <?php endif; ?>
                
                <button class="search-toggle" aria-label="Search" title="Search">
                    🔍
                </button>
                
                <button class="mobile-menu-toggle" aria-label="Toggle mobile menu" aria-expanded="false" style="display: none;">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
        
        <!-- Mobile search overlay -->
        <div class="mobile-search-overlay" id="mobile-search">
            <div class="mobile-search-container">
                <form class="mobile-search-form" action="search.php" method="GET">
                    <input type="search" name="q" placeholder="Search classes, trainers, or content..." autocomplete="off" required>
                    <button type="submit" aria-label="Search">🔍</button>
                    <button type="button" class="close-search" aria-label="Close search">✕</button>
                </form>
            </div>
        </div>
        
        <!-- Progress bar for loading states -->
        <div class="loading-progress" id="loading-progress"></div>
    </header>

    <!-- Main content wrapper -->
    <main id="main" class="main-content" role="main">

<script>
// Header JavaScript functionality
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        initializeHeader();
    });

    function initializeHeader() {
        // User menu toggle
        const userMenuToggle = document.querySelector('.user-menu-toggle');
        const userDropdown = document.querySelector('.user-dropdown');
        
        if (userMenuToggle && userDropdown) {
            userMenuToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const isOpen = this.getAttribute('aria-expanded') === 'true';
                this.setAttribute('aria-expanded', !isOpen);
                userDropdown.classList.toggle('active');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.user-menu')) {
                    userMenuToggle.setAttribute('aria-expanded', 'false');
                    userDropdown.classList.remove('active');
                }
            });

            // Handle keyboard navigation
            userMenuToggle.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.click();
                }
            });
        }

        // Mobile menu functionality
        const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
        const mainNav = document.querySelector('.main-nav');
        
        if (mobileMenuToggle && mainNav) {
            mobileMenuToggle.addEventListener('click', function() {
                const isExpanded = this.getAttribute('aria-expanded') === 'true';
                this.setAttribute('aria-expanded', !isExpanded);
                mainNav.classList.toggle('active');
                
                // Update hamburger animation
                this.classList.toggle('active');
            });
        }

        // Search functionality
        const searchToggle = document.querySelector('.search-toggle');
        const mobileSearch = document.getElementById('mobile-search');
        const closeSearch = document.querySelector('.close-search');
        
        if (searchToggle && mobileSearch) {
            searchToggle.addEventListener('click', function() {
                mobileSearch.classList.add('active');
                const searchInput = mobileSearch.querySelector('input[type="search"]');
                if (searchInput) {
                    setTimeout(() => searchInput.focus(), 100);
                }
            });
        }
        
        if (closeSearch && mobileSearch) {
            closeSearch.addEventListener('click', function() {
                mobileSearch.classList.remove('active');
            });
        }
        
        // Close search on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && mobileSearch && mobileSearch.classList.contains('active')) {
                mobileSearch.classList.remove('active');
            }
        });

        // Header scroll behavior
        let lastScrollTop = 0;
        const header = document.getElementById('header');
        
        window.addEventListener('scroll', function() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            if (scrollTop > lastScrollTop && scrollTop > 100) {
                // Scrolling down - hide header
                header.style.transform = 'translateY(-100%)';
            } else {
                // Scrolling up - show header
                header.style.transform = 'translateY(0)';
            }
            
            // Add shadow when scrolled
            if (scrollTop > 10) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
            
            lastScrollTop = scrollTop;
        });

        // Active navigation highlighting
        highlightActiveNavigation();
    }

    function highlightActiveNavigation() {
        const currentPath = window.location.pathname;
        const navLinks = document.querySelectorAll('.main-nav a');
        
        navLinks.forEach(link => {
            const linkPath = new URL(link.href).pathname;
            if (linkPath === currentPath || (currentPath === '/' && linkPath.includes('index'))) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
    }

    // Loading progress bar
    window.showLoadingProgress = function(show) {
        const progressBar = document.getElementById('loading-progress');
        if (progressBar) {
            if (show) {
                progressBar.classList.add('active');
            } else {
                progressBar.classList.remove('active');
            }
        }
    };
})();
</script>

<style>
/* Header-specific styles */
.site-header {
    background: #fff;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    position: sticky;
    top: 0;
    z-index: 1000;
    transition: transform 0.3s ease;
}

.site-header.scrolled {
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.user-menu {
    position: relative;
}

.user-menu-toggle {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: none;
    border: 1px solid #e9ecef;
    padding: 0.5rem;
    border-radius: 25px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.user-menu-toggle:hover {
    border-color: #e74c3c;
    background: #f8f9fa;
}

.user-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
}

.user-name {
    font-weight: 500;
    color: #333;
}

.dropdown-arrow {
    font-size: 0.8rem;
    color: #666;
    transition: transform 0.3s ease;
}

.user-menu-toggle[aria-expanded="true"] .dropdown-arrow {
    transform: rotate(180deg);
}

.user-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    min-width: 250px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    z-index: 1001;
    margin-top: 0.5rem;
}

.user-dropdown.active {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.user-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
}

.dropdown-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
}

.user-details strong {
    display: block;
    font-size: 1rem;
    color: #333;
    margin-bottom: 0.25rem;
}

.membership-type {
    font-size: 0.8rem;
    color: #e74c3c;
    font-weight: 500;
}

.dropdown-menu {
    list-style: none;
    margin: 0;
    padding: 0.5rem 0;
}

.dropdown-menu li {
    margin: 0;
}

.dropdown-menu a {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    color: #333;
    text-decoration: none;
    transition: background 0.2s ease;
}

.dropdown-menu a:hover {
    background: #f8f9fa;
}

.dropdown-menu .logout-link:hover {
    background: #fee;
    color: #e74c3c;
}

.dropdown-menu hr {
    margin: 0.5rem 0;
    border: none;
    border-top: 1px solid #e9ecef;
}

.dropdown-menu .icon {
    font-size: 1rem;
}

/* Mobile search overlay */
.mobile-search-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.9);
    z-index: 1002;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.mobile-search-overlay.active {
    opacity: 1;
    visibility: visible;
}

.mobile-search-container {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 90%;
    max-width: 500px;
}

.mobile-search-form {
    display: flex;
    align-items: center;
    background: white;
    border-radius: 50px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.mobile-search-form input {
    flex: 1;
    border: none;
    padding: 1rem 1.5rem;
    font-size: 1.1rem;
    outline: none;
}

.mobile-search-form button {
    background: #e74c3c;
    border: none;
    color: white;
    padding: 1rem 1.5rem;
    cursor: pointer;
    font-size: 1.1rem;
    transition: background 0.3s ease;
}

.mobile-search-form button:hover {
    background: #c0392b;
}

.close-search {
    background: #333 !important;
}

.close-search:hover {
    background: #555 !important;
}

/* Loading progress bar */
.loading-progress {
    position: absolute;
    bottom: 0;
    left: 0;
    height: 3px;
    background: #e74c3c;
    width: 0%;
    transition: width 0.3s ease;
    opacity: 0;
}

.loading-progress.active {
    opacity: 1;
    animation: loading-progress 2s ease-in-out infinite;
}

@keyframes loading-progress {
    0% { width: 0%; }
    50% { width: 70%; }
    100% { width: 100%; }
}

/* Mobile hamburger menu */
.mobile-menu-toggle {
    display: flex;
    flex-direction: column;
    justify-content: space-around;
    width: 24px;
    height: 24px;
    background: transparent;
    border: none;
    cursor: pointer;
}

.mobile-menu-toggle span {
    display: block;
    height: 2px;
    width: 100%;
    background: #333;
    transition: all 0.3s ease;
}

.mobile-menu-toggle.active span:nth-child(1) {
    transform: rotate(45deg) translate(5px, 5px);
}

.mobile-menu-toggle.active span:nth-child(2) {
    opacity: 0;
}

.mobile-menu-toggle.active span:nth-child(3) {
    transform: rotate(-45deg) translate(7px, -6px);
}

/* Skip link for accessibility */
.skip-link {
    position: absolute;
    top: -40px;
    left: 6px;
    background: #e74c3c;
    color: white;
    padding: 8px;
    text-decoration: none;
    border-radius: 4px;
    z-index: 1003;
    transition: top 0.3s ease;
}

.skip-link:focus {
    top: 6px;
}

/* Responsive styles */
@media (max-width: 768px) {
    .mobile-menu-toggle {
        display: flex !important;
    }

    .main-nav {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        width: 100%;
        background: white;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }

    .main-nav.active {
        display: block;
    }

    .main-nav ul {
        flex-direction: column;
        padding: 1rem;
    }

    .main-nav li {
        border-bottom: 1px solid #eee;
    }

    .main-nav a {
        display: block;
        padding: 1rem 0;
    }

    .user-name {
        display: none;
    }

    .user-dropdown {
        right: auto;
        left: 0;
        width: 100vw;
        border-radius: 0;
        margin-top: 0;
    }
}
</style>

<?php
// Helper functions for header
function getPageTitle($currentPage) {
    $titles = [
        'index' => 'FitZone Fitness Center - Your Ultimate Fitness Destination',
        'about' => 'About Us - FitZone Fitness Center',
        'classes' => 'Fitness Classes - FitZone Fitness Center',
        'trainers' => 'Personal Trainers - FitZone Fitness Center',
        'membership' => 'Membership Plans - FitZone Fitness Center',
        'blog' => 'Fitness Blog - FitZone Fitness Center',
        'contact' => 'Contact Us - FitZone Fitness Center',
        'login' => 'Login - FitZone Fitness Center',
        'register' => 'Sign Up - FitZone Fitness Center',
        'dashboard' => 'Member Dashboard - FitZone Fitness Center'
    ];
    
    return $titles[$currentPage] ?? 'FitZone Fitness Center';
}

function getCurrentURL() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

// getUserAvatar function moved to functions.php to avoid duplication
?>