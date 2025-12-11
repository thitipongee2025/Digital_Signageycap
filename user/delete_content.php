<?php
include '../config.php';
// ตรวจสอบ Login และสิทธิ์ User
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../index.php");
    exit();
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $content_id = (int)$_GET['id'];
    $user_id = $_SESSION['user_id'];
    
    // 1. ตรวจสอบสิทธิ์: ต้องเป็น Content ที่ User คนนี้อัพโหลดเท่านั้น
    $check_sql = "SELECT filepath FROM contents WHERE content_id = ? AND upload_by = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $content_id, $user_id);
    $check_stmt->execute();
    $file_to_delete = $check_stmt->get_result()->fetch_assoc();
    $check_stmt->close();

    if ($file_to_delete) {
        $filepath = '../assets/uploads/' . $file_to_delete['filepath'];
        
        $conn->begin_transaction();

        try {
            // 2. ลบ Content ออกจากตาราง device_content (Playlist)
            // เนื่องจาก User มีสิทธิ์เฉพาะในอุปกรณ์ที่ Admin กำหนดให้ (แม้ Content จะถูกลบจาก Playlist อื่นๆ ของ Admin แต่ไฟล์ยังอยู่)
            // ในที่นี้ เราจะอนุญาตให้ User ลบ Content ทั้งหมดที่เขาอัพโหลด (รวมถึงการลบไฟล์จริง)
            // หากต้องการให้ลบแค่ใน Playlist ที่ได้รับสิทธิ์ ต้องปรับ logic การลบไฟล์
            
            // ลบจาก Playlist ทั้งหมด
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
                unlink($filepath);
            }

            $conn->commit();
            $_SESSION['message'] = ['type' => 'success', 'text' => 'ลบ Content ของคุณเรียบร้อยแล้ว'];

        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['message'] = ['type' => 'danger', 'text' => 'เกิดข้อผิดพลาดในการลบ Content: ' . $e->getMessage()];
        }

    } else {
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'ไม่พบ Content นี้ หรือคุณไม่มีสิทธิ์ในการลบ'];
    }

} else {
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'ID Content ไม่ถูกต้อง'];
}

header("Location: index.php");
exit();

$conn->close();
?>