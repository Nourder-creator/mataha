<?php
session_start();
require_once 'config.php';
$success = $error = "";

if (isset($_POST['register'])) {
  $name = trim($_POST['full_name']);
  $contact = trim($_POST['contact']);
  $pass = $_POST['password'];
  $confirm = $_POST['confirm_password'];

  if ($pass !== $confirm) {
    $error = "تأكيد كلمة السر غير مطابق";
  } else {
    $stmt = $conn->prepare("SELECT id FROM users WHERE contact = ?");
    $stmt->bind_param("s", $contact);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
      $error = "هذا البريد أو رقم الهاتف مسجل مسبقًا.";
    } else {
      $otp = rand(100000, 999999);
      $hash = password_hash($pass, PASSWORD_DEFAULT);
      $insert = $conn->prepare("INSERT INTO users (full_name, contact, password, otp_code, is_verified) VALUES (?, ?, ?, ?, 0)");
      $insert->bind_param("ssss", $name, $contact, $hash, $otp);
      $insert->execute();
      $success = "رمز التحقق الخاص بك هو: <b>$otp</b><br>الرجاء تسجيل الدخول لإكمال عملية التحقق.";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>إنشاء حساب</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="style/style.css">

</head>
<body>
<div class="form-box">
  <div class="logo">
    <span>متاهة</span>
    <h2>إنشاء حساب</h2>
    <i class="fas fa-lightbulb"></i>
  </div>

  <?php if ($error) echo "<p class='error'>$error</p>"; ?>
  <?php if ($success) echo "<p class='success'>$success</p><a href='login.php'><button>الذهاب إلى تسجيل الدخول</button></a>"; ?>

  <?php if (!$success): ?>
  <form method="POST">
    <input type="text" name="full_name" placeholder="الاسم الكامل" required>
    <input type="text" name="contact" placeholder="الإيميل أو رقم الهاتف" required>
    <input type="password" name="password" placeholder="كلمة السر" required>
    <input type="password" name="confirm_password" placeholder="تأكيد كلمة السر" required>
    <button type="submit" name="register">إنشاء حساب</button>
  </form>
  <?php endif; ?>

  <p><a href="login.php">لديك حساب؟ سجل الدخول</a></p>
</div>
</body>
</html>
