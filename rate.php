<?php
session_start();
require 'config.php'; // يحتوي على $conn

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['book_id'])) {
    die("📕 الكتاب غير محدد.");
}

$book_id = (int)$_GET['book_id'];

// جلب بيانات الكتاب
$stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$result = $stmt->get_result();
$book = $result->fetch_assoc();

if (!$book) {
    die("📕 الكتاب غير موجود.");
}

// عند إرسال التقييم
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rating'])) {
    $rating = (int)$_POST['rating'];

    // هل المستخدم قيّم سابقًا؟
    $check = $conn->prepare("SELECT * FROM ratings WHERE user_id = ? AND book_id = ?");
    $check->bind_param("ii", $user_id, $book_id);
    $check->execute();
    $check_result = $check->get_result();

    if ($check_result->num_rows > 0) {
        // تحديث التقييم
        $update = $conn->prepare("UPDATE ratings SET rating = ?, rated_at = NOW() WHERE user_id = ? AND book_id = ?");
        $update->bind_param("iii", $rating, $user_id, $book_id);
        $update->execute();
    } else {
        // إضافة تقييم جديد
        $insert = $conn->prepare("INSERT INTO ratings (user_id, book_id, rating, rated_at) VALUES (?, ?, ?, NOW())");
        $insert->bind_param("iii", $user_id, $book_id, $rating);
        $insert->execute();
    }

    header("Location: index.php?rated=success");
    exit;
}


?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style/styleindex.css">
    <title>⭐ تقييم الكتاب</title>
    
</head>
<body>

<div class="card">
      <?php if (!empty($book['cover_image'])): ?>
            <img src="<?= htmlspecialchars($book['cover_image']) ?>" alt="غلاف الكتاب">
        <?php else: ?>
            <img src="default_cover.jpg" alt="غلاف افتراضي">
        <?php endif; ?>
    <h2>⭐ تقييم الكتاب: <?= htmlspecialchars($book['title']); ?></h2>
    <p><?= nl2br(substr($book['description'], 0, 200)); ?></p>

      <form method="post" action="rate.php?book_id=<?= $book_id; ?>">
        <div class="stars">
            <?php for ($i = 1; $i <= 5; $i++): ?>
<button class="star-btn <?= ($i <= $current_rating && $current_rating > 0) ? 'selected' : '' ?>" type="submit" name="rating" value="<?= $i; ?>">
    <?= $i; ?> &#9734;
</button>            <?php endfor; ?>
        </div>
    </form>

    <div class="back">
        <a href="index.php">⬅️ رجوع إلى الكتب</a>
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
</script>
</body>
</html>
