<?php
require_once 'auth.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function get_active_visits() {
    global $pdo;

    $stmt = $pdo->query("SELECT v.*, u.username 
                         FROM visits v
                         JOIN users u ON v.checked_in_by = u.id
                         WHERE v.active = 1
                         ORDER BY v.check_in DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_visit_history($limit = 50) {
    global $pdo;

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
    $stmt = $pdo->prepare("INSERT INTO visits (student_name, academic_number, specialization, checked_in_by) VALUES (?, ?, ?, ?)");
    $stmt->execute([$student_name, $academic_number, $specialization, $admin_id]);
}


function check_out_student($visit_id) {
    global $pdo;

    $stmt = $pdo->prepare("UPDATE visits 
                          SET check_out = NOW(), active = 0
                          WHERE id = ?");
    return $stmt->execute([$visit_id]);
}

function create_admin($username, $password, $role = 'admin') {
    global $pdo;

    $stmt = $pdo->prepare("INSERT INTO users 
                          (username, password, role) 
                          VALUES (?, ?, ?)");
    return $stmt->execute([$username, $password, $role]);
}


function update_admin($id, $username, $password = null) {
    global $pdo;

    if ($password) {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ? WHERE id = ?");
        $result = $stmt->execute([$username, $password, $id]);
        error_log("SQL with password: " . ($result ? "Success" : "Failed"));
        return $result;
    } else {
        $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
        $result = $stmt->execute([$username, $id]);
        error_log("SQL without password: " . ($result ? "Success" : "Failed"));
        return $result;
    }
}



function delete_admin($id) {
    global $pdo;

    // Prevent deleting the current user
    if ($_SESSION['user']['id'] == $id) {
        return false;
    }

    try {
        // Start a transaction (optional but recommended)
        $pdo->beginTransaction();

        // 1. Delete all visits associated with this admin
        $stmt_visits = $pdo->prepare("DELETE FROM visits WHERE checked_in_by = ?");
        $stmt_visits->execute([$id]);

        // 2. Delete the admin/user
        $stmt_user = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $success = $stmt_user->execute([$id]);

        // Commit transaction (if used)
        $pdo->commit();

        return $success;
    } catch (Exception $e) {
        // Rollback transaction and handle error
        $pdo->rollBack();
        error_log("Delete admin failed: " . $e->getMessage());
        return false;
    }
}

function get_all_admins() {
    global $pdo;

    $stmt = $pdo->query("SELECT * FROM users ORDER BY role DESC, username");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_visit_count_by_period($period = 'today') {
    global $pdo;

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


?>
