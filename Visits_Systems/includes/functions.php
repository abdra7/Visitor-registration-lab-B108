<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * دالة للاتصال بقاعدة البيانات (
 * @return PDO|null كائن PDO للاتصال بقاعدة البيانات، أو null في حالة الفشل.
 */
function db_connect() {
    $host = 'localhost'; 
    $db   = 'b108'; 
    $user = 'root';     
    $pass = '';     
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (\PDOException $e) {
        error_log("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
        return null;
    }
}


function get_active_visits() {
    global $pdo; 
    if (!isset($pdo)) $pdo = db_connect(); 
    if (!$pdo) return [];

    $stmt = $pdo->query("SELECT v.*, u.username 
                         FROM visits v
                         JOIN users u ON v.checked_in_by = u.id
                         WHERE v.active = 1
                         ORDER BY v.check_in DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_visit_history($limit = 50) {
    global $pdo;
    if (!isset($pdo)) $pdo = db_connect();
    if (!$pdo) return [];

    $stmt = $pdo->prepare("SELECT v.*, u.username 
                           FROM visits v
                           JOIN users u ON v.checked_in_by = u.id
                           WHERE v.active = 0
                           ORDER BY v.check_out DESC
                           LIMIT :limit");
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function check_in_student($student_name, $academic_number, $specialization, $admin_id) {
    global $pdo;
    if (!isset($pdo)) $pdo = db_connect();
    if (!$pdo) return false;

    $stmt = $pdo->prepare("INSERT INTO visits (student_name, academic_number, specialization, checked_in_by) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$student_name, $academic_number, $specialization, $admin_id]);
}

function check_out_student($visit_id) {
    global $pdo;
    if (!isset($pdo)) $pdo = db_connect();
    if (!$pdo) return false;

    $stmt = $pdo->prepare("UPDATE visits 
                          SET check_out = NOW(), active = 0
                          WHERE id = ?");
    return $stmt->execute([$visit_id]);
}

function create_admin($username, $password, $role = 'admin') {
    global $pdo;
    if (!isset($pdo)) $pdo = db_connect();
    if (!$pdo) return false;

    $stmt = $pdo->prepare("INSERT INTO users 
                          (username, password, role) 
                          VALUES (?, ?, ?)");
    return $stmt->execute([$username, $password, $role]);
}

function update_admin($id, $username, $role, $password = null) {
    global $pdo;
    if (!isset($pdo)) $pdo = db_connect();
    if (!$pdo) return false;

    if ($password) {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, role = ?, password = ? WHERE id = ?");
        $result = $stmt->execute([$username, $role, $password, $id]);
        return $result;
    } else {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
        $result = $stmt->execute([$username, $role, $id]);
        return $result;
    }
}

function delete_admin($id) {
    global $pdo;
    if (!isset($pdo)) $pdo = db_connect();
    if (!$pdo) return false;

    $id = intval($id);
    if ($id <= 0 || !isset($_SESSION['user']) || $_SESSION['user']['id'] == $id) {
        return false;
    }

    try {
        $pdo->beginTransaction();

        $stmt_visits = $pdo->prepare("UPDATE visits SET checked_in_by = NULL WHERE checked_in_by = ?");
        $stmt_visits->execute([$id]);

        $stmt_user = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $success = $stmt_user->execute([$id]);

        $pdo->commit();
        return $success;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Delete admin failed: " . $e->getMessage());
        return false;
    }
}

function get_all_admins() {
    global $pdo;
    if (!isset($pdo)) $pdo = db_connect();
    if (!$pdo) return [];

    $stmt = $pdo->query("SELECT * FROM users ORDER BY role DESC, username");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


/**
 * دالة لجلب إجمالي عدد زيارات المختبر
 * @return int إجمالي عدد زيارات المختبر
 */
function get_total_lab_visits() {
    $pdo = db_connect();
    if (!$pdo) return 0;

    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM visits");
        return $stmt->fetchColumn();
    } catch (\PDOException $e) {
        error_log("خطأ في جلب إجمالي زيارات المختبر: " . $e->getMessage());
        return 0;
    }
}

/**
 * دالة لجلب عدد زيارات المختبر النشطة حالياً (لم يتم تسجيل الخروج منها)
 * @return int عدد الزيارات النشطة
 */
function get_active_lab_visits() {
    $pdo = db_connect();
    if (!$pdo) return 0;

    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM visits WHERE active = 1");
        return $stmt->fetchColumn();
    } catch (\PDOException $e) {
        error_log("خطأ في جلب زيارات المختبر النشطة: " . $e->getMessage());
        return 0;
    }
}

/**
 * دالة لجلب إجمالي عدد زيارات العميد
 * @return int إجمالي عدد زيارات العميد
 */
function get_total_dean_visits() {
    $pdo = db_connect();
    if (!$pdo) return 0;

    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM dean_college_visits");
        return $stmt->fetchColumn();
    } catch (\PDOException $e) {
        error_log("خطأ في جلب إجمالي زيارات العميد: " . $e->getMessage());
        return 0;
    }
}

/**
 * دالة لجلب عدد زيارات العميد حسب الحالة
 * @param string $status الحالة المطلوبة ('scheduled', 'attended', 'cancelled')
 * @return int عدد الزيارات للحالة المحددة
 */
function get_dean_visits_by_status($status) {
    $pdo = db_connect();
    if (!$pdo) return 0;

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM dean_college_visits WHERE status = ?");
        $stmt->execute([$status]);
        return $stmt->fetchColumn();
    } catch (\PDOException $e) {
        error_log("خطأ في جلب زيارات العميد حسب الحالة: " . $e->getMessage());
        return 0;
    }
}

/**
 * دالة لجلب إجمالي عدد المستخدمين
 * @return int إجمالي عدد المستخدمين
 */
function get_total_users() {
    $pdo = db_connect();
    if (!$pdo) return 0;

    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        return $stmt->fetchColumn();
    } catch (\PDOException $e) {
        error_log("خطأ في جلب إجمالي المستخدمين: " . $e->getMessage());
        return 0;
    }
}

/**
 * دالة لجلب إجمالي عدد أنواع الزوار
 * @return int إجمالي عدد أنواع الزوار
 */
function get_total_visitor_types() {
    $pdo = db_connect();
    if (!$pdo) return 0;

    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM dean_college_visitor_types");
        return $stmt->fetchColumn();
    } catch (\PDOException $e) {
        error_log("خطأ في جلب إجمالي أنواع الزوار: " . $e->getMessage());
        return 0;
    }
}

/**
 * دالة لجلب جميع التخصصات الفريدة من جدول الزيارات
 * @return array قائمة بالتخصصات
 */
function get_all_specializations() {
    $pdo = db_connect();
    if (!$pdo) return [];

    try {
        $stmt = $pdo->query("SELECT DISTINCT specialization FROM visits WHERE specialization IS NOT NULL AND specialization != '' ORDER BY specialization ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (\PDOException $e) {
        error_log("خطأ في جلب جميع التخصصات: " . $e->getMessage());
        return [];
    }
}

/**
 * دالة لجلب جميع أنواع الزوار من جدول dean_college_visitor_types
 * @return array قائمة بأنواع الزوار (id, type_name_ar)
 */
function get_all_visitor_types() {
    $pdo = db_connect();
    if (!$pdo) return [];

    try {
        $stmt = $pdo->query("SELECT id, type_name_ar FROM dean_college_visitor_types ORDER BY type_name_ar ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
        error_log("خطأ في جلب جميع أنواع الزوار: " . $e->getMessage());
        return [];
    }
}

/**
 * دالة لجلب زيارات المختبر بناءً على الفلاتر
 * @param array $filters مصفوفة تحتوي على معايير البحث (search_term, specialization, start_date, end_date)
 * @return array قائمة بزيارات المختبر المطابقة
 */
function get_filtered_lab_visits($filters) {
    $pdo = db_connect();
    if (!$pdo) return [];

    $sql = "SELECT v.*, u.username AS checked_in_by_username 
            FROM visits v
            LEFT JOIN users u ON v.checked_in_by = u.id
            WHERE 1";
    $params = [];

    if (!empty($filters['search_term'])) {
        $searchTerm = '%' . $filters['search_term'] . '%';
        $sql .= " AND (v.student_name LIKE ? OR v.academic_number LIKE ?)";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    if (!empty($filters['specialization'])) {
        $sql .= " AND v.specialization = ?";
        $params[] = $filters['specialization'];
    }
    if (!empty($filters['start_date'])) {
        $sql .= " AND DATE(v.check_in) >= ?";
        $params[] = $filters['start_date'];
    }
    if (!empty($filters['end_date'])) {
        $sql .= " AND DATE(v.check_in) <= ?";
        $params[] = $filters['end_date'];
    }

    $sql .= " ORDER BY v.check_in DESC";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
        error_log("خطأ في جلب زيارات المختبر المفلترة: " . $e->getMessage());
        return [];
    }
}

/**
 * دالة لجلب زيارات العميد بناءً على الفلاتر
 * @param array $filters مصفوفة تحتوي على معايير البحث (search_term, visitor_type_id, status, start_date, end_date)
 * @return array قائمة بزيارات العميد المطابقة
 */
function get_filtered_dean_visits($filters) {
    $pdo = db_connect();
    if (!$pdo) return [];

    $sql = "SELECT dcv.*, dcvt.type_name_ar,
                   CASE dcv.status
                       WHEN 'scheduled' THEN 'مجدولة'
                       WHEN 'attended' THEN 'مكتملة'
                       WHEN 'cancelled' THEN 'ملغاة'
                       ELSE dcv.status
                   END AS status_ar
            FROM dean_college_visits dcv
            JOIN dean_college_visitor_types dcvt ON dcv.visitor_type_id = dcvt.id
            WHERE 1";
    $params = [];

    if (!empty($filters['search_term'])) {
        $searchTerm = '%' . $filters['search_term'] . '%';
        $sql .= " AND dcv.visitor_name LIKE ?";
        $params[] = $searchTerm;
    }
    if (!empty($filters['visitor_type_id'])) {
        $sql .= " AND dcv.visitor_type_id = ?";
        $params[] = $filters['visitor_type_id'];
    }
    if (!empty($filters['status'])) {
        $sql .= " AND dcv.status = ?";
        $params[] = $filters['status'];
    }
    if (!empty($filters['start_date'])) {
        $sql .= " AND dcv.visit_date >= ?";
        $params[] = $filters['start_date'];
    }
    if (!empty($filters['end_date'])) {
        $sql .= " AND dcv.visit_date <= ?";
        $params[] = $filters['end_date'];
    }

    $sql .= " ORDER BY dcv.visit_date DESC, dcv.visit_time DESC";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
        error_log("خطأ في جلب زيارات العميد المفلترة: " . $e->getMessage());
        return [];
    }
}


if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return isset($_SESSION['user']['id']);
    }
}


/*
function get_visit_count_by_period($period = 'today') {
    global $pdo;
    if (!isset($pdo)) $pdo = db_connect();
    if (!$pdo) return 0;

    $query = "SELECT COUNT(*) AS count FROM visits WHERE ";

    switch ($period) {
        case 'today':
            $query .= "DATE(check_in) = CURDATE()";
            break;
        case 'this_month':
            $query .= "YEAR(check_in) = YEAR(CURDATE()) AND MONTH(check_in) = MONTH(CURDATE())";
            break;
        case 'this_year':
            $query .= "YEAR(check_in) = YEAR(CURDATE())";
            break;
        default:
            $query .= "1"; // All visits
            break;
    }

    $stmt = $pdo->query($query);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return $result['count'];
}
*/
?>
