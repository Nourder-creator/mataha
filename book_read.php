<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];
// 🔹 جلب بيانات المستخدم
$user_stmt = $conn->prepare("SELECT full_name, contact, photo FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();
$photo = $user['photo'] ?? 'default.png';
//////////////////////////////////////
// 🔹 1. الكتب المقروءة
$read_sql = $conn->prepare("SELECT b.id, b.title, b.cover_image, COUNT(*) as read_count
                            FROM user_reads ur
                            JOIN books b ON ur.book_id = b.id
                            WHERE ur.user_id = ?
                            GROUP BY b.id");
$read_sql->bind_param("i", $user_id);
$read_sql->execute();
$reads = $read_sql->get_result()->fetch_all(MYSQLI_ASSOC);
//////////////////////////////////////
// 🔹 2. الكتب التي تم تقييمها
$rate_sql = $conn->prepare("SELECT b.id, b.title, b.cover_image, COUNT(*) as rate_count
                            FROM ratings r
                            JOIN books b ON r.book_id = b.id
                            WHERE r.user_id = ?
                            GROUP BY b.id");
$rate_sql->bind_param("i", $user_id);
$rate_sql->execute();
$ratings = $rate_sql->get_result()->fetch_all(MYSQLI_ASSOC);
//////////////////////////////////////
// 🔹 3. الكتب في التذكيرات
$rem_sql = $conn->prepare("SELECT b.id, b.title, b.cover_image, COUNT(*) as reminder_count
                           FROM reminders rm
                           JOIN books b ON rm.book_id = b.id
                           WHERE rm.user_id = ?
                           GROUP BY b.id");
$rem_sql->bind_param("i", $user_id);
$rem_sql->execute();
$reminders = $rem_sql->get_result()->fetch_all(MYSQLI_ASSOC);
//////////////////////////////////////
// 🔹 4. الكتب المفضلة
$fav_sql = $conn->prepare("SELECT b.id, b.title, b.cover_image
                           FROM favorites f
                           JOIN books b ON f.book_id = b.id
                           WHERE f.user_id = ?");
$fav_sql->bind_param("i", $user_id);
$fav_sql->execute();
$favorites = $fav_sql->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <title>أنشطتي</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style/styleindex.css">

 
</head>
<body> <?php require 'nav.php'; ?>
  <div class="buttons">
    <button onclick="showSection('reads')">✅ المقروءة</button>
    <button onclick="showSection('ratings')">⭐ التقييمات</button>
    <button onclick="showSection('reminders')">⏰ التذكيرات</button>
    <button onclick="showSection('favorites')">❤️ المفضلة</button>
  </div>
  <div class="sections">
    <!-- ✅ الكتب المقروءة -->
    <div id="reads" class="section">
      <h3>✅ الكتب المقروءة</h3>
      <div class="book-grid">
        <?php foreach ($reads as $book): ?>
         <div class="book">
            <img src="<?= htmlspecialchars($book['cover_image']) ?>" alt="غلاف">
            <h4><?= htmlspecialchars($book['title']) ?></h4>
            <div class="tag">✅ تمت قراءته <?= $book['read_count'] ?> مرة</div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <!-- ⭐ الكتب التي تم تقييمها -->
    <div id="ratings" class="section">
      <h3>⭐ الكتب التي قمت بتقييمها</h3>
      <div class="book-grid">
        <?php foreach ($ratings as $book): ?>
        <div class="book">
            <img src="<?= htmlspecialchars($book['cover_image']) ?>" alt="غلاف">
            <h4><?= htmlspecialchars($book['title']) ?></h4>
            <div class="tag">⭐ تم تقييمه <?= $book['rate_count'] ?> مرة</div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <!-- ⏰ الكتب المضافة كتذكير -->
    <div id="reminders" class="section">
      <h3>⏰ تذكيراتي</h3>
      <div class="book-grid">
        <?php foreach ($reminders as $book): ?>
          <div class="book-card">
            <img src="<?= htmlspecialchars($book['cover_image']) ?>" alt="غلاف">
            <h4><?= htmlspecialchars($book['title']) ?></h4>
            <div class="tag">⏰ تذكير <?= $book['reminder_count'] ?> مرة</div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <!-- ❤️ الكتب المفضلة -->
    <div id="favorites" class="section">
      <h3>❤️ كتبي المفضلة</h3>
      <div class="book-grid">
        <?php foreach ($favorites as $book): ?>
          <div class="book-card">
            <img src="<?= htmlspecialchars($book['cover_image']) ?>" alt="غلاف">
            <h4><?= htmlspecialchars($book['title']) ?></h4>
            <div class="tag">❤️ مفضلة</div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <script>
    function showSection(sectionId) {
      const sections = document.querySelectorAll('.section');
      sections.forEach(s => s.style.display = 'none');
      document.getElementById(sectionId).style.display = 'block';
    }
    // إظهار المقروءة افتراضيًا
    showSection('reads');
  </script>
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
