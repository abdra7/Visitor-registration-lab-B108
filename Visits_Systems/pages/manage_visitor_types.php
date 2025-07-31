<?php
require_once '../includes/auth.php';
require_once '../config/db.php'; // Include database connection

// التحقق من صلاحية الوصول: هذه الصفحة للسوبر أدمن فقط
if (!is_logged_in() || !is_superadmin()) {
    redirect('dashboard.php'); // أو توجيه لصفحة غير مصرح بها
}

$page = 'manage_visitor_types'; // لتمييز الصفحة النشطة في شريط التنقل
$message = '';
$message_type = ''; 

// ----------------------------------------------------
// 1. معالجة إضافة نوع جديد
// ----------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_type'])) {
    $new_type_name = trim($_POST['new_type_name_ar']);

    if (!empty($new_type_name)) {
        $sql_insert = "INSERT INTO `dean_college_visitor_types` (`type_name_ar`) VALUES (:type_name_ar)";
        try {
            $stmt = $pdo->prepare($sql_insert);
            $stmt->bindParam(':type_name_ar', $new_type_name);
            if ($stmt->execute()) {
                $message = "تمت إضافة الصفة بنجاح!";
                $message_type = "success";
            } else {
                $errorInfo = $stmt->errorInfo();
                if ($errorInfo[1] == 1062) { // MySQL error code for duplicate entry (for UNIQUE constraint)
                    $message = "هذه الصفة موجودة بالفعل.";
                    $message_type = "warning";
                } else {
                    $message = "حدث خطأ أثناء إضافة الصفة: " . $errorInfo[2];
                    $message_type = "error";
                }
            }
        } catch (PDOException $e) {
            $message = "خطأ في إضافة الصفة: " . $e->getMessage();
            $message_type = "error";
            error_log("PDO Error in manage_visitor_types.php (add): " . $e->getMessage());
        }
    } else {
        $message = "يرجى إدخال اسم الصفة.";
        $message_type = "warning";
    }
}

// ----------------------------------------------------
// 2. معالجة تعديل نوع موجود
// ----------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_type'])) {
    $type_id = (int)$_POST['type_id'];
    $updated_type_name = trim($_POST['updated_type_name_ar']);

    if (!empty($updated_type_name) && $type_id > 0) {
        $sql_update = "UPDATE `dean_college_visitor_types` SET `type_name_ar` = :type_name_ar WHERE `id` = :id";
        try {
            $stmt = $pdo->prepare($sql_update);
            $stmt->bindParam(':type_name_ar', $updated_type_name);
            $stmt->bindParam(':id', $type_id, PDO::PARAM_INT);
            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    $message = "تم تحديث الصفة بنجاح!";
                    $message_type = "success";
                } else {
                    $message = "لم يتم إجراء أي تعديل على الصفة (ربما نفس الاسم أو المعرف غير موجود).";
                    $message_type = "info";
                }
            } else {
                $errorInfo = $stmt->errorInfo();
                 if ($errorInfo[1] == 1062) { // MySQL error code for duplicate entry
                    $message = "هذه الصفة موجودة بالفعل.";
                    $message_type = "warning";
                } else {
                    $message = "حدث خطأ أثناء تحديث الصفة: " . $errorInfo[2];
                    $message_type = "error";
                }
            }
        } catch (PDOException $e) {
            $message = "خطأ في تحديث الصفة: " . $e->getMessage();
            $message_type = "error";
            error_log("PDO Error in manage_visitor_types.php (update): " . $e->getMessage());
        }
    } else {
        $message = "بيانات التعديل غير كاملة.";
        $message_type = "warning";
    }
}

