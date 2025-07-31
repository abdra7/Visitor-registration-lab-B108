<?php
// احذف فورم البحث في هاذي الصفحه 
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. تضمين ملف اتصال قاعدة البيانات
$host = "localhost";
$username = "root";
$password = "";
$dbname = "b108";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// 2. محاكاة بيانات الجلسة
if (!isset($_SESSION['user']['username']) || !isset($_SESSION['user']['role'])) {
    $_SESSION['user'] = ['username' => 'محمد السبيعي', 'role' => 'secretary'];
}

// --- 3. معالجة طلبات تحديث الحالة وتعديل الزيارة ---
$message = '';
$error_message = '';

// Handle status update
if (isset($_GET['action']) && $_GET['action'] === 'update_status' && isset($_GET['id']) && isset($_GET['status'])) {
    $visit_id = $_GET['id'];
    $new_status = $_GET['status'];

    $allowed_statuses = ['Scheduled', 'Attended', 'Cancelled'];
    if (!in_array($new_status, $allowed_statuses)) {
        $error_message = "حالة غير صالحة.";
    } else {
        try {
            $stmt_update_status = $pdo->prepare("UPDATE dean_college_visits SET status = :status WHERE id = :id");
            $stmt_update_status->bindParam(':status', $new_status);
            $stmt_update_status->bindParam(':id', $visit_id, PDO::PARAM_INT);

            if ($stmt_update_status->execute()) {
                $message = "تم تحديث حالة الزيارة بنجاح.";
                header("Location: registered_dean_visits.php?message=" . urlencode($message));
                exit();
            } else {
                $error_message = "فشل تحديث حالة الزيارة.";
            }
        } catch (PDOException $e) {
            $error_message = "خطأ في قاعدة البيانات أثناء تحديث الحالة: " . $e->getMessage();
        }
    }
}

// Handle delete visit
if (isset($_GET['action']) && $_GET['action'] === 'delete_visit' && isset($_GET['id'])) {
    $visit_id = $_GET['id'];
    try {
        $stmt_delete = $pdo->prepare("DELETE FROM dean_college_visits WHERE id = :id");
        $stmt_delete->bindParam(':id', $visit_id, PDO::PARAM_INT);
        if ($stmt_delete->execute()) {
            $message = "تم حذف الزيارة بنجاح.";
            header("Location: registered_dean_visits.php?message=" . urlencode($message));
            exit();
        } else {
            $error_message = "فشل حذف الزيارة.";
        }
    } catch (PDOException $e) {
        $error_message = "خطأ في قاعدة البيانات أثناء الحذف: " . $e->getMessage();
    }
}

// Handle edit visit form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_visit'])) {
    $visit_id = $_POST['id'];
    $visitor_name = $_POST['visitor_name'];
    $visitor_type_id = $_POST['visitor_type_id'];
    $visit_date = $_POST['visit_date'];
    $visit_time = $_POST['visit_time'];
    $visit_reason = $_POST['visit_reason'];
    $status = $_POST['status'];

    try {
        $update_stmt = $pdo->prepare("UPDATE dean_college_visits SET 
            visitor_name = :visitor_name, 
            visitor_type_id = :visitor_type_id, 
            visit_date = :visit_date, 
            visit_time = :visit_time, 
            visit_reason = :visit_reason,
            status = :status
            WHERE id = :id");

        $update_stmt->bindParam(':visitor_name', $visitor_name);
        $update_stmt->bindParam(':visitor_type_id', $visitor_type_id, PDO::PARAM_INT);
        $update_stmt->bindParam(':visit_date', $visit_date);
        $update_stmt->bindParam(':visit_time', $visit_time);
        $update_stmt->bindParam(':visit_reason', $visit_reason);
        $update_stmt->bindParam(':status', $status);
        $update_stmt->bindParam(':id', $visit_id, PDO::PARAM_INT);

        if ($update_stmt->execute()) {
            $message = "تم حفظ تعديلات الزيارة بنجاح.";
            header("Location: registered_dean_visits.php?message=" . urlencode($message));
            exit();
        } else {
            $error_message = "فشل حفظ تعديلات الزيارة.";
        }
    } catch (PDOException $e) {
        $error_message = "خطأ في قاعدة البيانات أثناء تعديل الزيارة: " . $e->getMessage();
    }
}

// Handle request to show edit form for a specific visit
$edit_visit_data = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit_visit' && isset($_GET['id'])) {
    $edit_visit_id = $_GET['id'];
    try {
        $stmt_fetch_edit = $pdo->prepare("SELECT * FROM dean_college_visits WHERE id = :id");
        $stmt_fetch_edit->bindParam(':id', $edit_visit_id, PDO::PARAM_INT);
        $stmt_fetch_edit->execute();
        $edit_visit_data = $stmt_fetch_edit->fetch(PDO::FETCH_ASSOC);

        if (!$edit_visit_data) {
            $error_message = "الزيارة المطلوبة للتعديل غير موجودة.";
        }
    } catch (PDOException $e) {
        $error_message = "خطأ في قاعدة البيانات عند جلب بيانات الزيارة للتعديل: " . $e->getMessage();
    }
}

