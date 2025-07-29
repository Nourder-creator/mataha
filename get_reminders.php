<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
  echo json_encode([]);
  exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
  SELECT r.id, r.book_id, b.title 
  FROM reminders r 
  JOIN books b ON r.book_id = b.id 
  WHERE r.user_id = ? AND r.reminder_time <= NOW() AND r.is_shown = 0
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$reminders = [];
while ($row = $result->fetch_assoc()) {
  $reminders[] = $row;
}

if (!empty($reminders)) {
  $ids = array_column($reminders, 'id');
  $ids_str = implode(',', $ids);
  $conn->query("UPDATE reminders SET is_shown = 1 WHERE id IN ($ids_str)");
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($reminders, JSON_UNESCAPED_UNICODE);
?>
