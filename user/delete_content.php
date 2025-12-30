<?php
include '../config.php';
// ตรวจสอบ Login และสิทธิ์ User
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../index.php");
    exit();
}

// ตรวจสอบสถานะบัญชี
checkAccountStatus($conn, $_SESSION['user_id']);

$user_id = $_SESSION['user_id'];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $content_id = (int)$_GET['id'];
    
    // ดึงรายการอุปกรณ์ที่ User นี้มีสิทธิ์
    $devices_sql = "
        SELECT d.device_id 
        FROM devices d
        JOIN user_permissions up ON d.device_id = up.device_id
        WHERE up.user_id = ?
    ";
    $devices_stmt = $conn->prepare($devices_sql);
    $devices_stmt->bind_param("i", $user_id);
    $devices_stmt->execute();
    $devices_result = $devices_stmt->get_result();
    $allowed_device_ids = [];
    while($row = $devices_result->fetch_assoc()) {
        $allowed_device_ids[] = $row['device_id'];
    }
    $devices_stmt->close();

    if (empty($allowed_device_ids)) {
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'คุณไม่มีสิทธิ์ในการลบไฟล์'];
        header("Location: my_content.php");
        exit();
    }

    // ตรวจสอบสิทธิ์: Content ต้องเป็นของ User และอยู่ในอุปกรณ์ที่ User มีสิทธิ์
    $placeholders = implode(',', array_fill(0, count($allowed_device_ids), '?'));
    $check_sql = "
        SELECT c.content_id, c.filepath 
        FROM contents c
        JOIN device_content dc ON c.content_id = dc.content_id
        WHERE c.content_id = ? AND c.upload_by = ? AND dc.device_id IN ($placeholders)
        LIMIT 1
    ";
    $check_stmt = $conn->prepare($check_sql);
    $params = array_merge([$content_id, $user_id], $allowed_device_ids);
    $types = str_repeat('i', count($params));
    $check_stmt->bind_param($types, ...$params);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $file_to_delete = $check_result->fetch_assoc();
    $check_stmt->close();

    if ($file_to_delete) {
        $filepath = '../assets/uploads/' . $file_to_delete['filepath'];
        
        $conn->begin_transaction();

        try {
            // 1. ลบจาก device_content
            $sql_dc = "DELETE FROM device_content WHERE content_id = ?";
            $stmt_dc = $conn->prepare($sql_dc);
            $stmt_dc->bind_param("i", $content_id);
            $stmt_dc->execute();
            $stmt_dc->close();

            // 2. ลบจาก contents
            $sql_c = "DELETE FROM contents WHERE content_id = ? AND upload_by = ?";
            $stmt_c = $conn->prepare($sql_c);
            $stmt_c->bind_param("ii", $content_id, $user_id);
            $stmt_c->execute();
            $stmt_c->close();

            // 3. ลบไฟล์จริงออกจาก Server
            if (file_exists($filepath) && !is_dir($filepath)) {
                unlink($filepath);
            }

            $conn->commit();
            $_SESSION['message'] = ['type' => 'success', 'text' => 'ลบ Content เรียบร้อยแล้ว'];

        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['message'] = ['type' => 'danger', 'text' => 'เกิดข้อผิดพลาดในการลบ: ' . $e->getMessage()];
        }

    } else {
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'ไม่พบ Content นี้ หรือคุณไม่มีสิทธิ์'];
    }

} else {
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'ID Content ไม่ถูกต้อง'];
}

header("Location: my_content.php");
exit();

$conn->close();
?>

