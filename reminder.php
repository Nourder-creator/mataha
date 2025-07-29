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


<style>  .reminder-button {
  position: absolute;
  top: 10px;
  left: 10px;
  font-size: 20px;
  color: #bbb;
  cursor: pointer;
  z-index: 10;
  transition: color 0.3s ease;
}
.reminder-button:hover {
  color: #7b4bb7;
}

#reminderModal {
  display: none;
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%) scale(0.9);
  background: white;
  padding: 30px 25px;
  border-radius: 15px;
  box-shadow: 0 0 25px rgba(0,0,0,0.3);
  z-index: 9999;
  width: 320px;
  max-width: 90%;
  text-align: center;
  transition: transform 0.3s ease, opacity 0.3s ease;
  opacity: 0;
}

#reminderModal.show {
  transform: translate(-50%, -50%) scale(1);
  opacity: 1;
}

#reminderModal h3 {
  margin-bottom: 15px;
  color: #7b4bb7;
  font-size: 1.2em;
}

#reminderModal label {
  font-weight: bold;
  color: #555;
}

#reminderModal input[type="datetime-local"] {
  width: 85%;
  padding: 8px 10px;
  margin-top: 5px;
  border: 1px solid #ccc;
  border-radius: 8px;
  font-size: 14px;
}

#reminderModal button[type="submit"] {
  background: #7b4bb7;
  color: white;
  border: none;
  padding: 8px 16px;
  margin: 15px 5px 0 5px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: bold;
  transition: background 0.3s ease;
}

#reminderModal button[type="submit"]:hover {
  background: #633aa2;
}

#reminderModal button[type="button"] {
  background: #ccc;
  color: #333;
  border: none;
  padding: 8px 16px;
  margin: 15px 5px 0 5px;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.3s ease;
}

#reminderModal button[type="button"]:hover {
  background: #bbb;
}
</style>

<script>function openReminderModal(bookId, title) {
  document.getElementById('reminderBookId').value = bookId;
  document.getElementById('reminderTitle').innerText = '⏰ تذكير بـ: ' + title;
  document.getElementById('reminderModal').style.display = 'block';
}</script>
<!-- نافذة التذكير -->
<div id="reminderModal" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%);
 background:white; padding:20px; border-radius:10px; box-shadow:0 0 20px rgba(0,0,0,0.3); z-index:9999;">
  <h3 id="reminderTitle">⏰ تذكير</h3>
  <form method="post" action="save_reminder.php">
    <input type="hidden" name="book_id" id="reminderBookId">
    <label>اختر الوقت:</label><br>
    <input type="datetime-local" name="reminder_time" required><br><br>
    <button type="submit"> حفظ</button>
    <button type="button" onclick="document.getElementById('reminderModal').style.display='none'"> إغلاق</button>
  </form>
</div>

<script>function openReminderModal(bookId, title) {
  document.getElementById('reminderBookId').value = bookId;
  document.getElementById('reminderTitle').innerText = '⏰ تذكير بـ: ' + title;
  const modal = document.getElementById('reminderModal');
  modal.style.display = 'block';
  setTimeout(() => modal.classList.add('show'), 10);
}
function closeReminderModal() {
  const modal = document.getElementById('reminderModal');
  modal.classList.remove('show');
  setTimeout(() => modal.style.display = 'none', 300);
}

</script>
<script>
<?php if (!empty($reminders)): ?>
  const reminders = <?= json_encode($reminders, JSON_UNESCAPED_UNICODE) ?>;
  reminders.forEach(r => {
    const popup = document.createElement('div');

    popup.innerHTML = `📚 <strong>${r.title}</strong><br>⏰ لقد حان وقت قراءة هذا الكتاب!`;
    popup.style.cssText =`
   
      position: fixed;
      bottom: 20px;
      right: 20px;
      background: linear-gradient(135deg, #9c6ade, #bfa7f7);
      color: white;
      padding: 20px;
      border-radius: 15px;
      font-size: 16px;
      box-shadow: 0 0 15px rgba(0,0,0,0.3);
      z-index: 99;
      animation: fadeIn 1s ease-in-out;
   `;
    document.body.appendChild(popup);
    setTimeout(() => popup.remove(), 9000000); // يختفي بعد 10 ثواني
  });
<?php endif; ?>
</script> <script>function checkReminders() {
  fetch('save_reminder.php')
    .then(res => res.json())
    .then(reminders => {
      reminders.forEach(r => {
        const popup = document.createElement('div');
        popup.innerHTML = `📚 <strong>${r.title}</strong><br>⏰ لقد حان وقت قراءة هذا الكتاب!`;
       
         
      });
    })
    .catch(err => console.error('خطأ في جلب التذكيرات:', err));
}

// 🔁 تحقق كل دقيقة (أو أقل حسب رغبتك)
setInterval(checkReminders, 10000);

// 🔁 يمكنك أيضًا استدعاؤها مباشرة عند فتح الصفحة
checkReminders();
</script>
<script>
document.querySelectorAll('.read-button').forEach(button => {
    button.addEventListener('click', function(e) {
        const bookId = this.dataset.bookId;

        fetch('mark_as_read.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'book_id=' + encodeURIComponent(bookId)
        })
        .then(res => res.json())
        .then(data => {
            console.log('قراءة مسجلة:', data.status);
        })
        .catch(err => {
            console.error('❌ فشل في تسجيل القراءة:', err);
        });
    });
});
</script>