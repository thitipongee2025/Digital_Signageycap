<?php
include '../config.php';
checkAdminLogin();

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $content_id = (int)$_GET['id'];
    
    // 1. ดึงข้อมูลไฟล์เพื่อลบออกจาก Server
    $sql_file = "SELECT filepath FROM contents WHERE content_id = ?";
    $stmt_file = $conn->prepare($sql_file);
    $stmt_file->bind_param("i", $content_id);
    $stmt_file->execute();
    $result_file = $stmt_file->get_result();
    $file_to_delete = $result_file->fetch_assoc();
    $stmt_file->close();

    if ($file_to_delete) {
        $filepath = '../assets/uploads/' . $file_to_delete['filepath'];
        
        // เริ่ม Transaction
        $conn->begin_transaction();
        $success = true;

        try {
            // 2. ลบ Content ออกจากตาราง device_content ก่อน (Foreign Key Constraint)
            $sql_dc = "DELETE FROM device_content WHERE content_id = ?";
            $stmt_dc = $conn->prepare($sql_dc);
            $stmt_dc->bind_param("i", $content_id);
            $stmt_dc->execute();
            $stmt_dc->close();

            // 3. ลบ Content ออกจากตาราง contents
            $sql_c = "DELETE FROM contents WHERE content_id = ?";
            $stmt_c = $conn->prepare($sql_c);
            $stmt_c->bind_param("i", $content_id);
            $stmt_c->execute();
            $stmt_c->close();

            // 4. ลบไฟล์จริงออกจาก Server
            if (file_exists($filepath) && !is_dir($filepath)) {
                if (!unlink($filepath)) {
                    // หากลบไฟล์ไม่ได้ ให้ยกเลิกการทำธุรกรรม
                    throw new Exception("ไม่สามารถลบไฟล์จริงออกจาก Server ได้");
                }
            }

            $conn->commit();
            $_SESSION['message'] = ['type' => 'success', 'text' => 'ลบ Content เรียบร้อยแล้ว'];

        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['message'] = ['type' => 'danger', 'text' => 'เกิดข้อผิดพลาดในการลบ Content: ' . $e->getMessage()];
        }

    } else {
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'ไม่พบ Content ที่ต้องการลบ'];
    }

} else {
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'ID Content ไม่ถูกต้อง'];
}

header("Location: contents.php");
exit();

$conn->close();
?>