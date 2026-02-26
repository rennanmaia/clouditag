<?php
/**
 * One-time script to fix UTF-8 double-encoding in field_types labels.
 * Run once via browser or CLI, then delete this file.
 */
require_once 'includes/functions.php';

$db = getDB();

// Repair: re-interpret the bytes stored as latin1 back to proper UTF-8
// This handles the case where UTF-8 bytes were inserted via a latin1 connection.
$fixed = 0;
$skipped = 0;

$rows = $db->query("SELECT id, label FROM field_types")->fetchAll();

foreach ($rows as $row) {
    // Try to detect double-encoding: convert stored string as latin1 bytes → utf8
    $repaired = mb_convert_encoding($row['label'], 'UTF-8', 'ISO-8859-1');

    // Only update if the repaired version is valid UTF-8 and differs from original
    if ($repaired !== $row['label'] && mb_check_encoding($repaired, 'UTF-8')) {
        $stmt = $db->prepare("UPDATE field_types SET label = ? WHERE id = ?");
        $stmt->execute([$repaired, $row['id']]);
        echo "Fixed [{$row['id']}]: <b>" . htmlspecialchars($row['label'], ENT_QUOTES, 'ISO-8859-1')
           . "</b> → <b>" . htmlspecialchars($repaired) . "</b><br>";
        $fixed++;
    } else {
        $skipped++;
    }
}

echo "<hr><p>Done. Fixed: <b>$fixed</b> rows, skipped (already OK): <b>$skipped</b> rows.</p>";
echo "<p style='color:red'><strong>Delete this file after running!</strong></p>";
