
<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$full_name = trim($_POST['full_name']);
$contact = trim($_POST['contact']);
$new_password = trim($_POST['new_password']);

$photo_name = null;
if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0 && !empty($_FILES['photo']['name'])) {
    $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
    $photo_name = uniqid() . "." . $ext;
    move_uploaded_file($_FILES['photo']['tmp_name'], "uploads/" . $photo_name);
}

if ($new_password == "") {
    if ($photo_name) {
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, contact = ?, photo = ? WHERE id = ?");
        $stmt->bind_param("sssi", $full_name, $contact, $photo_name, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, contact = ? WHERE id = ?");
        $stmt->bind_param("ssi", $full_name, $contact, $user_id);
    }
} else {
    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    if ($photo_name) {
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, contact = ?, password = ?, photo = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $full_name, $contact, $hashed, $photo_name, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, contact = ?, password = ? WHERE id = ?");
        $stmt->bind_param("sssi", $full_name, $contact, $hashed, $user_id);
    }
}

if ($stmt->execute()) {
    header("Location: profile.php");
    exit;
} else {
    echo "❌ خطأ أثناء التحديث.";
}
?>
