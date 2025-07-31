<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php'; 
if (!is_logged_in()) {
    header('Location: dashboard.php');
    exit();
}

// --- جلب الإحصائيات العامة ---
$total_lab_visits = get_total_lab_visits();
$active_lab_visits = get_active_lab_visits();
$total_dean_visits = get_total_dean_visits();
$scheduled_dean_visits = get_dean_visits_by_status('scheduled');
$attended_dean_visits = get_dean_visits_by_status('attended');
$cancelled_dean_visits = get_dean_visits_by_status('cancelled');
$total_users = get_total_users();
$total_visitor_types = get_total_visitor_types();

// حساب النسب المئوية
$percentage_active_lab_visits = ($total_lab_visits > 0) ? round(($active_lab_visits / $total_lab_visits) * 100, 2) : 0;
$percentage_attended_dean_visits = ($total_dean_visits > 0) ? round(($attended_dean_visits / $total_dean_visits) * 100, 2) : 0;
$percentage_cancelled_dean_visits = ($total_dean_visits > 0) ? round(($cancelled_dean_visits / $total_dean_visits) * 100, 2) : 0;

// --- معالجة فلاتر البحث لزيارات المختبر ---
$lab_visit_filters = [
    'search_term' => $_GET['lab_search_term'] ?? '',
    'specialization' => $_GET['lab_specialization'] ?? '',
    'start_date' => $_GET['lab_start_date'] ?? '',
    'end_date' => $_GET['lab_end_date'] ?? '',
];
$all_specializations = get_all_specializations(); // جلب جميع التخصصات
$filtered_lab_visits = get_filtered_lab_visits($lab_visit_filters);

// --- معالجة فلاتر البحث لزيارات العميد ---
$dean_visit_filters = [
    'search_term' => $_GET['dean_search_term'] ?? '',
    'visitor_type_id' => $_GET['dean_visitor_type'] ?? '',
    'status' => $_GET['dean_status'] ?? '',
    'start_date' => $_GET['dean_start_date'] ?? '',
    'end_date' => $_GET['dean_end_date'] ?? '',
];
$all_visitor_types = get_all_visitor_types(); // جلب جميع أنواع الزوار
$filtered_dean_visits = get_filtered_dean_visits($dean_visit_filters);

// دالة مساعدة لعرض حالة الزيارة
function get_status_badge_class($status) {
    switch ($status) {
        case 'scheduled': return 'status-scheduled';
        case 'attended': return 'status-attended';
        case 'cancelled': return 'status-cancelled';
        default: return '';
    }
}

