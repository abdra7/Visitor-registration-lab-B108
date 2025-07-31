<?php
require_once '../config/db.php'; 
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$message = '';
$message_type = ''; 

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $visitor_name = $_POST['visitor_name'];
    $visitor_type_id = (int)$_POST['visitor_type_id'];
    $visit_date = $_POST['visit_date'];
    $visit_time = $_POST['visit_time'];
    $visit_reason = $_POST['visit_reason'];

    $sql_insert = "INSERT INTO `dean_college_visits` (`visitor_name`, `visitor_type_id`, `visit_date`, `visit_time`, `visit_reason`) VALUES (:visitor_name, :visitor_type_id, :visit_date, :visit_time, :visit_reason)";

    try {
        $stmt = $pdo->prepare($sql_insert);
        $stmt->bindParam(':visitor_name', $visitor_name);
        $stmt->bindParam(':visitor_type_id', $visitor_type_id, PDO::PARAM_INT);
        $stmt->bindParam(':visit_date', $visit_date);
        $stmt->bindParam(':visit_time', $visit_time);
        $stmt->bindParam(':visit_reason', $visit_reason);

        if ($stmt->execute()) {
            $message = "تم تسجيل الزيارة بنجاح!";
            $message_type = "success";
        } else {
            $errorInfo = $stmt->errorInfo();
            $message = "حدث خطأ أثناء تسجيل الزيارة: " . $errorInfo[2];
            $message_type = "error";
        }
    } catch (PDOException $e) {
        $message = "خطأ في تحضير أو تنفيذ الاستعلام: " . $e->getMessage();
        $message_type = "error";
        error_log("PDO Error in dea_visits.php (insert): " . $e->getMessage());
    }
}

// Fetch visitor types for the dropdown (executed on both GET and POST)
$visitor_types = [];
$sql_types = "SELECT id, type_name_ar FROM dean_college_visitor_types ORDER BY type_name_ar ASC";

try {
    $stmt_types = $pdo->query($sql_types);
    $visitor_types = $stmt_types->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("PDO Error fetching visitor types: " . $e->getMessage());
    $message .= ($message ? "<br>" : "") . "تنبيه: لم يتم العثور على أنواع زوار أو حدث خطأ في جلبها.";
    $message_type = ($message_type == 'success' ? $message_type : 'warning');
}

// PDO connection is typically not closed explicitly as it closes automatically at script end.
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../assets/images/smalllogotvtc.png">
    <title>زيارات عميد الكلية</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
            font-size: 1.8rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .message.warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        textarea#reason {
            resize: none;
            height: 120px;
        }
    </style>
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
            <h1>تسجيل زيارة لعميد الكلية</h1>

            <?php if (!empty($message)): ?>
                <div class="message <?php echo htmlspecialchars($message_type); ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <h2>نموذج تسجيل الزيارة</h2>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group">
                        <label for="name">الاسم:</label>
                        <input type="text" id="name" name="visitor_name" placeholder="أدخل اسم الزائر" required>
                    </div>

                    <div class="form-group">
                        <label for="status">الصفة:</label>
                        <select id="status" name="visitor_type_id" required>
                            <option value="">اختر صفة الزائر</option>
                            <?php foreach ($visitor_types as $type): ?>
                                <option value="<?php echo htmlspecialchars($type['id']); ?>">
                                    <?php echo htmlspecialchars($type['type_name_ar']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="visitDate">تاريخ الزيارة:</label>
                        <input type="date" id="visitDate" name="visit_date" required>
                    </div>

                    <div class="form-group">
                        <label for="visitTime">وقت الزيارة:</label>
                        <input type="time" id="visitTime" name="visit_time" required>
                    </div>

                    <div class="form-group">
                        <label for="reason">سبب الزيارة:</label>
                        <textarea id="reason" name="visit_reason" rows="5" placeholder="اكتب هنا سبب الزيارة..." required></textarea>
                    </div>

                    <button type="submit">تسجيل الزيارة</button>
                </form>
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