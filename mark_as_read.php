<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['book_id'])) {
    echo json_encode(['status' => 'error']);
    exit;
}

$user_id = $_SESSION['user_id'];
$book_id = (int)$_POST['book_id'];

// تحقق إذا سبق وسجله
$check = $conn->prepare("SELECT id FROM user_reads WHERE user_id = ? AND book_id = ?");
$check->bind_param("ii", $user_id, $book_id);
$check->execute();
$check_result = $check->get_result();

if ($check_result->num_rows === 0) {
    // إدراج القراءة
    $stmt = $conn->prepare("INSERT INTO user_reads (user_id, book_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $book_id);
    $stmt->execute();
}

echo json_encode(['status' => 'recorded']);