<?php
// FitZone Fitness Center - Dashboard Page
session_start();
define('FITZONE_ACCESS', true);
require_once 'php/config/database.php';
require_once 'php/includes/functions.php';

// Require login to access dashboard
requireLogin();

// Get user data with profile information
$user = getUserById($_SESSION['user_id']);

// Get additional profile data
$db = getDB();
$profile = $db->selectOne(
    "SELECT * FROM user_profiles WHERE user_id = ?", 
    [$_SESSION['user_id']]
);

// Merge profile data with user data
if ($profile) {
    $user = array_merge($user, $profile);
}

// Get user dashboard statistics
$userStats = getUserDashboardStats($_SESSION['user_id']);
$todaySchedule = getTodaySchedule($_SESSION['user_id']);
$userAppointments = getUserUpcomingAppointments($_SESSION['user_id'], 10);


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
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
    
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
                        <h1>Welcome Back, <?php echo htmlspecialchars($user['first_name']); ?>!</h1>
                        <p>Ready to crush your fitness goals today?</p>
                    </div>

                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">🏃‍♂️</div>
                            <div class="stat-info">
                                <h3><?php echo $userStats['total_workouts_month']; ?></h3>
                                <p>Workouts This Month</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">📅</div>
                            <div class="stat-info">
                                <h3><?php echo $userStats['classes_this_month']; ?></h3>
                                <p>Classes This Month</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">⏱️</div>
                            <div class="stat-info">
                                <h3><?php echo $userStats['workout_hours_month']; ?>h</h3>
                                <p>Total Workout Time</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">🔥</div>
                            <div class="stat-info">
                                <h3><?php echo number_format($userStats['calories_burned_month']); ?></h3>
                                <p>Calories Burned</p>
                            </div>
                        </div>
                    </div>

                    <div class="dashboard-grid">
                        <div class="widget">
                            <h3>Today's Schedule</h3>
                            <div class="schedule-list">
                                <?php if (empty($todaySchedule)): ?>
                                    <div class="schedule-empty">
                                        <p>No appointments scheduled for today.</p>
                                        <p><a href="classes.php">Book a class</a> or <a href="trainers.php">schedule training</a></p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($todaySchedule as $item): ?>
                                        <div class="schedule-item">
                                            <div class="schedule-time"><?php echo date('g:i A', strtotime($item['start_time'])); ?></div>
                                            <div class="schedule-details">
                                                <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                                <p><?php echo htmlspecialchars($item['room']); ?> - <?php echo htmlspecialchars($item['instructor_name']); ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
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
                    <div class="classes-header">
                        <h2>My Classes</h2>
                        <div class="classes-actions">
                            <a href="classes.php" class="btn btn-primary btn-sm">
                                <i class="icon-plus"></i> Book New Class
                            </a>
                        </div>
                    </div>
                    
                    <div class="class-tabs">
                        <button class="tab-btn active" data-tab="upcoming">Upcoming Classes</button>
                        <button class="tab-btn" data-tab="past">Past Classes</button>
                        <button class="tab-btn" data-tab="book-new">Quick Book</button>
                    </div>

                    <div class="classes-filters">
                        <div class="filter-group">
                            <label for="class-status-filter">Filter by Status:</label>
                            <select id="class-status-filter">
                                <option value="all">All Statuses</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="wait_listed">Wait Listed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="class-type-filter">Filter by Type:</label>
                            <select id="class-type-filter">
                                <option value="all">All Types</option>
                                <option value="cardio">Cardio</option>
                                <option value="strength">Strength</option>
                                <option value="flexibility">Yoga & Flexibility</option>
                                <option value="dance">Dance</option>
                            </select>
                        </div>
                        <button type="button" id="clear-class-filters" class="btn btn-secondary btn-sm">Clear Filters</button>
                    </div>

                    <div class="class-content active" id="upcoming">
                        <div class="upcoming-classes-header">
                            <h3>My Upcoming Classes</h3>
                            <div class="class-summary">
                                <?php 
                                $classBookings = array_filter($userAppointments, function($apt) {
                                    return $apt['type'] === 'class';
                                });
                                $confirmedCount = count(array_filter($classBookings, function($apt) {
                                    return $apt['booking_status'] === 'confirmed';
                                }));
                                $waitlistedCount = count(array_filter($classBookings, function($apt) {
                                    return $apt['booking_status'] === 'wait_listed';
                                }));
                                ?>
                                <span class="summary-item">
                                    <strong><?php echo $confirmedCount; ?></strong> Confirmed
                                </span>
                                <?php if ($waitlistedCount > 0): ?>
                                <span class="summary-item waitlisted">
                                    <strong><?php echo $waitlistedCount; ?></strong> Wait-listed
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="class-list" id="upcoming-appointments">
                            <?php 
                            $hasClasses = false;
                            foreach ($userAppointments as $appointment): 
                                if ($appointment['type'] !== 'class') continue;
                                $hasClasses = true;
                            ?>
                                <div class="class-booking-card" data-appointment-type="<?php echo $appointment['type']; ?>" data-booking-id="<?php echo $appointment['booking_id']; ?>">
                                    <div class="class-card-header">
                                        <div class="class-image">
                                            <?php if (!empty($appointment['image_url']) && file_exists($appointment['image_url'])): ?>
                                                <img src="<?php echo $appointment['image_url']; ?>" alt="<?php echo htmlspecialchars($appointment['title']); ?>">
                                            <?php else: ?>
                                                <div class="class-image-placeholder">
                                                    <span class="class-icon">🏃‍♂️</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="class-main-info">
                                            <h3 class="class-title"><?php echo htmlspecialchars($appointment['title']); ?></h3>
                                            <div class="class-schedule">
                                                <span class="class-date">
                                                    <i class="icon-calendar"></i>
                                                    <?php echo $appointment['formatted_date']; ?> (<?php echo $appointment['day_of_week']; ?>)
                                                </span>
                                                <span class="class-time">
                                                    <i class="icon-clock"></i>
                                                    <?php echo $appointment['start_time_formatted']; ?> - <?php echo $appointment['end_time_formatted']; ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="class-status-badge">
                                            <span class="status-indicator status-<?php echo strtolower($appointment['booking_status']); ?>">
                                                <?php 
                                                switch($appointment['booking_status']) {
                                                    case 'confirmed': echo '✓ Confirmed'; break;
                                                    case 'wait_listed': echo '⏳ Wait-listed'; break;
                                                    case 'scheduled': echo '📅 Scheduled'; break;
                                                    default: echo ucfirst(str_replace('_', ' ', $appointment['booking_status']));
                                                }
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="class-card-details">
                                        <div class="detail-row">
                                            <span class="detail-label">Instructor:</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($appointment['instructor_name']); ?></span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="detail-label">Location:</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($appointment['room']); ?></span>
                                        </div>
                                        <?php 
                                        $timeUntilClass = strtotime($appointment['date'] . ' ' . $appointment['start_time']) - time();
                                        if ($timeUntilClass > 0): 
                                        ?>
                                        <div class="detail-row">
                                            <span class="detail-label">Starts in:</span>
                                            <span class="detail-value time-until">
                                                <?php
                                                $days = floor($timeUntilClass / 86400);
                                                $hours = floor(($timeUntilClass % 86400) / 3600);
                                                $minutes = floor(($timeUntilClass % 3600) / 60);
                                                
                                                if ($days > 0) {
                                                    echo $days . 'd ' . $hours . 'h';
                                                } elseif ($hours > 0) {
                                                    echo $hours . 'h ' . $minutes . 'm';
                                                } else {
                                                    echo $minutes . ' minutes';
                                                }
                                                ?>
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="class-card-actions">
                                        <?php if ($appointment['booking_status'] === 'confirmed' || $appointment['booking_status'] === 'scheduled'): ?>
                                            <button class="btn btn-sm btn-outline-danger cancel-appointment" 
                                                    data-booking-id="<?php echo $appointment['booking_id']; ?>" 
                                                    data-type="<?php echo $appointment['type']; ?>">
                                                <i class="icon-cancel"></i> Cancel
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary reschedule-appointment" 
                                                    data-booking-id="<?php echo $appointment['booking_id']; ?>" 
                                                    data-type="<?php echo $appointment['type']; ?>">
                                                <i class="icon-reschedule"></i> Reschedule
                                            </button>
                                        <?php elseif ($appointment['booking_status'] === 'wait_listed'): ?>
                                            <button class="btn btn-sm btn-outline-warning" disabled>
                                                <i class="icon-wait"></i> On Wait List
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger cancel-appointment" 
                                                    data-booking-id="<?php echo $appointment['booking_id']; ?>" 
                                                    data-type="<?php echo $appointment['type']; ?>">
                                                <i class="icon-cancel"></i> Leave Wait List
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php if (!$hasClasses): ?>
                                <div class="no-classes-booked">
                                    <div class="no-classes-icon">📅</div>
                                    <h3>No Classes Booked</h3>
                                    <p>You haven't booked any fitness classes yet.</p>
                                    <div class="no-classes-actions">
                                        <a href="classes.php" class="btn btn-primary">
                                            <i class="icon-plus"></i> Browse Classes
                                        </a>
                                        <button class="btn btn-secondary book-new-tab" data-tab="book-new">
                                            <i class="icon-search"></i> Quick Book
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="class-content" id="past">
                        <div class="class-list" id="past-appointments">
                            <div class="loading-placeholder">
                                <p>Loading past appointments...</p>
                            </div>
                        </div>
                    </div>

                    <div class="class-content" id="book-new">
                        <div class="book-new-section">
                            <div class="book-new-header">
                                <h3>Book New Classes</h3>
                                <p>Ready to join a new fitness class?</p>
                            </div>
                            
                            <div class="book-new-actions">
                                <a href="classes.php" class="btn btn-primary btn-large">
                                    <i class="icon-plus"></i>
                                    Browse All Classes
                                </a>
                                <div class="quick-links">
                                    <h4>Popular Classes:</h4>
                                    <a href="classes.php?type=cardio" class="quick-link">Cardio Classes</a>
                                    <a href="classes.php?type=strength" class="quick-link">Strength Training</a>
                                    <a href="classes.php?type=yoga" class="quick-link">Yoga & Flexibility</a>
                                    <a href="classes.php?type=dance" class="quick-link">Dance Fitness</a>
                                </div>
                            </div>
                            
                            <div class="class-recommendations" id="class-recommendations">
                                <h4>Recommended for You:</h4>
                                <div class="loading-placeholder">
                                    <p>Loading recommendations...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-content" id="appointments">
                    <h2>All Appointments</h2>
                    <div class="appointments-view">
                        <div class="appointment-filters">
                            <button class="filter-btn active" data-filter="all">All</button>
                            <button class="filter-btn" data-filter="classes">Classes</button>
                            <button class="filter-btn" data-filter="training">Personal Training</button>
                            <button class="filter-btn" data-filter="upcoming">Upcoming</button>
                            <button class="filter-btn" data-filter="past">Past</button>
                        </div>
                        
                        <div class="appointments-list" id="all-appointments">
                            <?php if (empty($userAppointments)): ?>
                                <div class="no-appointments">
                                    <p>You have no appointments.</p>
                                    <p><a href="classes.php" class="btn btn-primary">Book a Class</a> or <a href="trainers.php" class="btn btn-secondary">Schedule Training</a></p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($userAppointments as $appointment): ?>
                                    <div class="appointment-item" 
                                         data-type="<?php echo $appointment['type']; ?>" 
                                         data-status="<?php echo strtotime($appointment['date'] . ' ' . $appointment['start_time']) > time() ? 'upcoming' : 'past'; ?>"
                                         data-booking-id="<?php echo $appointment['booking_id']; ?>">
                                        
                                        <div class="appointment-info">
                                            <div class="appointment-header">
                                                <h3><?php echo htmlspecialchars($appointment['title']); ?></h3>
                                                <span class="appointment-type-badge <?php echo $appointment['type']; ?>">
                                                    <?php echo ucfirst($appointment['type']); ?>
                                                </span>
                                            </div>
                                            
                                            <div class="appointment-details">
                                                <div class="detail-item">
                                                    <i class="icon-calendar"></i>
                                                    <span><?php echo $appointment['formatted_date']; ?> (<?php echo $appointment['day_of_week']; ?>)</span>
                                                </div>
                                                <div class="detail-item">
                                                    <i class="icon-clock"></i>
                                                    <span><?php echo $appointment['start_time_formatted']; ?> - <?php echo $appointment['end_time_formatted']; ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <i class="icon-user"></i>
                                                    <span>Instructor: <?php echo htmlspecialchars($appointment['instructor_name']); ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <i class="icon-location"></i>
                                                    <span>Location: <?php echo htmlspecialchars($appointment['room']); ?></span>
                                                </div>
                                            </div>
                                            
                                            <div class="appointment-status">
                                                <span class="status-badge status-<?php echo strtolower($appointment['booking_status']); ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $appointment['booking_status'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="appointment-actions">
                                            <?php 
                                            $canCancel = ($appointment['booking_status'] === 'confirmed' || $appointment['booking_status'] === 'scheduled') 
                                                        && strtotime($appointment['date'] . ' ' . $appointment['start_time']) > time();
                                            $isPast = strtotime($appointment['date'] . ' ' . $appointment['start_time']) <= time();
                                            ?>
                                            
                                            <?php if ($canCancel): ?>
                                                <button class="btn btn-sm btn-outline-danger cancel-appointment" 
                                                        data-booking-id="<?php echo $appointment['booking_id']; ?>" 
                                                        data-type="<?php echo $appointment['type']; ?>">
                                                    Cancel
                                                </button>
                                                <button class="btn btn-sm btn-outline-secondary reschedule-appointment" 
                                                        data-booking-id="<?php echo $appointment['booking_id']; ?>" 
                                                        data-type="<?php echo $appointment['type']; ?>">
                                                    Reschedule
                                                </button>
                                            <?php elseif ($isPast && $appointment['booking_status'] === 'completed'): ?>
                                                <button class="btn btn-sm btn-outline-primary rate-session" 
                                                        data-booking-id="<?php echo $appointment['booking_id']; ?>" 
                                                        data-type="<?php echo $appointment['type']; ?>">
                                                    Rate Session
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
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
                                    <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Last Name</label>
                                    <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Phone</label>
                                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Date of Birth</label>
                                    <input type="date" name="date_of_birth" value="<?php echo htmlspecialchars($user['date_of_birth'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Membership Plan</label>
                                    <input type="text" value="<?php echo ucfirst($user['membership_plan']); ?> Member" disabled>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3>Fitness Information</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Height</label>
                                    <input type="text" name="height" value="<?php echo htmlspecialchars($user['height'] ?? ''); ?>" placeholder="e.g., 5'10&quot; or 178cm">
                                </div>
                                <div class="form-group">
                                    <label>Current Weight</label>
                                    <input type="number" name="current_weight" value="<?php echo htmlspecialchars($user['current_weight'] ?? ''); ?>" placeholder="lbs or kg" step="0.1">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Fitness Goals</label>
                                <textarea rows="3" name="fitness_goals" placeholder="What are your fitness goals? e.g., lose weight, build muscle, improve endurance..."><?php echo htmlspecialchars($user['fitness_goals'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3>Emergency Contact</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Emergency Contact Name</label>
                                    <input type="text" name="emergency_contact" value="<?php echo htmlspecialchars($user['emergency_contact'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Emergency Contact Phone</label>
                                    <input type="tel" name="emergency_phone" value="<?php echo htmlspecialchars($user['emergency_phone'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3>Health Information</h3>
                            <div class="form-group">
                                <label>Medical Conditions or Allergies</label>
                                <textarea rows="3" name="medical_conditions" placeholder="Please list any medical conditions, injuries, or allergies we should be aware of..."><?php echo htmlspecialchars($user['medical_conditions'] ?? ''); ?></textarea>
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

    <!-- Class Booking Modal -->
    <div id="dashboard-booking-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Book Class</h2>
                <span class="close" id="dashboard-modal-close">&times;</span>
            </div>
            <div class="modal-body">
                <div id="dashboard-class-details">
                    <!-- Class details will be populated here -->
                </div>
                <form id="dashboard-booking-form">
                    <div class="form-group">
                        <label for="dashboard-booking-type">Booking Type:</label>
                        <select id="dashboard-booking-type" name="booking_type" required>
                            <option value="regular">Regular Booking</option>
                            <option value="drop_in">Drop-in</option>
                            <option value="guest_pass">Guest Pass</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="dashboard-booking-notes">Notes (optional):</label>
                        <textarea id="dashboard-booking-notes" name="notes" placeholder="Any special requirements or notes..."></textarea>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" id="dashboard-cancel-booking">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="dashboard-confirm-booking">Confirm Booking</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'php/includes/footer.php'; ?>
    <script src="js/main.js"></script>
    <script src="js/dashboard.js"></script>
</body>
</html>