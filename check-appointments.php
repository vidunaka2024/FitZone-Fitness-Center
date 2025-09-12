<?php
session_start();
define('FITZONE_ACCESS', true);
require_once 'php/config/database.php';
require_once 'php/includes/functions.php';

echo "<h2>Existing Appointments Check</h2>";

try {
    $db = getDB();
    
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    
    // Check existing appointments for Emma Wilson (trainer ID 18)
    $appointments = $db->select(
        "SELECT * FROM pt_appointments 
         WHERE trainer_id = 18 
         AND appointment_date = ?
         AND status NOT IN ('cancelled', 'completed')
         ORDER BY start_time",
        [$tomorrow]
    );
    
    echo "<h3>Emma Wilson's appointments for {$tomorrow}:</h3>";
    
    if (empty($appointments)) {
        echo "<p>✅ No existing appointments found</p>";
    } else {
        echo "<p>❌ Found " . count($appointments) . " existing appointments:</p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Client ID</th><th>Date</th><th>Start</th><th>End</th><th>Status</th><th>Session Goals</th><th>Action</th></tr>";
        
        foreach ($appointments as $apt) {
            echo "<tr>";
            echo "<td>{$apt['id']}</td>";
            echo "<td>{$apt['client_id']}</td>";
            echo "<td>{$apt['appointment_date']}</td>";
            echo "<td>{$apt['start_time']}</td>";
            echo "<td>{$apt['end_time']}</td>";
            echo "<td>{$apt['status']}</td>";
            echo "<td>{$apt['session_goals']}</td>";
            echo "<td><a href='?delete={$apt['id']}' onclick='return confirm(\"Delete this appointment?\")' style='color: red;'>Delete</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Handle deletion
    if (isset($_GET['delete'])) {
        $deleteId = (int)$_GET['delete'];
        $result = $db->delete('pt_appointments', ['id' => $deleteId]);
        if ($result) {
            echo "<p style='color: green;'>✅ Appointment {$deleteId} deleted successfully!</p>";
            echo "<script>setTimeout(() => window.location.href = 'check-appointments.php', 1000);</script>";
        } else {
            echo "<p style='color: red;'>❌ Failed to delete appointment {$deleteId}</p>";
        }
    }
    
    // Show all appointments for context
    echo "<h3>All PT Appointments:</h3>";
    $allAppointments = $db->select(
        "SELECT pta.*, u.first_name, u.last_name 
         FROM pt_appointments pta
         LEFT JOIN users u ON pta.trainer_id = u.id
         ORDER BY pta.appointment_date DESC, pta.start_time DESC
         LIMIT 10"
    );
    
    if (empty($allAppointments)) {
        echo "<p>No appointments found in database</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-top: 10px;'>";
        echo "<tr><th>ID</th><th>Trainer</th><th>Client ID</th><th>Date</th><th>Time</th><th>Status</th><th>Created</th></tr>";
        
        foreach ($allAppointments as $apt) {
            echo "<tr>";
            echo "<td>{$apt['id']}</td>";
            echo "<td>{$apt['first_name']} {$apt['last_name']} (ID: {$apt['trainer_id']})</td>";
            echo "<td>{$apt['client_id']}</td>";
            echo "<td>{$apt['appointment_date']}</td>";
            echo "<td>{$apt['start_time']} - {$apt['end_time']}</td>";
            echo "<td>{$apt['status']}</td>";
            echo "<td>{$apt['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f2f2f2; }
h3 { color: #2c3e50; margin-top: 30px; }
</style>