<?php
session_start();
require 'config.php';

// 🔒 تحديد نوع المحتوى للرد بـ JSON
header('Content-Type: application/json');

// 🔻 رد افتراضي في حالة الخطأ
$response = ['status' => 'error'];

// ✅ تحقق من تسجيل الدخول ووجود book_id في الطلب
if (!isset($_SESSION['user_id']) || !isset($_POST['book_id'])) {
    echo json_encode($response);
    exit;
}

$user_id = $_SESSION['user_id'];
$book_id = $_POST['book_id'];

// 🔍 تحقق إذا كان الكتاب موجودًا مسبقًا في المفضلة
$check_stmt = $conn->prepare("SELECT 1 FROM favorites WHERE user_id = ? AND book_id = ?");
$check_stmt->bind_param("ii", $user_id, $book_id);
$check_stmt->execute();
$check_stmt->store_result(); // نستخدم store_result بدلاً من get_result للسرعة

if ($check_stmt->num_rows > 0) {
    // 🗑️ إذا كان موجودًا نحذفه من المفضلة
    $del_stmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND book_id = ?");
    $del_stmt->bind_param("ii", $user_id, $book_id);
    if ($del_stmt->execute()) {
        $response['status'] = 'removed';
    }
    $del_stmt->close();
} else {
    // ➕ إذا لم يكن موجودًا نضيفه للمفضلة
    $add_stmt = $conn->prepare("INSERT INTO favorites (user_id, book_id) VALUES (?, ?)");
    $add_stmt->bind_param("ii", $user_id, $book_id);
    if ($add_stmt->execute()) {
        $response['status'] = 'added';
    }
    $add_stmt->close();
}

$check_stmt->close();
$conn->close(); // غلق الاتصال كأفضل ممارسة

echo json_encode($response);
exit;
?>
