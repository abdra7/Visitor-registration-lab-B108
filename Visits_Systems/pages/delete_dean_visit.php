<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$message = '';
$message_type = '';

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = (int)$_GET['id'];

    $sql_delete = "DELETE FROM `dean_college_visits` WHERE `id` = :id";

    try {
        $stmt = $pdo->prepare($sql_delete);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) { // Check if a row was actually deleted
                $message = "تم حذف الزيارة بنجاح!";
                $message_type = "success";
            } else {
                $message = "الزيارة المطلوبة للحذف غير موجودة.";
                $message_type = "warning";
            }
        } else {
            $errorInfo = $stmt->errorInfo();
            $message = "حدث خطأ أثناء حذف الزيارة: " . $errorInfo[2];
            $message_type = "error";
        }
    } catch (PDOException $e) {
        $message = "خطأ في تحضير أو تنفيذ الحذف: " . $e->getMessage();
        $message_type = "error";
        error_log("PDO Error in delete_dean_visit.php: " . $e->getMessage());
    }
} else {
    $message = "لم يتم تحديد معرف الزيارة للحذف.";
    $message_type = "error";
}

// Redirect back to the registered visits page with a message
header("Location: registered_dean_visits.php?message=" . urlencode($message) . "&type=" . urlencode($message_type));
exit();
?>