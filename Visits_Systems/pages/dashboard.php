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
    <title>لوحة التحكم</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="../assets/images/smalllogotvtc.png">
</head>
<body>
    <div class="container">
        <header>
            <div class="user-info">
                <span>مرحباً، <?php echo htmlspecialchars($_SESSION['user']['username']); ?> (<?php echo ucfirst($_SESSION['user']['role']); ?>)</span>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
            </div>
            <div class="logo">
                <img src="https://tvtc.gov.sa/Style%20Library/tvtc/images/logo.svg" alt="TVTC Logo">
            </div>
        </header>
        
        <nav>
            <ul>
                <?php
                $current_user_role = $_SESSION['user']['role'] ?? '';
                $current_page_name = basename($_SERVER['PHP_SELF']);
                
                if ($current_user_role === 'admin' || $current_user_role === 'superadmin') {
                    echo '<li><a href="dashboard.php" class="' . ($current_page_name == 'dashboard.php' ? 'active' : '') . '"><i class="fas fa-tachometer-alt"></i> لوحة التحكم</a></li>';
                    echo '<li><a href="visits.php" class="' . ($current_page_name == 'visits.php' ? 'active' : '') . '"><i class="fas fa-calendar-check"></i> تتبع الزيارات</a></li>';
                }
                
                if ($current_user_role === 'superadmin') {
                    echo '<li><a href="admins.php" class="' . ($current_page_name == 'admins.php' ? 'active' : '') . '"><i class="fas fa-users-cog"></i> إدارة المشرفين</a></li>';
                    echo '<li><a href="statistics.php" class="' . ($current_page_name == 'statistics.php' ? 'active' : '') . '"><i class="fas fa-chart-bar"></i> الإحصائيات</a></li>';
                    echo '<li><a href="manage_visitor_types.php" class="' . ($current_page_name == 'manage_visitor_types.php' ? 'active' : '') . '"><i class="fas fa-tags"></i> إدارة صفة الزوار</a></li>';
                }
                
                if ($current_user_role === 'secretary' || $current_user_role === 'superadmin') {
                    echo '<li><a href="dea_visits.php" class="' . ($current_page_name == 'dea_visits.php' ? 'active' : '') . '"><i class="fas fa-plus-circle"></i> تسجيل زيارة العميد</a></li>';
                    echo '<li><a href="registered_dean_visits.php" class="' . ($current_page_name == 'registered_dean_visits.php' ? 'active' : '') . '"><i class="fas fa-list"></i> الزيارات المسجلة</a></li>';
                }
                ?>
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
      <div class="footer-content" style="text-align: center; line-height: 1.6;">
        <p class="development-credit">
            تطوير: طلاب قسم علوم الحاسب الآلي، جامعة أم القرى (برنامج التدريب التعاوني <?php echo date('Y'); ?>)
        </p>
        <p class="copyright">
            تحت إشراف: المؤسسة العامة للتدريب التقني والمهني (TVTC)
        </p>
       </div>
      </footer>
     </div>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/javascript/script.js"></script>
</body>
</html>
