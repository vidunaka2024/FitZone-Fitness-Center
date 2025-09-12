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
    <title>Fitness Classes - FitZone Fitness Center</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">
</head>
<body>
    <?php include 'php/includes/header.php'; ?>
    
    <main>
        <section class="page-header">
            <div class="container">
                <h1>Fitness Classes</h1>
                <p>Discover our wide range of group fitness classes for all levels</p>
            </div>
        </section>

        <section class="search-filter">
            <div class="container">
                <div class="search-box">
                    <input type="text" id="class-search" placeholder="Search classes...">
                    <button type="button" onclick="searchClasses()">Search</button>
                </div>
                <div class="filter-options">
                    <select id="level-filter">
                        <option value="">All Levels</option>
                        <option value="beginner">Beginner</option>
                        <option value="intermediate">Intermediate</option>
                        <option value="advanced">Advanced</option>
                    </select>
                    <select id="type-filter">
                        <option value="">All Types</option>
                        <option value="cardio">Cardio</option>
                        <option value="strength">Strength</option>
                        <option value="flexibility">Flexibility</option>
                        <option value="dance">Dance</option>
                    </select>
                </div>
            </div>
        </section>

        <section class="classes-grid">
            <div class="container">
                <div class="loading-message" id="loading-message">
                    <p>Loading available classes...</p>
                </div>
                <div class="classes-container" id="classes-container">
                    <!-- Classes will be loaded dynamically -->
                </div>
                <div class="pagination-container" id="pagination-container">
                    <!-- Pagination will be added here if needed -->
                </div>
            </div>
        </section>

        <!-- Class Booking Modal -->
        <div id="booking-modal" class="modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Book Class</h2>
                    <span class="close">&times;</span>
                </div>
                <div class="modal-body">
                    <div id="class-details">
                        <!-- Class details will be populated here -->
                    </div>
                    <form id="booking-form">
                        <div class="form-group">
                            <label for="booking-type">Booking Type:</label>
                            <select id="booking-type" name="booking_type" required>
                                <option value="regular">Regular Booking</option>
                                <option value="drop_in">Drop-in</option>
                                <option value="guest_pass">Guest Pass</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="booking-notes">Notes (optional):</label>
                            <textarea id="booking-notes" name="notes" placeholder="Any special requirements or notes..."></textarea>
                        </div>
                        <div class="modal-actions">
                            <button type="button" class="btn btn-secondary cancel-booking">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="confirm-booking">Confirm Booking</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <?php include 'php/includes/footer.php'; ?>
    <script src="js/main.js"></script>
    <script src="js/search.js"></script>
    <script src="js/class-booking.js"></script>
</body>
</html>