// ----------------------------------------------------
// 3. معالجة حذف نوع موجود
// ----------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_type'])) {
    $type_id = (int)$_POST['type_id'];

    if ($type_id > 0) {
       

        $sql_delete = "DELETE FROM `dean_college_visitor_types` WHERE `id` = :id";
        try {
            $stmt = $pdo->prepare($sql_delete);
            $stmt->bindParam(':id', $type_id, PDO::PARAM_INT);
            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    $message = "تم حذف الصفة بنجاح!";
                    $message_type = "success";
                } else {
                    $message = "الصفة المطلوبة للحذف غير موجودة.";
                    $message_type = "warning";
                }
            } else {
                $errorInfo = $stmt->errorInfo();
                // 23000 هو SQLSTATE لخطأ قيود التكامل (مثل Foreign Key constraint violation)
                if ($errorInfo[0] === '23000') { 
                    $message = "لا يمكن حذف هذه الصفة لوجود زيارات مرتبطة بها.";
                    $message_type = "error";
                } else {
                    $message = "حدث خطأ أثناء حذف الصفة: " . $errorInfo[2];
                    $message_type = "error";
                }
            }
        } catch (PDOException $e) {
            $message = "خطأ في حذف الصفة: " . $e->getMessage();
            $message_type = "error";
            error_log("PDO Error in manage_visitor_types.php (delete): " . $e->getMessage());
        }
    } else {
        $message = "معرف الصفة غير صالح للحذف.";
        $message_type = "warning";
    }
}

// ----------------------------------------------------
// 4. جلب جميع أنواع الزوار لعرضها في الجدول
// ----------------------------------------------------
$visitor_types = [];
try {
    $stmt_types = $pdo->query("SELECT id, type_name_ar FROM `dean_college_visitor_types` ORDER BY type_name_ar ASC");
    $visitor_types = $stmt_types->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "خطأ في جلب أنواع الزوار: " . $e->getMessage();
    $message_type = "error";
    error_log("PDO Error in manage_visitor_types.php (fetch all types): " . $e->getMessage());
}

