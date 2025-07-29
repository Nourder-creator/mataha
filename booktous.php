<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
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
<?php 
$reminders = [];
$reminder_stmt = $conn->prepare("
    SELECT r.id, r.book_id, b.title 
    FROM reminders r 
    JOIN books b ON r.book_id = b.id 
    WHERE r.user_id = ? AND r.reminder_time <= NOW() AND r.is_shown = 0
");
$reminder_stmt->bind_param("i", $user_id);
$reminder_stmt->execute();
$reminder_result = $reminder_stmt->get_result();

while ($row = $reminder_result->fetch_assoc()) {
    $reminders[] = $row;
}
if (!empty($reminders)) {
    $ids = array_column($reminders, 'id');
    $ids_str = implode(',', $ids);
    $conn->query("UPDATE reminders SET is_shown = 1 WHERE id IN ($ids_str)");
} 
?>
<html lang="ar">
<head>
  <meta charset="UTF-8">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
     <link rel="stylesheet" href="style/styleindex.css">
</head>
<body>
  <?php require 'nav.php'; ?>

   <div class="categories-grid" id="categories">
  <?php foreach ($categories as $cat): ?>
    <div class="category-box">
      <a href="booktous.php?category=<?= urlencode($cat['category']); ?>">
        <?= htmlspecialchars($cat['category']); ?>
      </a>
    </div>
  <?php endforeach; ?>
</div><script>
const menuToggle = document.querySelector('.menu-toggle');
const navLinks = document.querySelector('.nav-links');

menuToggle.addEventListener('click', () => {
  navLinks.classList.toggle('active');
});
</script>

    <div class="book-grid">
    
    <?php foreach ($books as $book): ?>
      <div class="book">
        <!-- قلب المفضلة -->
        <form method="post" action="toggle_favorite.php" class="favorite-form">
          <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
          <button type="submit">
            <i class="fa<?= in_array($book['id'], $favorites) ? 's' : 'r' ?> fa-heart" style="color: <?= in_array($book['id'], $favorites) ? 'red' : '#bbb' ?>;"></i>
          </button>
        </form>
        <div class="reminder-button" onclick="openReminderModal(<?= $book['id'] ?>, '<?= htmlspecialchars($book['title'], ENT_QUOTES) ?>')">
  <i class="fas fa-clock"></i>
</div>
    <?php if (!empty($book['cover_image'])): ?>
      <img src="<?= htmlspecialchars($book['cover_image']) ?>" alt="غلاف الكتاب">
    <?php else: ?>
      <img src="default_cover.jpg" alt="غلاف افتراضي">
    <?php endif; ?>

    <h4><?= htmlspecialchars($book['title']) ?></h4>
    <p><?= nl2br(substr($book['description'], 0, 100)) ?>...</p>
    <div class="actions">
 <a href="<?= htmlspecialchars($book['file_path']) ?>" 
   class="read-button" 
   data-book-id="<?= $book['id'] ?>" 
   target="_blank">📖 قراءة</a>
      <a href="rate.php?book_id=<?= $book['id'] ?>">⭐️ تقييم</a>
    </div>
  </div>
<?php endforeach; ?>

  </div>
  <script>
document.querySelectorAll('.favorite-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault(); // ما يخليش يعاود تحميل الصفحة

        const formData = new FormData(form);
        const heartIcon = form.querySelector('i');

        fetch('toggle_favorite.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'added') {
                heartIcon.classList.remove('far'); // fa-regular
                heartIcon.classList.add('fas');    // fa-solid
                heartIcon.style.color = 'red';
            } else if (data.status === 'removed') {
                heartIcon.classList.remove('fas');
                heartIcon.classList.add('far');
                heartIcon.style.color = '#bbb';
            }
        })
        .catch(err => {
            console.error('خطأ في AJAX:', err);
        });
    });
});
</script>
<?php require 'reminder.php'; ?>