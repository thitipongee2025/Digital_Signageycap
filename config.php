<?php
// กำหนดค่าการเชื่อมต่อฐานข้อมูล
$host = "localhost";
$user = "root"; // ผู้ใช้เริ่มต้นของ XAMPP
$password = "12345678"; // รหัสผ่านเริ่มต้นของ XAMPP
$database = "digital_signage_db"; // ชื่อฐานข้อมูล

// สร้างการเชื่อมต่อ
$conn = new mysqli($host, $user, $password, $database);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ตั้งค่า Charset เป็น UTF8
$conn->set_charset("utf8");

// เริ่ม Session สำหรับระบบ Login
session_start();

// ฟังก์ชันตรวจสอบการ Login ของ Admin
function checkAdminLogin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header("Location: ../index.php"); // ส่งไปหน้า Login
        exit();
    }
}

/**
 * แปลงรูปแบบวันที่และเวลาจากฐานข้อมูล (Y-m-d H:i:s)
 * ให้อยู่ในรูปแบบที่ผู้ใช้มองเห็นได้ง่ายขึ้น
 * @param string|null $datetime_str วันที่และเวลา
 * @return string
 */
function formatDateTimeUser($datetime_str) {
    if (empty($datetime_str)) {
        return '- (ไม่กำหนด) -';
    }
    // ใช้ DateTime เพื่อจัดรูปแบบ
    try {
        $datetime = new DateTime($datetime_str);
        // รูปแบบ เช่น 15 ธ.ค. 2568 14:30
        return $datetime->format('d M Y H:i');
    } catch (Exception $e) {
        // หากรูปแบบผิดพลาด
        return $datetime_str; 
    }
}

?>