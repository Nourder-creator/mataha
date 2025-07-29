<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$user_id = $_SESSION['user_id'];

// جلب بيانات المستخدم
$stmt = $conn->prepare("SELECT full_name, contact, photo FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
  die("❌ المستخدم غير موجود.");
}
$user = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>ملفي الشخصي </title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    * {
      box-sizing: border-box;
      font-family: 'Cairo', sans-serif;
    
    }

 body {
  overflow: hidden;
        background: #f5f5f5;

  min-height: 100vh;
  display: flex;
  justify-content: center; 
  align-items: center;   
  margin:30px; 

}

  /* ✅ خلفية فقاعات */
  body::before {
    content: "";
    position: absolute;
    width: 250%;
    height: 250%;
    background: radial-gradient(circle at 20% 30%,rgb(27, 76, 116) 10%, transparent 11%),
                radial-gradient(circle at 80% 70%,rgb(89, 13, 102) 10%, transparent 11%),
                radial-gradient(circle at 50% 50%,rgb(29, 83, 128) 10%, transparent 11%);
    animation: backgroundMove 30s linear infinite;
    opacity: 0.12;
    z-index: 0;
  }

  @keyframes backgroundMove {
    from { transform: translate(0, 0); }
    to { transform: translate(-30%, -30%); }
  }
   .container {
    background: linear-gradient(145deg,rgb(255, 255, 255));
      border-radius: 60px;
      backdrop-filter: blur(10px);
      padding: 20px 10px;
      margin-top: 50px;
      width: 100%;
      max-width: 700px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.3);
    }

 .navbar {
  
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: rgb(159, 141, 207);
  padding: 15px 40px;
  position: fixed; /* بدل sticky */
  top: 0;
  left: 0; /* أضف left */
  width: 100%; /* أضف width لتغطي الصفحة بالكامل */
  z-index: 1000;
  box-shadow: 0 4px 20px rgb(172, 156, 201);
}


  .navbar .logo {
  display: flex;
      gap: 10px;
      font-size: 20px;
      font-weight: bold;
      color: #fff;
  align-items: center;
  font-size: 1.6em;
  font-weight: bold;
 
  cursor: pointer;
  transition: transform 0.3s;
}

.navbar .logo:hover {
  transform: scale(1.1);
}

.navbar .logo i {
   font-size: 24px;
  
  margin-right: 10px;
  color:rgb(255, 255, 255);;
  animation: glowLogo 3s ease-in-out infinite alternate;
}
@keyframes glowLogo {
  from { text-shadow: 0 0 10px rgb(255, 255, 255);; }
  to { text-shadow: 0 0 20px color:rgb(201, 153, 230);; }
}

.nav-links {
  display: flex;
  align-items: center;
  gap: 25px;
}

.nav-links span {
  font-weight: 500;
  font-size: 1em;
  color: #ccc;
}

.nav-links a {
   margin-left: 20px;
      text-decoration: none;
      color: #eee;
      font-weight: 500;
      position: relative;
}

    .nav-links a::after {
      content: '';
      display: block;
      width: 0;
      height: 2px;
      background:rgb(255, 255, 255);
      transition: width 0.3s;
      position: absolute;
      bottom: -5px;
      left: 0;
    }

    .nav-links a:hover::after {
      width: 100%;
    }

.nav-links a:hover {
  color:rgb(51, 7, 105);;
 
  transform: scale(1.15);
}

/* Menu toggle (mobile) */
.menu-toggle {
  display: none;
  font-size: 2em;
  color: #7b4bb7;
  cursor: pointer;
  transition: transform 0.3s;
}

    .container h2 {
      text-align: center;
      margin: 1px;
      font-size: 26px;
      color:rgb(0, 0, 0);
    }

    .profile-photo {
      display: flex;
      justify-content: center;
      margin-bottom: 2px;
    }

    .profile-photo img {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      border: 3px solidrgb(162, 151, 211);
      object-fit: cover;
      box-shadow: 0 0 20px rgba(0,0,0,0.4);
    }

    form .form-group {
      margin-bottom: 5px;
    }

    form label {
      display: block;
      margin-bottom: 3px;
      font-weight: bold;
      color:rgb(0, 0, 0);
    }

    form input[type="text"],
    form input[type="password"],
    form input[type="file"] {
      width: 100%;
      padding: 10px 15px;
      border: none;
      border-radius: 60px;
      outline: none;
      font-size: 16px;
    }

    form input[type="text"],
    form input[type="password"] {
      background: rgba(99, 75, 75, 0.2);
      color:rgb(0, 0, 0);
    }

    form input[type="text"]::placeholder,
    form input[type="password"]::placeholder {
      color:rgb(0, 0, 0);margin:20px;
    }

    form input[type="submit"] {
  width: 100%;
  background: linear-gradient(135deg, rgb(191, 179, 224), rgb(176, 153, 218));
  color: #333;
  padding: 12px;
  border: none;
  border-radius: 60px;
  font-size: 18px;
  font-weight: bold;
  cursor: pointer;
  transition: background 0.3s, transform 0.2s;
  margin-top: 20px; /* فقط هامش علوي */
}

    form input[type="submit"]:hover {
      background: linear-gradient(135deg,rgb(144, 126, 194),rgb(136, 132, 156));
      transform: scale(1.02);
      
      
    }

    @media (max-width: 600px) {
      .nav-links a {
        margin-left: 10px;
        font-size: 14px;
      }

      .navbar .logo {
        font-size: 16px;
      }

      .navbar .logo i {
        font-size: 20px;
      }
    }
  </style>
</head>
<body>

 <nav class="navbar">
    <div class="logo">
      <i class="fas fa-book-reader"></i>
      <span>متاهة</span>
    </div><div class="menu-toggle">
  <i class="fas fa-bars"></i>
</div>

    <div class="nav-links">
      <a href="logout.php"> خروج</a> 
       <a href="recommend.php"> التوصيات</a>
      <a href="favorite.php">المفضلة</a>
       <a href="index.php"> الرئيسية</a>
 <a href="profil.php">صفحتي</a>           

    </div>
  </nav> <script>
const menuToggle = document.querySelector('.menu-toggle');
const navLinks = document.querySelector('.nav-links');

menuToggle.addEventListener('click', () => {
  navLinks.classList.toggle('active');
});
</script>

<div class="container">
  <h2>🧍 ملفي الشخصي</h2>
  <form action="update_profile.php" method="post" enctype="multipart/form-data">
  <div class="profile-photo">
    <label for="photo-input">
      <img id="profile-img" src="uploads/<?= htmlspecialchars($user['photo']) ?: 'default.png' ?>" alt="صورتي" title="اضغط لتغيير الصورة">
    </label>
    <input type="file" name="photo" id="photo-input" style="display: none;" accept="image/*" onchange="previewImage(event)">
  </div>

  <div class="form-group">
    <label>الاسم الكامل</label>
    <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
  </div>

  <div class="form-group">
    <label>البريد أو الهاتف</label>
    <input type="text" name="contact" value="<?= htmlspecialchars($user['contact']) ?>" required>
  </div>

  <div class="form-group">
    <label>كلمة السر الجديدة</label>
    <input type="password" name="new_password" placeholder="اتركه فارغًا إن لم ترد تغييره">
  </div>

  <input type="submit" value="💾 حفظ التغييرات">
</form>


</div>
<style>.profile-photo img {
  cursor: pointer;
}
</style>
<script>
function previewImage(event) {
  const reader = new FileReader();
  reader.onload = function(){
    const output = document.getElementById('profile-img');
    output.src = reader.result;
  };
  reader.readAsDataURL(event.target.files[0]);
}
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
