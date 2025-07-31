<?php
require_once '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    if (login($username, $password)) {
        redirect('dashboard.php');
    } else {
        $error = "Invalid username or password";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>تسجيل الدخول </title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="icon" type="image/png" href="../assets/images/smalllogotvtc.png">
</head>
<body style="background-image: url('../assets/images/img2.jpg'); background-size: cover; background-position: center; background-repeat: no-repeat;"></body>

<body>
   
    <div class="login-container">
        <div class="logo">
            <img src="https://tvtc.gov.sa/Style%20Library/tvtc/images/logo.svg" alt="TVTC Logo">
        </div>
        <h2>تسجيل الدخول </h2>
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="اسم المستخدم" required>
            <input type="password" name="password" placeholder="كلمة المرور" required>
            <button type="submit">دخول</button>
        </form>
    </div>
      <script src="assets/javascript/script.js"></script>
</body>
</html>