// Handle reset requests
if (isset($_POST['reset_type']) && $_SESSION['user']['role'] === 'superadmin') {
    try {
        switch ($_POST['reset_type']) {
            case 'lab_visits':
                $sql = "DELETE FROM visits";
                break;
            case 'active_lab_visits':
                $sql = "DELETE FROM visits WHERE status = 'active'";
                break;
            case 'dean_visits':
                $sql = "TRUNCATE TABLE dean_college_visits";
                break;
            case 'scheduled_dean_visits':
                $sql = "DELETE FROM dean_college_visits WHERE status = 'scheduled'";
                break;
            case 'attended_dean_visits':
                $sql = "DELETE FROM dean_college_visits WHERE status = 'attended'";
                break;
            case 'cancelled_dean_visits':
                $sql = "DELETE FROM dean_college_visits WHERE status = 'cancelled'";
                break;
            case 'users':
                $sql = "DELETE FROM users WHERE role != 'superadmin'"; // Protect superadmin
                break;
            case 'visitor_types':
                $sql = "TRUNCATE TABLE dean_college_visitor_types";
                break;
            default:
                die(json_encode(['success' => false, 'message' => 'Invalid type']));
        }
        
        $pdo->exec($sql);
        
        // إضافة رسالة تأكيد
        echo json_encode(['success' => true, 'message' => 'تم إعادة التعيين بنجاح']);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الإحصائيات المتقدمة</title>
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
            <h1><i class="fas fa-chart-line"></i> الإحصائيات الشاملة</h1>
            <div class="statistics-summary">
                <div class="stat-card">
                    <i class="fas fa-undo reset-icon" onclick="resetStats('lab_visits')" title="إعادة تعيين زيارات المختبر"></i>
                    <h3>إجمالي زيارات المختبر</h3>
                    <p><?php echo $total_lab_visits; ?></p>
                    <span class="stat-desc">عدد الزيارات المسجلة للمختبر</span>
                </div>

                <div class="stat-card active-visits">
                    <i class="fas fa-undo reset-icon" onclick="resetStats('active_lab_visits')" title="إعادة تعيين الزيارات النشطة"></i>
                    <h3>زيارات المختبر النشطة حالياً</h3>
                    <p><?php echo $active_lab_visits; ?></p>
                    <div class="progress-bar-container">
                        <div class="progress-bar" style="width: <?php echo $percentage_active_lab_visits; ?>%;"></div>
                    </div>
                    <span class="stat-desc"><?php echo $percentage_active_lab_visits; ?>% من إجمالي الزيارات</span>
                </div>
                <div class="stat-card">
                    <i class="fas fa-undo reset-icon" onclick="resetStats('dean_visits')" title="إعادة تعيين زيارات العميد"></i>
                    <h3>إجمالي زيارات العميد</h3>
                    <p><?php echo $total_dean_visits; ?></p>
                    <span class="stat-desc">عدد زيارات العميد المسجلة</span>
                </div>
                <div class="stat-card scheduled-visits">
                    <i class="fas fa-undo reset-icon" onclick="resetStats('scheduled_dean_visits')" title="إعادة تعيين الزيارات المجدولة"></i>
                    <h3>زيارات العميد المجدولة</h3>
                    <p><?php echo $scheduled_dean_visits; ?></p>
                    <span class="stat-desc">زيارات لم تتم بعد</span>
                </div>
                <div class="stat-card attended-visits">
                    <i class="fas fa-undo reset-icon" onclick="resetStats('attended_dean_visits')" title="إعادة تعيين الزيارات المكتملة"></i>
                    <h3>زيارات العميد المكتملة</h3>
                    <p><?php echo $attended_dean_visits; ?></p>
                    <div class="progress-bar-container">
                        <div class="progress-bar" style="width: <?php echo $percentage_attended_dean_visits; ?>%;"></div>
                    </div>
                    <span class="stat-desc"><?php echo $percentage_attended_dean_visits; ?>% من إجمالي زيارات العميد</span>
                </div>
                <div class="stat-card cancelled-visits">
                    <i class="fas fa-undo reset-icon" onclick="resetStats('cancelled_dean_visits')" title="إعادة تعيين الزيارات الملغاة"></i>
                    <h3>زيارات العميد الملغاة</h3>
                    <p><?php echo $cancelled_dean_visits; ?></p>
                    <div class="progress-bar-container">
                        <div class="progress-bar" style="width: <?php echo $percentage_cancelled_dean_visits; ?>%;"></div>
                    </div>
                    <span class="stat-desc"><?php echo $percentage_cancelled_dean_visits; ?>% من إجمالي زيارات العميد</span>
                </div>
                 <div class="stat-card">
                    <i class="fas fa-undo reset-icon" onclick="resetStats('users')" title="إعادة تعيين المستخدمين"></i>
                    <h3>إجمالي المستخدمين</h3>
                    <p><?php echo $total_users; ?></p>
                    <span class="stat-desc">عدد الحسابات المسجلة</span>
                </div>
                <div class="stat-card">
                    <i class="fas fa-undo reset-icon" onclick="resetStats('visitor_types')" title="إعادة تعيين أنواع الزوار"></i>
                    <h3>أنواع الزوار</h3>
                    <p><?php echo $total_visitor_types; ?></p>
                    <span class="stat-desc">عدد صفات الزوار المتاحة</span>
                </div>
            </div>

            <!-- قسم فلاتر وبحث زيارات المختبر -->
            <div class="card filter-section">
                <h2><i class="fas fa-filter"></i> بحث وتصفية زيارات المختبر</h2>
                <form action="statistics.php" method="GET" class="filter-form">
                    <input type="hidden" name="tab" value="lab_visits">
                    <div class="filter-grid">
                        <div class="form-group">
                            <label for="lab_search_term">اسم الطالب / الرقم الأكاديمي</label>
                            <input type="text" id="lab_search_term" name="lab_search_term" placeholder="ابحث بالاسم أو الرقم الأكاديمي" value="<?php echo htmlspecialchars($lab_visit_filters['search_term']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="lab_specialization">التخصص</label>
                            <select id="lab_specialization" name="lab_specialization">
                                <option value="">جميع التخصصات</option>
                                <?php foreach ($all_specializations as $spec): ?>
                                    <option value="<?php echo htmlspecialchars($spec); ?>" <?php echo ($lab_visit_filters['specialization'] == $spec) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($spec); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="lab_start_date">تاريخ البدء</label>
                            <input type="date" id="lab_start_date" name="lab_start_date" value="<?php echo htmlspecialchars($lab_visit_filters['start_date']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="lab_end_date">تاريخ الانتهاء</label>
                            <input type="date" id="lab_end_date" name="lab_end_date" value="<?php echo htmlspecialchars($lab_visit_filters['end_date']); ?>">
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> بحث</button>
                        <a href="statistics.php?tab=lab_visits" class="btn btn-secondary"><i class="fas fa-sync-alt"></i> إعادة تعيين</a>
                    </div>
                </form>
            </div>

            <!-- جدول زيارات المختبر -->
            <div class="card table-container">
                <h2><i class="fas fa-flask"></i> سجل زيارات المختبر</h2>
                <?php if (!empty($filtered_lab_visits)): ?>
                <table>
                    <thead>
                        <tr>
                            <th> </th>
                            <th>اسم الطالب</th>
                            <th>الرقم الأكاديمي</th>
                            <th>التخصص</th>
                            <th>وقت الدخول</th>
                            <th>وقت الخروج</th>
                            <th>بواسطة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; foreach ($filtered_lab_visits as $visit): ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><?php echo htmlspecialchars($visit['student_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($visit['academic_number'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($visit['specialization']); ?></td>
                            <td><?php echo htmlspecialchars($visit['check_in']); ?></td>
                            <td><?php echo htmlspecialchars($visit['check_out'] ?? 'نشط'); ?></td>
                            <td><?php echo htmlspecialchars($visit['checked_in_by_username'] ?? 'N/A'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <p class="no-results-message">لا توجد زيارات مختبر مطابقة لمعايير البحث.</p>
                <?php endif; ?>
            </div>

            <!-- قسم فلاتر وبحث زيارات العميد -->
            <div class="card filter-section">
                <h2><i class="fas fa-filter"></i> بحث وتصفية زيارات العميد</h2>
                <form action="statistics.php" method="GET" class="filter-form">
                    <input type="hidden" name="tab" value="dean_visits">
                    <div class="filter-grid">
                        <div class="form-group">
                            <label for="dean_search_term">اسم الزائر</label>
                            <input type="text" id="dean_search_term" name="dean_search_term" placeholder="ابحث باسم الزائر" value="<?php echo htmlspecialchars($dean_visit_filters['search_term']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="dean_visitor_type">صفة الزائر</label>
                            <select id="dean_visitor_type" name="dean_visitor_type">
                                <option value="">جميع الصفات</option>
                                <?php foreach ($all_visitor_types as $type): ?>
                                    <option value="<?php echo htmlspecialchars($type['id']); ?>" <?php echo ($dean_visit_filters['visitor_type_id'] == $type['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type['type_name_ar']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="dean_status">الحالة</label>
                            <select id="dean_status" name="dean_status">
                                <option value="">جميع الحالات</option>
                                <option value="scheduled" <?php echo ($dean_visit_filters['status'] == 'scheduled') ? 'selected' : ''; ?>>مجدولة</option>
                                <option value="attended" <?php echo ($dean_visit_filters['status'] == 'attended') ? 'selected' : ''; ?>>مكتملة</option>
                                <option value="cancelled" <?php echo ($dean_visit_filters['status'] == 'cancelled') ? 'selected' : ''; ?>>ملغاة</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="dean_start_date">تاريخ البدء</label>
                            <input type="date" id="dean_start_date" name="dean_start_date" value="<?php echo htmlspecialchars($dean_visit_filters['start_date']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="dean_end_date">تاريخ الانتهاء</label>
                            <input type="date" id="dean_end_date" name="dean_end_date" value="<?php echo htmlspecialchars($dean_visit_filters['end_date']); ?>">
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> بحث</button>
                        <a href="statistics.php?tab=dean_visits" class="btn btn-secondary"><i class="fas fa-sync-alt"></i> إعادة تعيين</a>
                    </div>
                </form>
            </div>

            <!-- جدول زيارات العميد -->
            <div class="card table-container">
                <h2><i class="fas fa-user-tie"></i> سجل زيارات العميد</h2>
                <?php if (!empty($filtered_dean_visits)): ?>
                <table>
                    <thead>
                        <tr>
                            <th> </th>
                            <th>اسم الزائر</th>
                            <th>صفة الزائر</th>
                            <th>تاريخ الزيارة</th>
                            <th>وقت الزيارة</th>
                            <th>السبب</th>
                            <th>الحالة</th>
                            <th>تاريخ الإنشاء</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; foreach ($filtered_dean_visits as $visit): ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><?php echo htmlspecialchars($visit['visitor_name']); ?></td>
                            <td><?php echo htmlspecialchars($visit['type_name_ar']); ?></td>
                            <td><?php echo htmlspecialchars($visit['visit_date']); ?></td>
                            <td><?php echo htmlspecialchars($visit['visit_time']); ?></td>
                            <td><span class="visit-reason" title="<?php echo htmlspecialchars($visit['visit_reason']); ?>"><?php echo htmlspecialchars(mb_substr($visit['visit_reason'], 0, 50, 'UTF-8')) . (mb_strlen($visit['visit_reason'], 'UTF-8') > 50 ? '...' : ''); ?></span></td>
                            <td><span class="status-badge <?php echo get_status_badge_class($visit['status']); ?>"><?php echo htmlspecialchars($visit['status_ar']); ?></span></td>
                            <td><?php echo htmlspecialchars($visit['created_at']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <p class="no-results-message">لا توجد زيارات عميد مطابقة لمعايير البحث.</p>
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
    </body> 
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function resetStats(type) {
    Swal.fire({
        title: 'تأكيد إعادة التعيين',
        text: 'هل أنت متأكد من رغبتك في إعادة تعيين هذه الإحصائيات؟',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'نعم، إعادة التعيين',
        cancelButtonText: 'إلغاء'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('reset_type', type);

            fetch('statistics.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'تم بنجاح!',
                        text: 'تم إعادة تعيين الإحصائيات بنجاح',
                        icon: 'success',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'حسناً'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    throw new Error(data.message || 'فشل في إعادة التعيين');
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'خطأ!',
                    text: error.message || 'حدث خطأ أثناء إعادة التعيين',
                    icon: 'error',
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'حسناً'
                });
            });
        }
    });
}
</script>


    <script src="../assets/javascript/script.js"></script>
</body>
</html>