// Check for messages passed via GET after redirects
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}

// --- 4. جلب أنواع الزوار---
$visitor_types_stmt = $pdo->query("SELECT id, type_name_ar AS type_name FROM dean_college_visitor_types ORDER BY type_name_ar");
$visitor_types = $visitor_types_stmt->fetchAll(PDO::FETCH_ASSOC);

// 5. إعداد مصفوفة لترجمة وتنسيق حالة الزيارة
$status_map = [
    'Scheduled' => ['text' => 'مجدولة', 'class' => 'status-scheduled'],
    'Attended'  => ['text' => 'تمت', 'class' => 'status-attended'],
    'Cancelled' => ['text' => 'ملغاة', 'class' => 'status-cancelled']
];

// 6. جلب بيانات الزيارات لعرض الجدول
$sql = "
    SELECT 
        dcv.id, 
        dcv.visitor_name, 
        dcv.visit_date, 
        dcv.visit_time, 
        dcv.visit_reason, 
        dcv.status,
        vt.type_name_ar AS visitor_type,
        dcv.created_at
    FROM 
        dean_college_visits AS dcv
    LEFT JOIN 
        dean_college_visitor_types AS vt ON dcv.visitor_type_id = vt.id
    ORDER BY 
        dcv.visit_date DESC, dcv.visit_time DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$visits = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 7. جلب إحصائيات الزيارات
$stats = [];
$stats_stmt = $pdo->query("SELECT status, COUNT(*) as count FROM dean_college_visits GROUP BY status");
$stats_rows = $stats_stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($stats_rows as $row) {
    $stats[$row['status']] = $row['count'];
}

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>جدول زيارات العميد</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="../assets/images/smalllogotvtc.png">
    <style>
    @media print {
    body * {
        visibility: hidden;
        margin: 0 !important;
        padding: 0 !important;
    }
    
    .table-container,
    .table-container * {
        visibility: visible;
    }
    
    .table-container {
        position: absolute;
        width: 100%;
        left: 0;
        top: 0;
        padding: 15px;
        box-sizing: border-box;
    }
    
    .action-buttons,
    .dashboard-header {
        display: none !important;
    }
}

    </style>
</head>
<body id="page-registered-dean-visits">
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
            <div class="dashboard-header">
                <h1><i class="fas fa-calendar-alt"></i> جدول زيارات عميد الكلية</h1>
                <div class="action-buttons">
                    <button class="btn btn-secondary" onclick="window.print()"><i class="fas fa-print"></i> طباعة</button>
                    <a href="dea_visits.php" class="btn btn-primary"><i class="fas fa-plus"></i> زيارة جديدة</a>
                </div>
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="message success-message">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="message error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            
<!-- Statistics Cards -->
<div class="stats-container">
    <div class="stat-card">
        <div class="stat-label">إجمالي الزيارات</div>
        <div class="stat-value"><?php echo count($visits); ?></div>
        <div class="stat-desc">جميع الزيارات المسجلة</div>
    </div>
    
    <div class="stat-card scheduled">
        <div class="stat-label">الزيارات المجدولة</div>
        <div class="stat-value"><?php echo $stats['scheduled'] ?? 0; ?></div>
        <div class="stat-desc">زيارات قيد الانتظار</div>
    </div>
    
    <div class="stat-card attended">
        <div class="stat-label">الزيارات المنتهية</div>
        <div class="stat-value"><?php echo $stats['attended'] ?? 0; ?></div>
        <div class="stat-desc">زيارات تمت بنجاح</div>
    </div>
    
    <div class="stat-card cancelled">
        <div class="stat-label">الزيارات الملغاة</div>
        <div class="stat-value"><?php echo $stats['cancelled'] ?? 0; ?></div>
        <div class="stat-desc">زيارات تم إلغاؤها</div>
    </div>
