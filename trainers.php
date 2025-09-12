<?php
session_start();
define('FITZONE_ACCESS', true);
require_once 'php/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
    <title>Personal Trainers - FitZone Fitness Center</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">
</head>
<body>
    <?php include 'php/includes/header.php'; ?>
    
    <main>
        <section class="page-header">
            <div class="container">
                <h1>Personal Trainers</h1>
                <p>Discover our certified fitness professionals for personalized training</p>
            </div>
        </section>

        <section class="search-filter">
            <div class="container">
                <div class="search-box">
                    <input type="text" id="trainer-search" placeholder="Search trainers...">
                    <button type="button" onclick="searchTrainers()">Search</button>
                </div>
                <div class="filter-options">
                    <select id="specialization-filter">
                        <option value="">All Specializations</option>
                        <option value="yoga">Yoga & Wellness</option>
                        <option value="strength">Strength Training</option>
                        <option value="cardio">Cardio & HIIT</option>
                        <option value="dance">Dance Fitness</option>
                        <option value="rehabilitation">Rehabilitation</option>
                    </select>
                    <select id="rate-filter">
                        <option value="">All Rates</option>
                        <option value="low">$50-$75/hour</option>
                        <option value="mid">$75-$85/hour</option>
                        <option value="high">$85+/hour</option>
                    </select>
                </div>
            </div>
        </section>

        <section class="trainers-grid">
            <div class="container">
                <div class="loading-message" id="loading-message">
                    <p>Loading available trainers...</p>
                </div>
                <div class="trainers-container" id="trainers-container">
                    <!-- Trainers will be loaded dynamically -->
                </div>
                <div class="pagination-container" id="pagination-container">
                    <!-- Pagination will be added here if needed -->
                </div>
            </div>
        </section>

        <!-- Trainer Booking Modal -->
        <div id="booking-modal" class="modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Book Personal Training Session</h2>
                    <span class="close">&times;</span>
                </div>
                <div class="modal-body">
                    <div id="trainer-details">
                        <!-- Trainer details will be populated here -->
                    </div>
                    <form id="booking-form">
                        <div class="form-group">
                            <label for="appointment-date">Preferred Date:</label>
                            <input type="date" id="appointment-date" name="appointment_date" required min="">
                        </div>
                        <div class="form-group">
                            <label for="start-time">Start Time:</label>
                            <select id="start-time" name="start_time" required>
                                <option value="">Select time...</option>
                                <option value="06:00:00">6:00 AM</option>
                                <option value="07:00:00">7:00 AM</option>
                                <option value="08:00:00">8:00 AM</option>
                                <option value="09:00:00">9:00 AM</option>
                                <option value="10:00:00">10:00 AM</option>
                                <option value="11:00:00">11:00 AM</option>
                                <option value="12:00:00">12:00 PM</option>
                                <option value="13:00:00">1:00 PM</option>
                                <option value="14:00:00">2:00 PM</option>
                                <option value="15:00:00">3:00 PM</option>
                                <option value="16:00:00">4:00 PM</option>
                                <option value="17:00:00">5:00 PM</option>
                                <option value="18:00:00">6:00 PM</option>
                                <option value="19:00:00">7:00 PM</option>
                                <option value="20:00:00">8:00 PM</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="end-time">End Time:</label>
                            <select id="end-time" name="end_time" required>
                                <option value="">Select end time...</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="session-focus">Session Focus:</label>
                            <select id="session-focus" name="session_focus" required>
                                <option value="">Select focus...</option>
                                <option value="Weight Loss">Weight Loss</option>
                                <option value="Strength Training">Strength Training</option>
                                <option value="Cardio Conditioning">Cardio Conditioning</option>
                                <option value="Flexibility & Mobility">Flexibility & Mobility</option>
                                <option value="Sport-Specific Training">Sport-Specific Training</option>
                                <option value="Injury Rehabilitation">Injury Rehabilitation</option>
                                <option value="General Fitness">General Fitness</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="appointment-type">Session Type:</label>
                            <select id="appointment-type" name="appointment_type" required>
                                <option value="individual">Individual Session</option>
                                <option value="small_group">Small Group (2-4 people)</option>
                                <option value="assessment">Fitness Assessment</option>
                                <option value="consultation">Initial Consultation</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="location">Preferred Location:</label>
                            <select id="location" name="location" required>
                                <option value="gym_floor">Main Gym Floor</option>
                                <option value="private_studio">Private Studio</option>
                                <option value="outdoor_area">Outdoor Training Area</option>
                                <option value="pool_area">Pool Area</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="booking-notes">Notes (optional):</label>
                            <textarea id="booking-notes" name="notes" placeholder="Any special requirements, health considerations, or goals you'd like to share..."></textarea>
                        </div>
                        <div class="pricing-info" id="pricing-info">
                            <!-- Pricing will be calculated here -->
                        </div>
                        <div class="modal-actions">
                            <button type="button" class="btn btn-secondary cancel-booking">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="confirm-booking">Book Session</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <section class="training-programs">
            <div class="container">
                <h2>Personal Training Programs</h2>
                <div class="programs-grid">
                    <div class="program">
                        <h3>Individual Training</h3>
                        <p>One-on-one sessions tailored to your specific goals</p>
                        <ul>
                            <li>Personalized workout plans</li>
                            <li>Flexible scheduling</li>
                            <li>Progress tracking</li>
                            <li>Nutritional guidance</li>
                        </ul>
                    </div>
                    <div class="program">
                        <h3>Small Group Training</h3>
                        <p>Semi-private sessions with 2-4 participants</p>
                        <ul>
                            <li>Cost-effective option</li>
                            <li>Motivational group dynamic</li>
                            <li>Customized for group needs</li>
                            <li>Build workout partnerships</li>
                        </ul>
                    </div>
                    <div class="program">
                        <h3>Specialized Programs</h3>
                        <p>Targeted training for specific needs</p>
                        <ul>
                            <li>Injury rehabilitation</li>
                            <li>Sports performance</li>
                            <li>Senior fitness</li>
                            <li>Youth training</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'php/includes/footer.php'; ?>
    <script src="js/main.js"></script>
    <script src="js/trainer-booking.js"></script>
</body>
</html>