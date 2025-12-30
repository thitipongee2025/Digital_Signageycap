<?php
// C:\xampp\htdocs\Digital_Signageycap\config.php

// 1. เริ่มต้น Session (ต้องอยู่บรรทัดแรกๆ)
session_start(); 

// 2. ข้อมูลการเชื่อมต่อฐานข้อมูล (โปรดตรวจสอบค่าเหล่านี้)
$servername = "localhost";
$username = "root";
$password = "12345678";
$dbname = "digital_signage_db";
$port = 3306;

// 3. สร้างการเชื่อมต่อ
$conn = @new mysqli($servername, $username, $password, $dbname, $port); 

// 4. ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("
        <h1 style='color: red;'>❌ Connection Failed!</h1>
        <p><strong>Error:</strong> ไม่สามารถเชื่อมต่อฐานข้อมูลได้</p>
        <p><strong>รายละเอียด:</strong> " . $conn->connect_error . "</p>
    ");
}

// 5. กำหนดการเข้ารหัสเป็น UTF-8
$conn->set_charset("utf8mb4");

// 6. ฟังก์ชันอำนวยความสะดวก
function formatDateTimeUser($datetime) {
    if (is_null($datetime) || $datetime === '0000-00-00 00:00:00') {
        return 'ตลอดไป';
    }
    return date('Y-m-d H:i', strtotime($datetime));
}

// 6.2 ฟังก์ชันตรวจสอบสิทธิ์ผู้ดูแลระบบ (Admin)
function checkAdminLogin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้ (Admin Required)'];
        header("Location: ../index.php"); 
        exit();
    }
}

// 6.3 ฟังก์ชันตรวจสอบสิทธิ์ผู้ใช้งานทั่วไป (User)
function checkUserLogin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้ (User Required)'];
        header("Location: ../index.php"); 
        exit();
    }
}

// 6.4 ฟังก์ชันตรวจสอบสถานะบัญชี (ใหม่)
function checkAccountStatus($conn, $user_id) {
    $sql = "SELECT account_status, fullname FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if ($user['account_status'] === 'suspended') {
            // บัญชีถูกระงับ - แสดงหน้าแจ้งเตือน
            $fullname = htmlspecialchars($user['fullname']);
            echo '<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บัญชีถูกระงับ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: "Sarabun", sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .suspended-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 500px;
            text-align: center;
        }
        .icon-suspended {
            font-size: 5rem;
            color: #dc3545;
            margin-bottom: 20px;
        }
        h2 {
            color: #dc3545;
            font-weight: 700;
            margin-bottom: 20px;
        }
        .alert {
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="suspended-card">
        <i class="bi bi-ban icon-suspended"></i>
        <h2>บัญชีถูกระงับการใช้งานชั่วคราว</h2>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>เรียน คุณ' . $fullname . '</strong><br>
            บัญชีของคุณถูกระงับการใช้งานชั่วคราวโดยผู้ดูแลระบบ
        </div>
        <div class="alert alert-info">
            <strong>หากต้องการเปิดการใช้งานอีกครั้ง:</strong>
            <ul class="mt-2 mb-0 text-start">
                <li>ติดต่อผู้ดูแลระบบ (Admin)</li>
                <li>แจ้งรหัสผู้ใช้งาน: <code>' . $_SESSION['username'] . '</code></li>
                <li>ระบุเหตุผลในการขอเปิดใช้งาน</li>
            </ul>
        </div>
        <a href="../logout.php" class="btn btn-danger btn-lg w-100 mt-3">
            <i class="bi bi-box-arrow-right"></i> ออกจากระบบ
        </a>
    </div>
</body>
</html>';
            exit();
        }
    }
    $stmt->close();
}
?>