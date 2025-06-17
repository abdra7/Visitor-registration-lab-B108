<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit();
}

$active_visits = get_active_visits();
$recent_visits = get_visit_history(5);
?>
<!DOCTYPE html>
<html>
<head>
    <title>لوحة التحكم - متتبع زيارات المختبر</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=2">
    <link rel="icon" type="image/png" href="../assets/images/smalllogotvtc.png">
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <img src="https://tvtc.gov.sa/Style%20Library/tvtc/images/logo.svg" alt="شعار TVTC">
            </div>
            <div class="user-info">
                <span>مرحباً، <?php echo $_SESSION['user']['username']; ?> (<?php echo ucfirst($_SESSION['user']['role']); ?>)</span>
                <a href="logout.php">تسجيل الخروج</a>
            </div>
        </header>
        
        <nav>
            <ul>
                <li><a href="dashboard.php" class="active">لوحة التحكم</a></li>
                <li><a href="visits.php">تتبع الزيارات</a></li>
                <?php if (is_superadmin()): ?>
                    <li><a href="admins.php">إدارة المشرفين</a></li>
                    <li><a href="statistics.php">الإحصائيات</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        
        <main>
            <h1>لوحة التحكم</h1>
            
            <div class="stats">
                <div class="stat-card">
                    <h3>الزيارات النشطة</h3>
                    <p><?php echo count($active_visits); ?></p>
                </div>
                <div class="stat-card">
                    <h3>زيارات اليوم</h3>
                    <p><?php echo count($recent_visits); ?></p>
                </div>
            </div>
            
            <div class="recent-visits">
                <h2>الزيارات الحديثة</h2>
                <?php if (!empty($recent_visits)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>الطالب</th>
                                <th>الرقم الأكاديمي</th>
                                <th>التخصص</th>
                                <th>وقت الدخول</th>
                                <th>وقت الخروج</th>
                                <th>تم التسجيل بواسطة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_visits as $visit): ?>
                                <tr>
                                    <td><?php echo $visit['student_name']; ?></td>
                                    <td><?php echo $visit['academic_number']; ?></td>
                                    <td><?php echo $visit['specialization']; ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($visit['check_in'])); ?></td>
                                    <td><?php echo $visit['check_out'] ? date('Y-m-d H:i', strtotime($visit['check_out'])) : 'نشط'; ?></td>
                                    <td><?php echo $visit['username']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>لا توجد زيارات حديثة.</p>
                <?php endif; ?>
            </div>
        </main>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> متتبع زيارات المختبر - الكلية التقنية TVTC</p>
        </footer>
    </div>
     <script src="../assets/javascript/script.js"></script>
</body>
</html>
