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

    if (create_admin($username, $password)) {
        header('Location: admins.php');
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_admin'])) {
    $id = $_POST['admin_id'];
    $username = $_POST['edit_username'];
    $password = $_POST['edit_password'] ?: null;

    if (update_admin($id, $username, $password)) {
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
    <title>إدارة المشرفين - متتبع زيارات المختبر</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=2">
    <link rel="icon" type="image/png" href="../assets/images/smalllogotvtc.png">

    <style>
        /* نفس أنماط الـ CSS بدون تغيير */
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
                <li><a href="dashboard.php">لوحة التحكم</a></li>
                <li><a href="visits.php">تتبع الزيارات</a></li>
                <li><a href="admins.php">إدارة المشرفين</a></li>
                <li><a href="statistics.php" class="<?php echo ($page == 'statistics') ? 'active' : ''; ?>">الإحصائيات</a></li>
            </ul>
        </nav>

        <main>
            <h1>إدارة المشرفين</h1>

            <div class="card">
                <h2>إنشاء مشرف جديد</h2>
                <form method="POST">
                    <input type="text" name="new_username" placeholder="اسم المستخدم" required>
                    <input type="password" name="new_password" placeholder="كلمة المرور" required>
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
                                    <button class="btn-edit" 
                                        data-id="<?php echo $admin['id']; ?>"
                                        data-username="<?php echo htmlspecialchars($admin['username']); ?>">
                                        تعديل
                                    </button>
                                    <?php if ($admin['role'] === 'admin' && $admin['id'] != $_SESSION['user']['id']): ?>
                                        <a href="?delete=<?php echo $admin['id']; ?>" class="btn-delete" onclick="return confirm('هل أنت متأكد من رغبتك في حذف هذا المشرف؟')">حذف</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>

        <footer>
            <p>&copy; <?php echo date('Y'); ?> متتبع زيارات المختبر - الكلية التقنية TVTC</p>
        </footer>
    </div>

    <!-- نافذة التعديل -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>تعديل المشرف</h3>
            <form method="POST">
                <input type="hidden" name="admin_id" id="edit_admin_id">
                <input type="text" name="edit_username" id="edit_username" required>
                <input type="password" name="edit_password" placeholder="كلمة مرور جديدة (اختياري)">
                <button type="submit" name="update_admin">تحديث المشرف</button>
            </form>
        </div>
    </div>

    <script src="../assets/javascript/script.js"></script>
</body>
</html>
