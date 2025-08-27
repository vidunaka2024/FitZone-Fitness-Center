<?php
// FitZone Fitness Center - Dashboard Page
session_start();
define('FITZONE_ACCESS', true);
require_once 'php/config/database.php';
require_once 'php/includes/functions.php';

// Require login to access dashboard
requireLogin();

// Get user data
$user = getUserById($_SESSION['user_id']);

// If user not found or inactive, logout
if (!$user || $user['status'] !== 'active') {
    session_destroy();
    header('Location: login.php?error=account_inactive');
    exit;
}

// Redirect admin/staff users to admin dashboard
if ($user['role'] === 'admin' || $user['role'] === 'staff') {
    header('Location: admin-dashboard.php');
    exit;
}

include 'php/includes/header.php';
?>
    
    <main class="dashboard-main">
        <div class="dashboard-container">
            <aside class="dashboard-sidebar">
                <div class="user-profile">
                    <img src="<?php echo getUserAvatar($user); ?>" alt="User Profile" class="profile-avatar">
                    <h3 class="user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                    <p class="user-membership"><?php echo ucfirst($user['membership_plan']); ?> Member</p>
                    <p class="member-since">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                </div>

                <nav class="dashboard-nav">
                    <ul>
                        <li><a href="#overview" class="nav-link active" data-tab="overview">Overview</a></li>
                        <li><a href="#workouts" class="nav-link" data-tab="workouts">My Workouts</a></li>
                        <li><a href="#classes" class="nav-link" data-tab="classes">Classes</a></li>
                        <li><a href="#appointments" class="nav-link" data-tab="appointments">Appointments</a></li>
                        <li><a href="#progress" class="nav-link" data-tab="progress">Progress</a></li>
                        <li><a href="#nutrition" class="nav-link" data-tab="nutrition">Nutrition</a></li>
                        <li><a href="#profile" class="nav-link" data-tab="profile">Profile</a></li>
                        <li><a href="#settings" class="nav-link" data-tab="settings">Settings</a></li>
                    </ul>
                </nav>

                <div class="quick-actions">
                    <h4>Quick Actions</h4>
                    <button class="btn btn-primary btn-sm">Book Class</button>
                    <button class="btn btn-secondary btn-sm">Schedule PT</button>
                </div>
            </aside>

            <div class="dashboard-content">
                <div class="tab-content active" id="overview">
                    <div class="dashboard-header">
                        <h1>Welcome Back, John!</h1>
                        <p>Ready to crush your fitness goals today?</p>
                    </div>

                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">🏃‍♂️</div>
                            <div class="stat-info">
                                <h3>12</h3>
                                <p>Workouts This Month</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">📅</div>
                            <div class="stat-info">
                                <h3>8</h3>
                                <p>Classes Attended</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">⏱️</div>
                            <div class="stat-info">
                                <h3>24h</h3>
                                <p>Total Workout Time</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">🔥</div>
                            <div class="stat-info">
                                <h3>2,450</h3>
                                <p>Calories Burned</p>
                            </div>
                        </div>
                    </div>

                    <div class="dashboard-grid">
                        <div class="widget">
                            <h3>Today's Schedule</h3>
                            <div class="schedule-list">
                                <div class="schedule-item">
                                    <div class="schedule-time">10:00 AM</div>
                                    <div class="schedule-details">
                                        <h4>Yoga Class</h4>
                                        <p>Studio A - Sarah Johnson</p>
                                    </div>
                                </div>
                                <div class="schedule-item">
                                    <div class="schedule-time">2:00 PM</div>
                                    <div class="schedule-details">
                                        <h4>Personal Training</h4>
                                        <p>Gym Floor - Mike Thompson</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="widget">
                            <h3>Progress Snapshot</h3>
                            <div class="progress-chart">
                                <canvas id="progressChart" width="300" height="200"></canvas>
                            </div>
                            <div class="progress-stats">
                                <div class="progress-item">
                                    <span class="label">Weight Loss:</span>
                                    <span class="value">-8 lbs</span>
                                </div>
                                <div class="progress-item">
                                    <span class="label">Goal Progress:</span>
                                    <span class="value">65%</span>
                                </div>
                            </div>
                        </div>

                        <div class="widget">
                            <h3>Recent Achievements</h3>
                            <div class="achievement-list">
                                <div class="achievement-item">
                                    <span class="achievement-badge">🏆</span>
                                    <div class="achievement-details">
                                        <h4>10 Classes Milestone</h4>
                                        <p>Completed your 10th group fitness class</p>
                                    </div>
                                </div>
                                <div class="achievement-item">
                                    <span class="achievement-badge">💪</span>
                                    <div class="achievement-details">
                                        <h4>Strength Warrior</h4>
                                        <p>Increased bench press by 20 lbs</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="widget">
                            <h3>Quick Stats</h3>
                            <div class="quick-stats">
                                <div class="quick-stat">
                                    <h4>Current Weight</h4>
                                    <p>175 lbs <span class="trend down">-2 lbs</span></p>
                                </div>
                                <div class="quick-stat">
                                    <h4>Body Fat %</h4>
                                    <p>18.5% <span class="trend down">-1.2%</span></p>
                                </div>
                                <div class="quick-stat">
                                    <h4>BMI</h4>
                                    <p>24.1 <span class="trend neutral">Normal</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-content" id="workouts">
                    <h2>My Workouts</h2>
                    <div class="workout-filters">
                        <button class="filter-btn active" data-filter="all">All Workouts</button>
                        <button class="filter-btn" data-filter="strength">Strength</button>
                        <button class="filter-btn" data-filter="cardio">Cardio</button>
                        <button class="filter-btn" data-filter="flexibility">Flexibility</button>
                    </div>
                    
                    <div class="workout-list">
                        <div class="workout-item" data-type="strength">
                            <div class="workout-date">Aug 25, 2024</div>
                            <div class="workout-details">
                                <h3>Upper Body Strength</h3>
                                <p>Duration: 45 minutes | Calories: 320</p>
                                <div class="workout-exercises">
                                    <span>Bench Press</span>
                                    <span>Pull-ups</span>
                                    <span>Shoulder Press</span>
                                </div>
                            </div>
                            <div class="workout-actions">
                                <button class="btn btn-sm btn-secondary">View Details</button>
                                <button class="btn btn-sm btn-primary">Repeat Workout</button>
                            </div>
                        </div>

                        <div class="workout-item" data-type="cardio">
                            <div class="workout-date">Aug 23, 2024</div>
                            <div class="workout-details">
                                <h3>HIIT Cardio Blast</h3>
                                <p>Duration: 30 minutes | Calories: 285</p>
                                <div class="workout-exercises">
                                    <span>Burpees</span>
                                    <span>Mountain Climbers</span>
                                    <span>Jump Squats</span>
                                </div>
                            </div>
                            <div class="workout-actions">
                                <button class="btn btn-sm btn-secondary">View Details</button>
                                <button class="btn btn-sm btn-primary">Repeat Workout</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-content" id="classes">
                    <h2>My Classes</h2>
                    <div class="class-tabs">
                        <button class="tab-btn active" data-tab="upcoming">Upcoming</button>
                        <button class="tab-btn" data-tab="past">Past Classes</button>
                        <button class="tab-btn" data-tab="book-new">Book New</button>
                    </div>

                    <div class="class-content active" id="upcoming">
                        <div class="class-list">
                            <div class="class-booking">
                                <div class="class-info">
                                    <h3>Yoga Flow</h3>
                                    <p>Tomorrow, 10:00 AM - 11:00 AM</p>
                                    <p>Instructor: Sarah Johnson</p>
                                </div>
                                <div class="class-actions">
                                    <button class="btn btn-sm btn-danger">Cancel</button>
                                    <button class="btn btn-sm btn-secondary">Reschedule</button>
                                </div>
                            </div>

                            <div class="class-booking">
                                <div class="class-info">
                                    <h3>Personal Training</h3>
                                    <p>Tomorrow, 2:00 PM - 3:00 PM</p>
                                    <p>Trainer: Mike Thompson</p>
                                </div>
                                <div class="class-actions">
                                    <button class="btn btn-sm btn-danger">Cancel</button>
                                    <button class="btn btn-sm btn-secondary">Reschedule</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-content" id="profile">
                    <h2>Profile Settings</h2>
                    <form class="profile-form">
                        <div class="form-section">
                            <h3>Personal Information</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>First Name</label>
                                    <input type="text" value="John">
                                </div>
                                <div class="form-group">
                                    <label>Last Name</label>
                                    <input type="text" value="Doe">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" value="john.doe@email.com">
                                </div>
                                <div class="form-group">
                                    <label>Phone</label>
                                    <input type="tel" value="(555) 123-4567">
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3>Fitness Information</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Height</label>
                                    <input type="text" value="5'10\"">
                                </div>
                                <div class="form-group">
                                    <label>Current Weight</label>
                                    <input type="text" value="175 lbs">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Fitness Goals</label>
                                <textarea rows="3">Lose weight and build muscle strength</textarea>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                            <button type="reset" class="btn btn-secondary">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <?php include 'php/includes/footer.php'; ?>
    <script src="js/main.js"></script>
    <script src="js/dashboard.js"></script>
</body>
</html>