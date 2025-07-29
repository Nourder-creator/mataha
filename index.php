<?php
session_start();
require 'config.php'; // الاتصال بقاعدة البيانات

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// 🔹 جلب التصنيفات
$cats_sql = "SELECT DISTINCT category FROM books";
$cats_result = $conn->query($cats_sql);
$categories = [];
if ($cats_result && $cats_result->num_rows > 0) {
    while ($row = $cats_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// 🔹 جلب المفضلات للمستخدم
$fav_stmt = $conn->prepare("SELECT book_id FROM favorites WHERE user_id = ?");
$fav_stmt->bind_param("i", $user_id);
$fav_stmt->execute();
$fav_result = $fav_stmt->get_result();
$favorites = [];
while ($row = $fav_result->fetch_assoc()) {
    $favorites[] = $row['book_id'];
}

// 🔹 الفلترة حسب التصنيف
$selected_category = isset($_GET['category']) ? $_GET['category'] : 'all';

if ($selected_category == 'all') {
    $books_sql = "SELECT * FROM books ORDER BY created_at DESC";
    $books_result = $conn->query($books_sql);
} else {
    $books_stmt = $conn->prepare("SELECT * FROM books WHERE category = ?");
    $books_stmt->bind_param("s", $selected_category);
    $books_stmt->execute();
    $books_result = $books_stmt->get_result();
}

$books = [];
if ($books_result && $books_result->num_rows > 0) {
    while ($row = $books_result->fetch_assoc()) {
        $books[] = $row;
    }
}
?>
<!DOCTYPE html>

<html lang="ar">
<head>
  <meta charset="UTF-8">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <link rel="stylesheet" href="style/styleindex.css">
</head>
<body>
 <?php require 'nav.php'; ?>
 <header class="welcome-header">
  <div class="welcome-content">
    <div class="welcome-text">
      <h1> مرحبًا بك في <span>متاهة</span></h1>
      <p>هنا، لا تختار الكتب... بل هي من تختارك.

سرٌ يتوارى بين السطور، واكتشاف يلوح في كل صفحة.

ادخل، وتيه في العُمق... حيث تبدأ الحكاية.</p>
    </div>
    <img src="img/reading.png" alt="مرحبًا" class="welcome-icon">
  </div>
</header>


  <div class="categories-grid" id="categories">
  <?php foreach ($categories as $cat): ?>
    <div class="category-box">
      <a href="booktous.php?category=<?= urlencode($cat['category']); ?>">
        <?= htmlspecialchars($cat['category']); ?>
      </a>
    </div>
  <?php endforeach; ?>
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
</script>
</body>
</html>