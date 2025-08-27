<?php
// FitZone Fitness Center - Admin Dashboard
session_start();
define('FITZONE_ACCESS', true);
require_once 'php/config/database.php';
require_once 'php/includes/functions.php';
require_once 'php/includes/PermissionManager.php';

// Require admin login
requireLogin();
if (!userHasPermission($_SESSION['user_id'], 'admin_access')) {
    header('Location: dashboard.php?error=access_denied');
    exit;
}

// Get admin user data
$admin = getUserById($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FitZone Fitness Center</title>
    <link href="css/style.css" rel="stylesheet">
    <link href="css/dashboard.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .admin-dashboard {
            display: flex;
            min-height: 100vh;
            background-color: #f8f9fa;
        }

        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }

        .sidebar-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .admin-info {
            margin-top: 1rem;
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .sidebar-menu {
            padding: 1rem 0;
        }

        .sidebar-menu ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .sidebar-menu li {
            margin: 0;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: #fff;
        }

        .sidebar-menu i {
            width: 20px;
            margin-right: 1rem;
            font-size: 1.1rem;
        }

        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
        }

        .top-bar {
            background: white;
            padding: 1rem 2rem;
            margin: -2rem -2rem 2rem -2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: between;
            align-items: center;
        }

        .breadcrumb {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .user-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .stat-card .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .stat-card .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .content-section {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .section-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
            color: #333;
        }

        .btn-primary {
            background: #667eea;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: background 0.3s ease;
        }

        .btn-primary:hover {
            background: #5a6fd8;
        }

        .user-management-section {
            display: none;
        }

        .user-management-section.active {
            display: block;
        }

        .loading {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }

        .user-table {
            width: 100%;
            border-collapse: collapse;
        }

        .user-table th,
        .user-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .user-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .user-table .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .role-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .role-admin { background: #dc3545; color: white; }
        .role-staff { background: #fd7e14; color: white; }
        .role-trainer { background: #20c997; color: white; }
        .role-member { background: #6f42c1; color: white; }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }
        .status-suspended { background: #fff3cd; color: #856404; }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            border-radius: 3px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-edit { background: #ffc107; color: #212529; }
        .btn-delete { background: #dc3545; color: white; }
        .btn-view { background: #17a2b8; color: white; }

        .btn-sm:hover {
            transform: translateY(-1px);
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
                height: auto;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="images/logo.png" alt="FitZone Logo" style="width: 40px; height: 40px; margin-bottom: 1rem;">
                <h2>Admin Panel</h2>
                <div class="admin-info">
                    Welcome, <?php echo htmlspecialchars($admin['first_name']); ?>
                </div>
            </div>

            <nav class="sidebar-menu">
                <ul>
                    <li><a href="#dashboard" class="menu-link active" data-section="dashboard"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="#users" class="menu-link" data-section="users"><i class="fas fa-users"></i> User Management</a></li>
                    <li><a href="#classes" class="menu-link" data-section="classes"><i class="fas fa-dumbbell"></i> Classes</a></li>
                    <li><a href="#trainers" class="menu-link" data-section="trainers"><i class="fas fa-user-tie"></i> Trainers</a></li>
                    <li><a href="#memberships" class="menu-link" data-section="memberships"><i class="fas fa-id-card"></i> Memberships</a></li>
                    <li><a href="#reports" class="menu-link" data-section="reports"><i class="fas fa-chart-bar"></i> Reports</a></li>
                    <li><a href="#settings" class="menu-link" data-section="settings"><i class="fas fa-cogs"></i> Settings</a></li>
                    <li><a href="dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a></li>
                    <li><a href="php/auth/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <div class="breadcrumb" id="breadcrumb">Admin Dashboard</div>
                <div class="user-actions">
                    <span>Last login: <?php echo date('M j, Y g:i A'); ?></span>
                </div>
            </div>

            <!-- Dashboard Section -->
            <section id="dashboard-section" class="content-section active">
                <div class="section-header">
                    <h2 class="section-title">Dashboard Overview</h2>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-number" id="total-users">Loading...</div>
                        <div class="stat-label">Total Members</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                            <i class="fas fa-dumbbell"></i>
                        </div>
                        <div class="stat-number" id="total-classes">Loading...</div>
                        <div class="stat-label">Active Classes</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="stat-number" id="total-trainers">Loading...</div>
                        <div class="stat-label">Trainers</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white;">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-number" id="monthly-revenue">Loading...</div>
                        <div class="stat-label">Monthly Revenue</div>
                    </div>
                </div>

                <div class="content-section">
                    <div class="section-header">
                        <h3 class="section-title">Recent Activities</h3>
                    </div>
                    <div id="recent-activities">Loading recent activities...</div>
                </div>
            </section>

            <!-- User Management Section -->
            <section id="users-section" class="content-section user-management-section">
                <div class="section-header">
                    <h2 class="section-title">User Management</h2>
                    <button class="btn-primary" id="add-user-btn">
                        <i class="fas fa-plus"></i> Add New User
                    </button>
                </div>

                <div class="user-filters" style="margin-bottom: 1rem;">
                    <input type="text" id="user-search" placeholder="Search users..." style="padding: 0.5rem; margin-right: 1rem;">
                    <select id="role-filter" style="padding: 0.5rem; margin-right: 1rem;">
                        <option value="">All Roles</option>
                        <option value="admin">Admin</option>
                        <option value="staff">Staff</option>
                        <option value="trainer">Trainer</option>
                        <option value="member">Member</option>
                    </select>
                    <select id="status-filter" style="padding: 0.5rem;">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>

                <div id="users-table-container">
                    <div class="loading">Loading users...</div>
                </div>
            </section>

            <!-- Other sections will be loaded dynamically -->
            <section id="classes-section" class="content-section" style="display: none;">
                <h2>Classes Management</h2>
                <p>Classes management interface will be loaded here.</p>
            </section>

            <section id="trainers-section" class="content-section" style="display: none;">
                <h2>Trainers Management</h2>
                <p>Trainers management interface will be loaded here.</p>
            </section>
        </main>
    </div>

    <!-- Include JavaScript -->
    <script src="js/admin-dashboard.js"></script>
    <script>
        // Initialize admin dashboard when page loads
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof AdminDashboard !== 'undefined') {
                const dashboard = new AdminDashboard();
                dashboard.init();
            }

            // Menu navigation
            const menuLinks = document.querySelectorAll('.menu-link');
            const sections = document.querySelectorAll('.content-section');

            menuLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const targetSection = this.dataset.section;
                    
                    // Update active menu item
                    menuLinks.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');

                    // Show target section
                    sections.forEach(section => {
                        section.style.display = 'none';
                        section.classList.remove('active');
                    });

                    const target = document.getElementById(targetSection + '-section');
                    if (target) {
                        target.style.display = 'block';
                        target.classList.add('active');
                    }

                    // Update breadcrumb
                    const breadcrumb = document.getElementById('breadcrumb');
                    breadcrumb.textContent = 'Admin Dashboard > ' + this.textContent.trim();

                    // Load section content if needed
                    if (targetSection === 'users' && typeof AdminDashboard !== 'undefined') {
                        AdminDashboard.loadUsers();
                    }
                });
            });
        });
    </script>
</body>
</html>