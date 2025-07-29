<?php
session_start();
require 'config.php'; // الاتصال بقاعدة البيانات

if (!isset($_SESSION['user_id'])) {
    die("🚫 غير مسموح.");
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 🔹 حفظ التذكير
    if (!isset($_POST['book_id']) || !isset($_POST['reminder_time'])) {
        die("❌ بيانات غير مكتملة.");
    }

    $book_id = (int) $_POST['book_id'];
    $reminder_time = $_POST['reminder_time'];

    if (strtotime($reminder_time) === false) {
        die("⚠️ وقت غير صالح.");
    }

    $stmt = $conn->prepare("INSERT INTO reminders (user_id, book_id, reminder_time) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $user_id, $book_id, $reminder_time);

    if ($stmt->execute()) {
        echo "<script>
            alert('✅ تم حفظ التذكير بنجاح!');
            window.location.href = 'index.php';
        </script>";
    } else {
        echo "❌ فشل في حفظ التذكير: " . $conn->error;
    }

} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // 🔹 جلب التذكيرات القادمة وإرجاعها كـ JSON
    header('Content-Type: application/json; charset=utf-8');

    $sql = "SELECT r.id, b.title, r.reminder_time
            FROM reminders r
            JOIN books b ON r.book_id = b.id
            WHERE r.user_id = ? AND r.reminder_time <= NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $reminders = [];
    while ($row = $result->fetch_assoc()) {
        $reminders[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'time' => $row['reminder_time']
        ];
    }

    echo json_encode($reminders, JSON_UNESCAPED_UNICODE);
    exit;
}
?>
