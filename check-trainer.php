<?php
session_start();
define('FITZONE_ACCESS', true);
require_once 'php/config/database.php';
require_once 'php/includes/functions.php';

echo "<h2>Trainer Check</h2>";

try {
    $db = getDB();
    
    // Check if trainer ID 18 exists
    $trainer = $db->selectOne(
        "SELECT u.id, u.first_name, u.last_name, u.status, u.role,
                tp.hourly_rate, tp.is_accepting_clients, tp.specializations
         FROM users u
         LEFT JOIN trainer_profiles tp ON u.id = tp.user_id
         WHERE u.id = ?",
        [18]
    );
    
    if ($trainer) {
        echo "<p>✅ Trainer found:</p>";
        echo "<pre>" . json_encode($trainer, JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<p>❌ Trainer ID 18 not found</p>";
        
        // Show all trainers
        $trainers = $db->select(
            "SELECT u.id, u.first_name, u.last_name, u.role, u.status
             FROM users u
             WHERE u.role = 'trainer'"
        );
        
        echo "<p>Available trainers:</p>";
        if (empty($trainers)) {
            echo "<p>No trainers found!</p>";
        } else {
            echo "<ul>";
            foreach ($trainers as $t) {
                echo "<li>ID {$t['id']}: {$t['first_name']} {$t['last_name']} ({$t['status']})</li>";
            }
            echo "</ul>";
        }
    }
    
    // Test the exact booking query
    echo "<h3>Testing booking query...</h3>";
    $bookingTrainer = $db->selectOne(
        "SELECT u.id, u.first_name, u.last_name, u.status, 
                tp.hourly_rate, tp.is_accepting_clients, tp.specializations,
                tp.availability
         FROM users u
         INNER JOIN trainer_profiles tp ON u.id = tp.user_id
         WHERE u.id = ? AND u.role = 'trainer' AND u.status = 'active'",
        [18]
    );
    
    if ($bookingTrainer) {
        echo "<p>✅ Booking query successful:</p>";
        echo "<pre>" . json_encode($bookingTrainer, JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<p>❌ Booking query failed - trainer not found or not active</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style>