<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// معلومات الاتصال
$host = "localhost";
$username = "root";
$password = "";
$dbname = "BDD";

// إنشاء الاتصال
$conn = new mysqli($host, $username, $password, $dbname);

// تحقق من الاتصال
if ($conn->connect_error) {
    die("❌ فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// ضبط الترميز إلى utf8
$conn->set_charset("utf8");
?>
