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

// 🔍 جلب كل الكتب الموجودة في مفضلة هذا المستخدم
$sql = "SELECT books.* FROM books 
        INNER JOIN favorites ON books.id = favorites.book_id 
        WHERE favorites.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>📌 كتبي المفضلة</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="style/styleindex.css">
</head>
<body>
<?php require 'nav.php'; ?>

<script>
const menuToggle = document.querySelector('.menu-toggle');
const navLinks = document.querySelector('.nav-links');

menuToggle.addEventListener('click', () => {
  navLinks.classList.toggle('active');
});
</script>

 <header class="welcome-header">
  <div class="welcome-content">
    <div class="welcome-text">
      <h1> مرحبًا بك في <span>متاهة</span></h1>
      <p>احفظ الكتب الذي لامس روحك،
وارجع إليها كلما شئت...
فلكل قارئ، متاهته التي لا تشبه أحدًا.</p>
    </div>
    <img src="img/book-donation.png" alt="مرحبًا" class="welcome-icon">
  </div>
</header>


<?php if ($result->num_rows == 0): ?>
  <p style="text-align:center; color:gray;">😔 لا توجد كتب مفضلة حتى الآن.</p>
<?php else: ?>
<div class="book-grid">
  <?php while ($book = $result->fetch_assoc()): ?>
    <div class="book">
      <img src="<?= htmlspecialchars($book['cover_image']) ?>" alt="غلاف">
      <h4><?= htmlspecialchars($book['title']) ?></h4>
      <p><?= nl2br(substr($book['description'], 0, 100)) ?>...</p>
      <a href="<?= htmlspecialchars($book['file_path']) ?>" target="_blank">📖 قراءة</a>
    </div>
  <?php endwhile; ?>
</div>
<?php endif; ?>
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
