<?php
// debug_session.php
session_start();
require_once 'config.php';

$session_uid = $_SESSION['user_id'] ?? 'Not logged in';

echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><link rel="stylesheet" href="../css/utilities.css"><title>Nexagram Session Debug</title></head><body>';
echo "<h3>Logged-in User ID in Session:</h3> " . htmlspecialchars($session_uid) . "<hr>";

echo "<h3>All Notifications in Database:</h3>";
$stmt = $pdo->query("SELECT id, user_id, sender_id, type, message, created_at FROM notifications ORDER BY id DESC LIMIT 10");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table class='debug-table'>";
echo "<tr><th>ID</th><th>user_id (Receiver)</th><th>sender_id</th><th>Type</th><th>Matches Session?</th></tr>";
foreach ($rows as $r) {
    $match = ($r['user_id'] == $session_uid) ? "<b class='debug-match-yes'>YES</b>" : "<b class='debug-match-no'>NO</b>";
    echo "<tr>
            <td>{$r['id']}</td>
            <td>{$r['user_id']}</td>
            <td>" . ($r['sender_id'] ?? 'N/A') . "</td>
            <td>{$r['type']}</td>
            <td>{$match}</td>
          </tr>";
}
echo "</table>";
echo "</body></html>";