// PDO connection is typically not closed explicitly.
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../assets/images/smalllogotvtc.png">
    <title>إدارة صفة الزوار</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* Shared message and button styles already in style.css or previous pages.
           Adding specific styles here if they are unique to this page. */
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
            font-size: 1.8rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .message.warning { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .message.info { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }

        .form-inline {
            display: flex;
            gap: 15px;
            align-items: flex-end; 
            margin-bottom: 20px;
            flex-wrap: wrap; 
        }
        .form-inline .form-group {
            flex: 1; 
            min-width: 200px; 
            margin-bottom: 0; 
        }
        .form-inline button {
            flex-shrink: 0; 
            width: auto;
            padding: 12px 25px; 
            height: 48px; 
            margin-top: 28px; 
        }

        .form-inline .btn-primary { 
            background-color: var(--color-primary);
            color: var(--color-white);
            border: none;
            border-radius: var(--border-radius-md);
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .form-inline .btn-primary:hover {
            background-color: var(--color-primary-dark);
        }

        .action-buttons {
    display: flex;
    gap: 8px;
    justify-content: center;
}

.btn-edit-table, 
.btn-delete-table {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 6px;
    transition: all 0.2s ease;
    text-decoration: none;
    border: none;
    cursor: pointer;
    font-size: 16px;
}

.btn-edit-table:hover, 
.btn-delete-table:hover {
    transform: scale(1.1);
}

/* Edit button specific styles */
.btn-edit-table {
    background-color: #fff3cd;
    color: #e57300;
}

.btn-edit-table:hover {
    background-color: #e57300;
    color: #ffffff;
    box-shadow: 0 4px 8px rgba(229, 115, 0, 0.2);
}

/* Delete button specific styles */
.btn-delete-table {
    background-color: #f8d7da;
    color: #dc3545;
}

.btn-delete-table:hover {
    background-color: #dc3545;
    color: #ffffff;
    box-shadow: 0 4px 8px rgba(220, 53, 69, 0.2);
}
        .visits-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 1.5rem;
            overflow: hidden;
            border-radius: var(--border-radius-md);
            box-shadow: var(--shadow-sm);
            direction: rtl;
        }
        .visits-table thead {
            background-color: var(--color-primary);
            color: var(--color-white);
        }
        .visits-table th,
        .visits-table td {
            padding: 1.5rem;
            text-align: right;
            vertical-align: middle;
            white-space: nowrap;
        }
        .visits-table th {
            font-weight: 600;
            position: relative;
        }
        .visits-table tbody tr {
            background-color: var(--color-white);
            transition: background-color var(--transition-fast);
        }
        .visits-table tbody tr:hover {
            background-color: rgba(0, 102, 102, 0.05);
        }
        .visits-table tbody tr:nth-child(even) {
            background-color: rgba(0, 0, 0, 0.02);
        }
        .visits-table tbody tr:nth-child(even):hover {
            background-color: rgba(0, 102, 102, 0.05);
        }
        .visits-table td {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        .visits-table tbody tr:last-child td {
            border-bottom: none;
        }
        .visits-table thead tr:first-child th:first-child { border-top-right-radius: var(--border-radius-md); }
        .visits-table thead tr:first-child th:last-child { border-top-left-radius: var(--border-radius-md); }
        .visits-table tbody tr:last-child td:first-child { border-bottom-right-radius: var(--border-radius-md); }
        .visits-table tbody tr:last-child td:last-child { border-bottom-left-radius: var(--border-radius-md); }
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
            <h1>إدارة صفة الزوار</h1>

            <?php if (!empty($message)): ?>
                <div class="message <?php echo htmlspecialchars($message_type); ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <h2>إضافة صفة زائر جديد</h2>
                <form method="POST" class="form-inline">
                    <div class="form-group" style="flex-grow: 1;">
                        <label for="new_type_name_ar">اسم الصفة:</label>
                        <input type="text" id="new_type_name_ar" name="new_type_name_ar" placeholder="مثال: عضو هيئة تدريس" required>
                    </div>
                    <button type="submit" name="add_type" class="btn-primary">إضافة صفة</button>
                </form>
            </div>

            <div class="card">
                <h2>الصفاة الحالية</h2>
                <?php if (!empty($visitor_types)): ?>
                <div class="table-responsive">
                    <table class="visits-table"> <thead>
                            <tr>
                                <th>ID</th>
                                <th>اسم الصفة</th>
                                <th>العمليات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($visitor_types as $type): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($type['id']); ?></td>
                                <td><?php echo htmlspecialchars($type['type_name_ar']); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-edit-table" onclick="openEditModal(<?php echo htmlspecialchars($type['id']); ?>, '<?php echo htmlspecialchars(addslashes($type['type_name_ar'])); ?>')"><i class="fas fa-edit"></i></button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذه الصفة؟ لا يمكن التراجع عن هذا الإجراء وقد يؤثر على الزيارات المرتبطة بها.');">
                                            <input type="hidden" name="type_id" value="<?php echo htmlspecialchars($type['id']); ?>">
                                            <button type="submit" name="delete_type" class="btn-delete-table"><i class="fas fa-trash-alt"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <p class="message info">لا توجد أنواع زوار مسجلة حالياً.</p>
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

    <div id="editModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close-button" onclick="closeEditModal()">&times;</span>
            <h3>تعديل الصفة</h3>
            <form method="POST">
                <input type="hidden" name="type_id" id="edit_type_id">
                <div class="form-group">
                    <label for="updated_type_name_ar">اسم الصفة الجديد:</label>
                    <input type="text" id="updated_type_name_ar" name="updated_type_name_ar" required>
                </div>
                <button type="submit" name="update_type" class="btn-primary">حفظ التعديلات</button>
            </form>
        </div>
    </div>

    <script>
        // JavaScript for Modal functionality
        function openEditModal(id, name) {
            document.getElementById('edit_type_id').value = id;
            document.getElementById('updated_type_name_ar').value = name;
            document.getElementById('editModal').style.display = 'flex'; // Use flex to center
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Close modal if clicked outside (optional)
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        // Delete button functionality
    document.querySelectorAll('.btn-action.delete').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const href = this.getAttribute('href');
            
            Swal.fire({
                title: 'تأكيد الحذف',
                text: 'هل أنت متأكد من رغبتك في حذف هذا المشرف؟',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'نعم، قم بالحذف',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = href;
                }
            });
        });
    });

    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/javascript/script.js"></script>
</body>
</html>