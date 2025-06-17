<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit();
}

// AJAX: جلب اسم الطالب حسب الرقم الأكاديمي
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['academic_id']) && !isset($_POST['check_in'])) {
    $academic_id = $_POST['academic_id'];

    function get_student_name_by_academic_number($academic_number) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT student_name FROM visits WHERE academic_number = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$academic_number]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['student_name'] : null;
    }

    $name = get_student_name_by_academic_number($academic_id);
    echo $name ? $name : "";
    exit();
}

$active_visits = get_active_visits();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_in'])) {
    $student_name = $_POST['student_name'];
    $academic_number = $_POST['academic_number'];
    $specialization = $_POST['specialization'];
    $admin_id = $_SESSION['user']['id'];

    check_in_student($student_name, $academic_number, $specialization, $admin_id);
    header('Location: visits.php');
    exit();
}

if (isset($_GET['check_out'])) {
    $visit_id = $_GET['check_out'];
    check_out_student($visit_id);
    header('Location: visits.php');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>تتبع الزيارات - متتبع زيارات المختبر</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=2">
    <link rel="icon" type="image/png" href="../assets/images/smalllogotvtc.png">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    let debounceTimer;

    function debouncedFetchName() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(fetchStudentName, 5000); // 5 ثواني
    }

    function fetchStudentName() {
        var academicId = document.getElementById("academic_number").value.trim();
        if (academicId !== "") {
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "visits.php", true);
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhr.onload = function () {
                document.getElementById("student_name").value = this.responseText;
            };
            xhr.send("academic_id=" + encodeURIComponent(academicId));
        }
    }

    function confirmCheckout(visitId, studentName) {
        Swal.fire({
            title: 'تأكيد الخروج',
            html: `هل أنت متأكد أنك تريد تسجيل خروج الطالب <strong>${studentName}</strong>?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'نعم، سجل الخروج',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '?check_out=' + visitId;
            }
        });
    }
    </script>
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
            <li><a href="visits.php" class="active">تتبع الزيارات</a></li>
            <?php if (is_superadmin()): ?>
                <li><a href="admins.php">إدارة المشرفين</a></li>
                <li><a href="statistics.php">الإحصائيات</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <main>
        <h1>تتبع الزيارات</h1>

        <div class="card">
            <h2>تسجيل زيارة جديدة</h2>
            <form method="POST">
                <input type="text" id="academic_number" name="academic_number" placeholder="الرقم الأكاديمي"  required>
                <input type="text" id="student_name" name="student_name" placeholder="اسم الطالب" required>

                <select id="specialization" name="specialization" required>
                    <option value="">التخصص</option>
                    <option value="تقنيات الاعمال المكتبية">تقنيات الاعمال المكتبية</option>
                    <option value="تقنيات محاسبية">تقنيات محاسبية</option>
                    <option value="تقنية التسويق والابتكار">تقنية التسويق والابتكار</option>
                    <option value="السفر والسياحة">السفر والسياحة</option>
                    <option value="الفندقة">الفندقة</option>
                    <option value="خدمات الحج والعمرة">خدمات الحج والعمرة</option>
                    <option value="تقنية السلامة والصحة المهنية">تقنية السلامة والصحة المهنية</option>
                </select>

                <button type="submit" name="check_in">تسجيل الدخول</button>
            </form>
        </div>

        <div class="card">
            <h2>الزيارات النشطة</h2>
            <?php if (!empty($active_visits)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>الطالب</th>
                            <th>الرقم الأكاديمي</th>
                            <th>التخصص</th>
                            <th>وقت الدخول</th>
                            <th>تم التسجيل بواسطة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($active_visits as $visit): 
                            $checkInTime = new DateTime($visit['check_in']);
                            $now = new DateTime();
                            $duration = $checkInTime->diff($now);
                        ?>
                            <tr>
                                <td><?php echo $visit['student_name']; ?></td>
                                <td><?php echo $visit['academic_number']; ?></td>
                                <td><?php echo $visit['specialization']; ?></td>
                                <td><?php echo date('H:i', strtotime($visit['check_in'])); ?></td>
                                <td><?php echo $visit['username']; ?></td>
                                <td>
                                    <button class="btn-checkout" onclick="confirmCheckout(<?php echo $visit['id']; ?>, '<?php echo htmlspecialchars($visit['student_name'], ENT_QUOTES); ?>')">
                                        تسجيل الخروج
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>لا توجد زيارات نشطة حالياً.</p>
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
