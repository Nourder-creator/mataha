<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
  die("❌ غير مصرح.");
}

$user_id = $_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT full_name, points FROM users WHERE id=$user_id"));

$points = (int)$user['points'];
$badge = 'مبتدئ';
if ($points >= 200) $badge = 'أسطورة';
elseif ($points >= 100) $badge = 'ذهبي';
elseif ($points >= 50)  $badge = 'محترف';
elseif ($points >= 20)  $badge = 'نشط';

$cert_id = strtoupper("MTH-" . $user_id . "-" . date('dmY'));
$date = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>شهادة إنجاز</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@600&family=Great+Vibes&display=swap');

    body {
      margin: 0;
      padding: 0;
      background: linear-gradient(145deg, #f5f5f5, #e8f5e9);
      font-family: 'Cairo', sans-serif;
      direction: rtl;
    }

    .certificate {
      width: 700px;
      margin: 50px auto;
      background: #fff;
      border: 10px solid #d4af37;
      padding: 40px 50px;
      border-radius: 18px;
      box-shadow: 0 20px 40px rgba(0,0,0,0.1);
      text-align: center;
      position: relative;
      background-image: url('https://i.ibb.co/1fnwZ7B/pattern.png');
      background-size: cover;
      background-blend-mode: lighten;
    }

    .logo {
      font-size: 40px;
      margin-bottom: 10px;
      color: #d4af37;
      animation: pulse 2s infinite;
    }

    @keyframes pulse {
      0%, 100% { transform: scale(1); opacity: 1; }
      50% { transform: scale(1.1); opacity: 0.8; }
    }

    .title {
      font-size: 32px;
      font-weight: bold;
      color: #c0902f;
      margin-bottom: 15px;
      border-bottom: 2px dashed #d4af37;
      display: inline-block;
      padding-bottom: 5px;
    }

    .badge {
      display: inline-block;
      background: linear-gradient(to right, #d4af37, #fdd835);
      color: #000;
      font-size: 14px;
      padding: 6px 20px;
      border-radius: 50px;
      font-weight: bold;
      margin: 20px 0;
      box-shadow: 0 3px 6px rgba(0,0,0,0.1);
    }

    .cert-body {
      font-size: 18px;
      color: #333;
      line-height: 2;
      margin-top: 20px;
    }

    .name {
      font-size: 24px;
      font-weight: bold;
      color: #222;
      margin: 15px 0;
      border-bottom: 2px solid #d4af37;
      display: inline-block;
      padding: 5px 10px;
    }

    .footer-area {
      display: flex;
      justify-content: space-between;
      margin-top: 50px;
      padding: 0 20px;
    }

    .footer-right {
      text-align: right;
    }

    .footer-right .role {
      font-size: 15px;
      color: #555;
    }

    .footer-right .sign {
      font-family: 'Great Vibes', cursive;
      font-size: 28px;
      color: #d4af37;
      margin-top: 5px;
    }

    .stamp {
      width: 75px;
      height: 75px;
      margin-top: 10px;
      border: 3px solid #d4af37;
      border-radius: 50%;
      font-size: 11px;
      font-weight: bold;
      color: #d4af37;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
    }

    .stamp::before {
      content: '';
      position: absolute;
      inset: 6px;
      border: 1px dashed #d4af37;
      border-radius: 50%;
    }

    .footer-left {
      text-align: left;
      font-size: 13px;
      color: #888;
      align-self: flex-end;
    }

    .actions {
      text-align: center;
      margin-top: 30px;
    }

    .actions button {
      background: linear-gradient(to right, #c0921f, #a77b1c);
      color: white;
      padding: 10px 24px;
      font-size: 16px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: 0.3s;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .actions button:hover {
      background: linear-gradient(to right, #a77b1c, #8d6717);
      transform: scale(1.03);
    }

    @media print {
      .actions { display: none; }
      body { background: white; }
    }
  </style>
</head>
<body>

  <div class="certificate">


    <div class="title">شهادة إنجاز</div>
    <br> <div class="badge"> <?= $badge ?></div>
   

    <div class="cert-body">
      تُمنح هذه الشهادة إلى:<br>
      <div class="name"><?= htmlspecialchars($user['full_name']) ?></div>
      تقديراً لنشاطه ومساهمته الفعالة على منصة <strong>متاهة للقراءة والتوصيات</strong><br>
      بعد تحقيقه <strong><?= $points ?> نقطة</strong> بكل جدارة.
    </div>

    <div class="footer-area">
      <div class="footer-right">
        <div class="role">مديرة المنصة</div>
        <div class="sign">Aya</div>
      </div>
      <div class="footer-left">
        رقم الشهادة: <?= $cert_id ?><br>
        التاريخ: <?= $date ?>
      </div>
    </div>

  </div>

  <div class="actions">
    <button onclick="window.print()">🖨 طباعة الشهادة</button>
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
