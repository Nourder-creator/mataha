<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT full_name, contact, photo FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    die("❌ المستخدم غير موجود.");
}
$user = $result->fetch_assoc();
$photo = $user['photo'] ?? 'default.png';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <title>ملفي الشخصي</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style/styleprofile.css">
</head>
<body>

  <style></style>
<div class="container">
  <!-- Sidebar -->
  <div class="sidebar">
    <h2>الملف الشخصي</h2>
    
    <div class="profile-photo">
      <img src="uploads/<?= htmlspecialchars($photo) ?>" alt="صورتي">
    </div>
        <button onclick="window.location.href='index.php'"> الرئيسية</button>
    <button onclick="window.location.href='moi.php'" >تعديل معلوماتي </button>
    <button onclick="window.location.href='book_read.php'"> نشاطي في الكتب</button>
    <button onclick="window.location.href='logout.php'"> تسجيل الخروج</button>
  </div>

  <!-- Content -->
<div class="profile-box">
  <h2> ملفي الشخصي</h2>

  <div class="profile-photo">
    <img src="uploads/<?= htmlspecialchars($user['photo']) ?>" alt="صورتي">
  </div>

  <div class="info">
    <label>الاسم الكامل</label>
    <span><?= htmlspecialchars($user['full_name']) ?></span>
  </div>

  <div class="info">
    <label>البريد أو الهاتف</label>
    <span><?= htmlspecialchars($user['contact']) ?></span>
  </div>

  
</div>

<script>function checkReminders() {
  fetch('get_reminders.php')
    .then(res => res.json())
    .then(reminders => {
      reminders.forEach(r => {
        const popup = document.createElement('div');
        popup.innerHTML = `📚 <strong>${r.title}</strong><br>⏰ لقد حان وقت قراءة هذا الكتاب!`;
        popup.style.cssText = `
          position: fixed;
          bottom: 20px;
          right: 20px;
          background: linear-gradient(135deg, #9c6ade, #bfa7f7);
          color: white;
          padding: 20px;
          border-radius: 15px;
          font-size: 16px;
          box-shadow: 0 0 15px rgba(0,0,0,0.3);
          z-index: 9999;
          animation: fadeIn 1s ease-in-out;
        `;
        document.body.appendChild(popup);
        setTimeout(() => popup.remove(), 10000); // يختفي بعد 10 ثواني
      });
    })
    .catch(err => console.error('خطأ في جلب التذكيرات:', err));
}

// 🔁 تحقق كل دقيقة
setInterval(checkReminders, 10); // كل 10 ثواني

checkReminders(); // استدعاء أولي عند تحميل الصفحة
</script></body>
</html>
