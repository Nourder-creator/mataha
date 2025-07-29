<?php
session_start();
require_once 'config.php';
$error = "";
$show_modal = false;

if (isset($_POST['login'])) {
  $contact = trim($_POST['contact']);
  $password = $_POST['password'];

  $stmt = $conn->prepare("SELECT id, full_name, contact, password, otp_code, is_verified FROM users WHERE contact = ?");
  $stmt->bind_param("s", $contact);
  $stmt->execute();
  $result = $stmt->get_result();
$password = $_POST['password'];
$hashed_password = password_hash($password, PASSWORD_DEFAULT);



  if ($result->num_rows == 1) {
    $user = $result->fetch_assoc();
    if (password_verify($password, $user['password'])) {
      if ($user['is_verified'] == 1) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];

        // ✅ تحقق إذا كان admin بالتحديد بال contact
        if ($user['contact'] == 'AYA@gmail.COM') {
          header("Location: admin.php");
        } else {
          header("Location: index.php");
        }
        exit();

      } else {
        $_SESSION['verify_user'] = [
          'id' => $user['id'],
          'otp_code' => $user['otp_code']
        ];
        $show_modal = true;
      }
    } else {
      $error = "كلمة سر غير صحيحة";
    }
  } else {
    $error = "مستخدم غير موجود";
  }
}

if (isset($_POST['verify_code'])) {
  $code = $_POST['otp_code'];
  if (isset($_SESSION['verify_user']) && $code == $_SESSION['verify_user']['otp_code']) {
    $uid = $_SESSION['verify_user']['id'];
    $stmt = $conn->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    unset($_SESSION['verify_user']);
    $_SESSION['user_id'] = $uid;
    header("Location: index.php");
    exit();
  } else {
    $error = "رمز غير صحيح";
    $show_modal = true;
  }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>تسجيل الدخول</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="icon" type="image/png" href="favicon.png">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="theme-color" content="#512da8">
  <link rel="stylesheet" href="style/style.css">
</head>
<body>

<div class="form-box">
  <div class="logo">
    <i class="fas fa-brain logo-icon"></i>
    <span class="logo-text">متاهة</span>
  </div>
  <h2>تسجيل الدخول</h2>
  <?php if ($error) echo "<p class='error'>$error</p>"; ?>
  <form method="POST">
    <div class="input-group">
      <i class="fa fa-user"></i>
      <input type="text" name="contact" placeholder="الإيميل أو رقم الهاتف" required>
    </div>
    <div class="input-group">
      <i class="fa fa-lock"></i>
      <input type="password" name="password" placeholder="كلمة السر" required>
    </div>
    <button type="submit" name="login">تسجيل الدخول</button>
  </form>
  <p style="text-align: center; margin-top: 15px;">
    <a href="register.php">لا تملك حسابًا؟ أنشئ حسابًا</a>
  </p>
</div>

<div class="modal <?php if($show_modal) echo 'show'; ?>" id="verifyModal">
  <div class="modal-content">
    <form method="POST">
      <h3>أدخل رمز التحقق</h3>
      <input type="text" name="otp_code" placeholder="كود" required style="padding: 10px; width: 100%; margin-bottom: 15px; border-radius: 6px; border: 1px solid #ccc;">
      <button type="submit" name="verify_code">تفعيل الحساب</button>
    </form>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const modal = document.querySelector('.modal');
    if (modal.classList.contains("show")) {
      modal.style.display = "flex";
    }
    document.querySelectorAll('.input-group input').forEach(input => {
      input.addEventListener('focus', () => {
        input.parentElement.style.boxShadow = '0 0 8px #7e57c2';
      });
      input.addEventListener('blur', () => {
        input.parentElement.style.boxShadow = 'none';
      });
    });
  });
</script>

</body>
</html>
