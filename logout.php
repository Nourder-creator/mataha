<?php
session_start();
session_unset(); // مسح كل متغيرات الجلسة
session_destroy(); // إنهاء الجلسة
header('Location: login.php');
exit;
?>