</div>
            <!-- Filters Section -->
            <div class="filters-container">
                <div class="filter-group">
                    <label>نوع الزائر</label>
                    <select id="visitorTypeFilter">
                        <option value="">جميع الأنواع</option>
                        <?php
                        foreach ($visitor_types as $type) {
                            echo '<option value="' . htmlspecialchars($type['type_name']) . '">' . htmlspecialchars($type['type_name']) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>حالة الزيارة</label>
                    <select id="statusFilter">
                        <option value="">جميع الحالات</option>
                        <option value="Scheduled">مجدولة</option>
                        <option value="Attended">تمت</option>
                        <option value="Cancelled">ملغاة</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>من تاريخ</label>
                    <input type="date" id="fromDateFilter">
                </div>
                
                <div class="filter-group">
                    <label>إلى تاريخ</label>
                    <input type="date" id="toDateFilter">
                </div>
                
                <div class="filter-group">
                    <label>بحث</label>
                    <input type="text" id="searchInput" placeholder="ابحث بالاسم، الصفة، السبب...">
                </div>
                
                <div class="filter-buttons">
                    <button class="btn btn-secondary" onclick="resetFilters()"><i class="fas fa-redo"></i> إعادة تعيين</button>
                    <button class="btn btn-primary" onclick="applyFilters()"><i class="fas fa-search"></i> بحث</button>
                </div>
            </div>
            
            <?php if ($edit_visit_data): // Show edit form ?>
                <div class="form-container">
                    <h1><i class="fas fa-edit"></i> تعديل معلومات الزيارة</h1>
                    <form action="registered_dean_visits.php" method="POST">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($edit_visit_data['id']); ?>">
                        <input type="hidden" name="update_visit" value="1">
                        
                        <div class="form-group">
                            <label for="visitor_name">اسم الزائر:</label>
                            <input type="text" id="visitor_name" name="visitor_name" value="<?php echo htmlspecialchars($edit_visit_data['visitor_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="visitor_type_id">نوع الزائر:</label>
                            <select id="visitor_type_id" name="visitor_type_id" required>
                                <?php foreach ($visitor_types as $type): ?>
                                    <option value="<?php echo htmlspecialchars($type['id']); ?>" <?php echo ($type['id'] == $edit_visit_data['visitor_type_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type['type_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="visit_date">تاريخ الزيارة:</label>
                            <input type="date" id="visit_date" name="visit_date" value="<?php echo htmlspecialchars($edit_visit_data['visit_date']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="visit_time">وقت الزيارة:</label>
                            <input type="time" id="visit_time" name="visit_time" value="<?php echo htmlspecialchars($edit_visit_data['visit_time']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="visit_reason">سبب الزيارة:</label>
                            <textarea id="visit_reason" name="visit_reason" rows="5" required><?php echo htmlspecialchars($edit_visit_data['visit_reason']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">حالة الزيارة:</label>
                            <select id="status" name="status" required>
                                <?php foreach ($status_map as $key => $value): ?>
                                    <option value="<?php echo htmlspecialchars($key); ?>" <?php echo ($key == $edit_visit_data['status']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($value['text']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" onclick="window.location.href='registered_dean_visits.php'">
                                <i class="fas fa-times"></i> إلغاء
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> حفظ التعديلات
                            </button>
                        </div>
                    </form>
                </div>

            <?php else: // Show visits table ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th width="50"> </th>
                                <th>اسم الزائر</th>
                                <th>نوع الزائر</th>
                                <th>تاريخ ووقت الزيارة</th>
                                <th>سبب الزيارة</th>
                                <th>الحالة</th>
                                <th>تاريخ التسجيل</th>
                                <th width="200">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody id="visitsTableBody">
                            <?php
                            if (!empty($visits)) {
                                $count = 1;
                                foreach ($visits as $row) {
                                    $visit_time_formatted = date('h:i A', strtotime($row['visit_time']));
                                    $visit_time_arabic = str_replace(['AM', 'PM'], ['ص', 'م'], $visit_time_formatted);
                                    $created_date = date('Y-m-d', strtotime($row['created_at']));
                                    
                                    $status_info = $status_map[$row['status']] ?? ['text' => $row['status'], 'class' => ''];
                            ?>
                            
                            <tr data-id="<?php echo htmlspecialchars($row['id']); ?>" 
                                data-visitor-type="<?php echo htmlspecialchars($row['visitor_type']); ?>" 
                                data-status="<?php echo htmlspecialchars($row['status']); ?>" 
                                data-date="<?php echo htmlspecialchars($row['visit_date']); ?>">
                                
                                <td data-label="#"><?php echo $count++; ?></td>
                                
                                <td data-label="اسم الزائر">
                                    <div class="visitor-info">
                                        <div class="visitor-name"><?php echo htmlspecialchars($row['visitor_name']); ?></div>
                                        
                                    </div>
                                </td>
                                
                                <td data-label="نوع الزائر"><?php echo htmlspecialchars($row['visitor_type']); ?></td>
                                
                                <td data-label="تاريخ ووقت الزيارة">
                                    <div class="visit-date"><?php echo htmlspecialchars($row['visit_date']); ?></div>
                                    <div class="visit-time"><?php echo $visit_time_arabic; ?></div>
                                </td>
                                
                                <td data-label="سبب الزيارة" class="visit-reason" title="<?php echo htmlspecialchars($row['visit_reason']); ?>">
                                    <?php echo htmlspecialchars($row['visit_reason']); ?>
                                </td>
                                
                                <td data-label="الحالة">
                                    <span class="status-badge <?php echo $status_info['class']; ?>"><?php echo $status_info['text']; ?></span>
                                </td>
                                
                                <td data-label="تاريخ التسجيل"><?php echo $created_date; ?></td>
                                
                                <td data-label="الإجراءات" class="actions-cell">
                                    <div class="action-buttons">
                                        <a href="?action=update_status&id=<?php echo $row['id']; ?>&status=Attended" class="btn-action attended" title="تمت الزيارة" >
                                            <i class="fas fa-check-circle"></i>
                                        </a>
                                        <a href="?action=edit_visit&id=<?php echo $row['id']; ?>" class="btn-action edit" title="تعديل الزيارة">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?action=update_status&id=<?php echo $row['id']; ?>&status=Cancelled" class="btn-action cancel" title="إلغاء الزيارة">
                                            <i class="fas fa-times-circle"></i>
                                        </a>
                                        <a href="?action=delete_visit&id=<?php echo $row['id']; ?>" class="btn-action delete" title="حذف الزيارة">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>

                            <?php
                                }
                            } else {
                                echo '<tr><td colspan="8" class="no-results" style="text-align:center;padding:30px;">لا توجد زيارات مسجلة لعرضها حاليًا.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
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

    <script>
        // دمج البحث مع الفلاتر
        function applyFilters() {
            const searchText = document.getElementById('searchInput').value.toUpperCase();
            const visitorType = document.getElementById('visitorTypeFilter').value;
            const status = document.getElementById('statusFilter').value;
            const fromDate = document.getElementById('fromDateFilter').value;
            const toDate = document.getElementById('toDateFilter').value;
            
            const tableBody = document.getElementById('visitsTableBody');
            const rows = tableBody.getElementsByTagName('tr');
            
            let hasVisibleRows = false;

            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                if (!row.hasAttribute('data-id')) {
                    row.style.display = 'none';
                    continue; 
                }

                const rowVisitorType = row.getAttribute('data-visitor-type');
                const rowStatus = row.getAttribute('data-status');
                const rowDate = row.getAttribute('data-date');
                const rowText = row.textContent.toUpperCase();
                
                const typeMatch = !visitorType || rowVisitorType === visitorType;
                const statusMatch = !status || rowStatus === status;
                const searchMatch = rowText.includes(searchText);

                let dateMatch = true;
                if (fromDate && toDate) {
                    dateMatch = rowDate >= fromDate && rowDate <= toDate;
                } else if (fromDate) {
                    dateMatch = rowDate >= fromDate;
                } else if (toDate) {
                    dateMatch = rowDate <= toDate;
                }
                
                if (typeMatch && statusMatch && dateMatch && searchMatch) {
                    row.style.display = '';
                    hasVisibleRows = true;
                } else {
                    row.style.display = 'none';
                }
            }

            const noResultsRow = document.querySelector('#visitsTableBody .no-results');
            if (noResultsRow) {
                if (hasVisibleRows) {
                    noResultsRow.style.display = 'none';
                } else {
                    noResultsRow.style.display = '';
                }
            } else if (!hasVisibleRows) {
                const newNoResultsRow = document.createElement('tr');
                newNoResultsRow.innerHTML = '<td colspan="8" class="no-results" style="text-align:center;padding:30px;">لا توجد زيارات مطابقة للمعايير المحددة.</td>';
                newNoResultsRow.classList.add('no-results');
                tableBody.appendChild(newNoResultsRow);
            }
        }
        
        function resetFilters() {
            document.getElementById('visitorTypeFilter').value = '';
            document.getElementById('statusFilter').value = '';
            document.getElementById('fromDateFilter').value = '';
            document.getElementById('toDateFilter').value = '';
            document.getElementById('searchInput').value = '';
            applyFilters();
        }

        // تطبيق الفلاتر عند أي تغيير
        document.querySelectorAll('.filters-container select, .filters-container input').forEach(element => {
            element.addEventListener('change', applyFilters);
        });
        
        document.getElementById('searchInput').addEventListener('keyup', applyFilters);

        // Expand/Collapse visit reason
        document.querySelectorAll('.visit-reason').forEach(item => {
            item.addEventListener('click', function() {
                this.classList.toggle('expanded');
            });
        });

        // Initial filter application on page load
        document.addEventListener('DOMContentLoaded', function() {
            applyFilters();
            
            // Set today's date as default in date filters
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('fromDateFilter').value = today;
        });

    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/javascript/script.js"></script>
</body>
</html>