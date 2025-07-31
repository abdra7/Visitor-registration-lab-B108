<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_superadmin()) {
    header('Location: dashboard.php');
    exit();
}

$admins = get_all_admins();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_admin'])) {
    $username = $_POST['new_username'];
    $password = $_POST['new_password'];
    $role = $_POST['new_role']; // Get the new role from the form

    if (create_admin($username, $password, $role)) { // Pass the role to the function
        header('Location: admins.php');
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_admin'])) {
    $id = $_POST['admin_id'];
    $username = $_POST['edit_username'];
    $password = $_POST['edit_password'] ?: null;
    $role = $_POST['edit_role']; // Get the edited role from the form

    if (update_admin($id, $username, $role, $password)) { // Pass the role to the function
        header('Location: admins.php');
        exit();
    }
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    if (delete_admin($id)) {
        header('Location: admins.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>إدارة المشرفين</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="../assets/images/smalllogotvtc.png">

    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fff;
            padding: 20px;
            width: 400px;
            border-radius: 5px;
            position: relative;
        }

        .modal .close {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
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
            <h1>إدارة المشرفين</h1>

            <div class="card">
                <h2>إنشاء مشرف جديد</h2>
                <form method="POST">
                    <input type="text" name="new_username" placeholder="اسم المستخدم" required>
                    <input type="password" name="new_password" placeholder="كلمة المرور" required>
                    <select name="new_role" required>
                        <option value="admin">مشرف عادي</option>
                        <option value="secretary">سكرتير</option>
                        <option value="superadmin">مدير النظام</option>
                    </select>
                    <button type="submit" name="create_admin">إنشاء مشرف</button>
                </form>
            </div>

            <div class="card">
                <h2>المشرفون الحاليون</h2>
                <table>
                    <thead>
                        <tr>
                            <th>الرقم</th>
                            <th>اسم المستخدم</th>
                            <th>الدور</th>
                            <th>تاريخ الإنشاء</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $admin): ?>
                            <tr>
                                <td><?php echo $admin['id']; ?></td>
                                <td><?php echo $admin['username']; ?></td>
                                <td><?php echo ucfirst($admin['role']); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($admin['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button type="button" class="btn-action edit" title="تعديل المشرف"
                                            data-id="<?php echo $admin['id']; ?>"
                                            data-username="<?php echo htmlspecialchars($admin['username']); ?>"
                                            data-role="<?php echo htmlspecialchars($admin['role']); ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($admin['role'] === 'admin' || $admin['role'] === 'secretary' && $admin['id'] != $_SESSION['user']['id']): ?>
                                            <a href="?delete=<?php echo $admin['id']; ?>" class="btn-action delete" title="حذف المشرف">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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

    <div id="editModal" class="modal" data-modal="edit">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>تعديل المشرف</h3>
            <form method="POST" id="editAdminForm">
                <input type="hidden" name="admin_id" id="edit_admin_id">
                <input type="text" name="edit_username" id="edit_username" required>
                <input type="password" name="edit_password" placeholder="كلمة مرور جديدة (اختياري)">
                <select name="edit_role" id="edit_role" required>
                    <option value="admin">مشرف عادي</option>
                    <option value="secretary">سكرتير</option>
                    <option value="superadmin">مدير النظام</option>
                </select>
                <button type="submit" name="update_admin">تحديث المشرف</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/javascript/script.js"></script>
    <script>
function initializeAdminsPage() {
    const modal = document.getElementById('editModal');
    if (!modal) return;

    let isModalOpen = false;

    function openModal() {
        modal.style.display = 'flex';
        isModalOpen = true;
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.style.display = 'none';
        isModalOpen = false;
        document.body.style.overflow = '';
    }

    // Setup edit buttons
    document.querySelectorAll('.btn-action.edit').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation(); // Prevent bubbling to modal

            // Get data from button attributes
            const id = this.getAttribute('data-id');
            const username = this.getAttribute('data-username');
            const role = this.getAttribute('data-role');

            document.getElementById('edit_admin_id').value = id;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_role').value = role;

            openModal();
        });
    });

    // Prevent modal closure when clicking inside modal content
    modal.querySelector('.modal-content').addEventListener('click', function(e) {
        e.stopPropagation();
    });

    // Close button handler
    const closeBtn = modal.querySelector('.close');
    if (closeBtn) {
        closeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            closeModal();
        });
    }

    // Click on backdrop closes modal ONLY if clicking outside modal-content
    modal.addEventListener('click', function(e) {
        if (e.target === modal && isModalOpen) {
            closeModal();
        }
    });

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
}
    </script>
</body>
</html>