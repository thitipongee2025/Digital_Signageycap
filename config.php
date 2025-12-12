<?php
// C:\xampp\htdocs\Digital_Signageycap\config.php

// 1. เริ่มต้น Session (ต้องอยู่บรรทัดแรกๆ)
session_start(); 

// 2. ข้อมูลการเชื่อมต่อฐานข้อมูล (โปรดตรวจสอบค่าเหล่านี้)
$servername = "localhost"; // หรือ 127.0.0.1
$username = "root";        // โดยปกติ XAMPP/WAMP จะใช้ root
$password = "12345678";            // โดยปกติ XAMPP/WAMP จะไม่มีรหัสผ่าน (ถ้ามี ให้ใส่รหัสผ่านของคุณ)
$dbname = "digital_signage_db"; // ชื่อฐานข้อมูลของคุณ (ตรวจสอบให้ตรง)
$port = 3306; // Port มาตรฐานของ MySQL/MariaDB (หากมี Error "actively refused" ให้ลองเปลี่ยนเป็น Port ที่ XAMPP ใช้จริง)

// 3. สร้างการเชื่อมต่อ (เกิด Error ที่บรรทัดนี้: LINE 12)
// ใช้ $port ใน constructor ด้วยเพื่อความแน่ใจ
$conn = @new mysqli($servername, $username, $password, $dbname, $port); 

// 4. ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    // ข้อความ Error ที่ปรับปรุงแล้ว
    die("
        <h1 style='color: red;'>❌ Connection Failed!</h1>
        <p><strong>Error:</strong> ไม่สามารถเชื่อมต่อฐานข้อมูลได้ (Target machine actively refused it).</p>
        <p><strong>สาเหตุที่พบบ่อย:</strong></p>
        <ul>
            <li><strong>XAMPP:</strong> โปรดตรวจสอบ <strong>XAMPP Control Panel</strong> และคลิก <strong>Start</strong> ที่บริการ <strong>MySQL/MariaDB</strong> (ต้องเป็นสีเขียว)</li>
            <li><strong>Port:</strong> โปรดตรวจสอบว่าค่า <strong>\$port</strong> ใน config.php (\$$port) ตรงกับ Port ที่ MySQL กำลังรันอยู่หรือไม่</li>
            <li><strong>Database Name:</strong> ตรวจสอบว่า <strong>\$dbname</strong> (\$$dbname) สะกดถูกต้อง</li>
        </ul>
        <p><strong>รายละเอียด:</strong> " . $conn->connect_error . "</p>
    ");
}

// 5. กำหนดการเข้ารหัสเป็น UTF-8 เพื่อรองรับภาษาไทย
$conn->set_charset("utf8mb4");

// ----------------------------------------------------
// 6. ฟังก์ชันอำนวยความสะดวก
// ----------------------------------------------------

// 6.1 ฟังก์ชันสำหรับฟอร์แมตวันที่และเวลาสำหรับผู้ใช้งาน
function formatDateTimeUser($datetime) {
    // ถ้าเป็นค่า NULL หรือค่าเริ่มต้นของ MySQL (0000-00-00 00:00:00) ให้แสดงเป็น "ตลอดไป"
    if (is_null($datetime) || $datetime === '0000-00-00 00:00:00') {
        return 'ตลอดไป';
    }
    // แปลงรูปแบบวันที่เป็น YYYY-MM-DD HH:MM
    return date('Y-m-d H:i', strtotime($datetime));
}

// 6.2 ฟังก์ชันตรวจสอบสิทธิ์ผู้ดูแลระบบ (Admin)
function checkAdminLogin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        // Redirect ไปที่หน้า Login หากไม่ได้เป็น Admin
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้ (Admin Required)'];
        header("Location: ../index.php"); 
        exit();
    }
}

// 6.3 ฟังก์ชันตรวจสอบสิทธิ์ผู้ใช้งานทั่วไป (User)
function checkUserLogin() {
    // ตรวจสอบว่ามีการ Login และเป็นบทบาท 'user' หรือไม่
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
        // Redirect ไปที่หน้า Login หากไม่ได้เป็น User
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้ (User Required)'];
        header("Location: ../index.php"); 
        exit();
    }
}
?>