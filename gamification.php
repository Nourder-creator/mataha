<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
  die("❌ يجب تسجيل الدخول.");
}

$user_id = $_SESSION['user_id'];

// ✅ جلب book_id من الرابط GET والتحقق من وجوده في قاعدة البيانات
$book_id = isset($_GET['book_id']) ? intval($_GET['book_id']) : 0;
$check_book = mysqli_query($conn, "SELECT id FROM books WHERE id=$book_id");
if ($book_id <= 0 || mysqli_num_rows($check_book) == 0) {
  die("❌ كتاب غير صالح.");
}

// ✅ إضافة النقاط عند الضغط على الأزرار
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  $action = $_POST['action'];
  $points_map = ['read'=>10, 'rate'=>5, 'fav'=>2];

  if (isset($points_map[$action])) {
    $table = $action === 'read' ? 'user_reads' : ($action === 'rate' ? 'ratings' : 'favorites');
    $check = mysqli_query($conn, "SELECT * FROM $table WHERE user_id=$user_id AND book_id=$book_id");

    if (mysqli_num_rows($check) === 0) {
      if ($action === 'rate') {
        mysqli_query($conn, "INSERT INTO ratings (user_id, book_id, rating) VALUES ($user_id, $book_id, 4)");
      } else {
        mysqli_query($conn, "INSERT INTO $table (user_id, book_id) VALUES ($user_id, $book_id)");
      }
      mysqli_query($conn, "UPDATE users SET points = points + {$points_map[$action]} WHERE id = $user_id");
    }
  }
  exit;
}

// ✅ جلب بيانات المستخدم
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT full_name, points FROM users WHERE id=$user_id"));
$points = (int)$user['points'];

// ✅ تحديد الشارة حسب النقاط
$badge_icon = '<i class="fas fa-seedling"></i>'; $badge_label = 'مبتدئ'; $next = 20;
if ($points >= 200) { $badge_icon = '<i class="fas fa-crown"></i>'; $badge_label = 'أسطورة'; $next = 200; }
elseif ($points >= 100) { $badge_icon = '<i class="fas fa-trophy"></i>'; $badge_label = 'ذهبي'; $next = 200; }
elseif ($points >= 50)  { $badge_icon = '<i class="fas fa-fire"></i>'; $badge_label = 'محترف'; $next = 100; }
elseif ($points >= 20)  { $badge_icon = '<i class="fas fa-bolt"></i>'; $badge_label = 'نشط'; $next = 50; }

$remaining = max(0, $next - $points);
$progress = min(100, floor(($points / $next) * 100));
$show_certificate = in_array($badge_label, ['نشط','محترف','ذهبي','أسطورة']);
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>نقاطي وشاراتي</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(to right, #f0f4f8, #e1f5fe);
      display: flex;
      flex-direction: column;
      align-items: center;
      direction: rtl;
      overflow-x: hidden;
    }

    .navbar {
      width: 97%;
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: rgb(159, 141, 207);
      padding: 15px 30px;
      position: sticky;
      top: 0;
      z-index: 1000;
      box-shadow: 0 4px 20px rgb(172, 156, 201);
    }

    .navbar .logo {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 1.6em;
      font-weight: bold;
      color: #fff;
      cursor: pointer;
    }

    .navbar .logo i {
      font-size: 24px;
      color: white;
      animation: glowLogo 3s ease-in-out infinite alternate;
    }

    @keyframes glowLogo {
      from { text-shadow: 0 0 10px white; }
      to   { text-shadow: 0 0 20px rgb(201, 153, 230); }
    }

    .nav-links {
      display: flex;
      align-items: center;
      gap: 20px;
    }

    .nav-links a {
      text-decoration: none;
      color: #eee;
      font-weight: 500;
      position: relative;
    }

    .nav-links a:hover {
      color: rgb(51, 7, 105);
    }

    .card {
      background: #fff;
      padding: 30px;
      width: 360px;
      border-radius: 20px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.08);
      text-align: center;
      margin-top: 130px;
    }

    .points {
      font-size: 40px;
      color: #1976d2;
      margin-bottom: 15px;
    }

    .points i {
      color: #ffd600;
      margin-left: 6px;
    }

    .badge {
      background: #fbe9e7;
      color: #bf360c;
      padding: 12px;
      margin-bottom: 10px;
      font-size: 16px;
      border-radius: 50px;
      display: inline-flex;
      align-items: center;
      gap: 10px;
      justify-content: center;
    }

    .badge i {
      background: #fff3e0;
      color: #ff6f00;
      padding: 10px;
      border-radius: 50%;
      font-size: 20px;
    }

    .remaining {
      font-size: 13px;
      color: #555;
      margin-bottom: 20px;
    }

    .progress {
      background: #ddd;
      height: 10px;
      border-radius: 20px;
      overflow: hidden;
      margin: 20px 0;
    }

    .progress-bar {
      height: 100%;
      background: linear-gradient(to right, #42a5f5, #1976d2);
      width: <?= $progress ?>%;
      transition: width 0.4s ease;
    }

    .actions {
      display: flex;
      gap: 10px;
      margin-top: 25px;
      flex-wrap: wrap;
    }

    .actions button {
      flex: 1;
      padding: 10px;
      border: none;
      border-radius: 12px;
      background: #1976d2;
      color: white;
      font-size: 14px;
      display: flex;
      flex-direction: column;
      align-items: center;
      cursor: pointer;
      transition: 0.3s;
    }

    .actions button:hover {
      background: #1565c0;
    }

    .print-btn {
      margin-top: 20px;
      background: #00796b;
      padding: 10px 18px;
      border: none;
      border-radius: 10px;
      color: white;
      font-size: 15px;
      cursor: pointer;
    }

    .print-btn:hover {
      background: #004d40;
    }
  </style>
</head>
<body>

<nav class="navbar">
  <div class="logo">
    <i class="fas fa-book-reader"></i>
    <span>متاهة</span>
  </div>
  <div class="nav-links">
    <a href="profile.php">صفحتي</a>
    <a href="index.php">الرئيسية</a>
    <a href="favorite.php">المفضلة</a>
    <a href="recommend.php">التوصيات</a>
    <a href="logout.php">خروج</a>
  </div>
</nav>

<div class="card">
  <div class="points">
    <i class="fas fa-star"></i> <?= $points ?>
  </div>
  <div class="badge">
    <?= $badge_icon ?> <?= $badge_label ?>
  </div>
  <div class="remaining">
    تبقى لك <strong><?= $remaining ?></strong> نقطة للوصول إلى المرتبة التالية 🎯
  </div>
  <div class="progress">
    <div class="progress-bar"></div>
  </div>
  <div class="actions">
    <button onclick="doAction('read')"><i class="fas fa-book"></i>قرأت</button>
    <button onclick="doAction('rate')"><i class="fas fa-star-half-alt"></i>قيّمت</button>
    <button onclick="doAction('fav')"><i class="fas fa-heart"></i>مفضلة</button>
  </div>
  <?php if ($show_certificate): ?>
    <button class="print-btn" onclick="window.open('certificate.php', '_blank')">
      🖨️ طباعة الشهادة
    </button>
  <?php endif; ?>
</div>

<script>
  function doAction(action) {
    fetch(location.href, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=${action}`
    }).then(() => location.reload());
  }
</script><script>function checkReminders() {
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
</script>
</body>
</html>
