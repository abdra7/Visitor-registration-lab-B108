<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    header('Location: dashboard.php');
    exit();
}

$today_visits = get_visit_count_by_period('today');
$month_visits = get_visit_count_by_period('this_month');
$year_visits = get_visit_count_by_period('this_year');
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>الإحصائيات - متتبع زيارات المختبر</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=2">
    <link rel="icon" type="image/png" href="../assets/images/smalllogotvtc.png">
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <img src="https://tvtc.gov.sa/Style%20Library/tvtc/images/logo.svg " alt="شعار TVTC">
            </div>
            <div class="user-info">
                <span>مرحباً، <?php echo $_SESSION['user']['username']; ?> (<?php echo ucfirst($_SESSION['user']['role']); ?>)</span>
                <a href="logout.php">تسجيل الخروج</a>
            </div>
        </header>

        <nav>
            <ul>
                <li><a href="dashboard.php">لوحة التحكم</a></li>
                <li><a href="visits.php">تتبع الزيارات</a></li>
                <li><a href="admins.php">إدارة المشرفين</a></li>
                <li><a href="statistics.php" class="active">الإحصائيات</a></li>
            </ul>
        </nav>

        <main>
            <h1>إحصائيات زيارات المختبر</h1>

            <div class="stats">
                <div class="stat-card">
                    <h3>زيارات اليوم</h3>
                    <p><?php echo $today_visits; ?></p>
                </div>
                <div class="stat-card">
                    <h3>زيارات هذا الشهر</h3>
                    <p><?php echo $month_visits; ?></p>
                </div>
                <div class="stat-card">
                    <h3>زيارات هذا العام</h3>
                    <p><?php echo $year_visits; ?></p>
                </div>
            </div>
        </main>

        <footer>
            <p>&copy; <?php echo date('Y'); ?> متتبع زيارات المختبر - الكلية التقنية TVTC</p>
        </footer>
    </div>
     <script src="../assets/javascript/script.js"></script>
</body>
</html>
