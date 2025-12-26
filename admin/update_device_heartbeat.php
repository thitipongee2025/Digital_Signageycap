<?php
// admin/update_device_heartbeat.php
include '../config.php';

header('Content-Type: application/json');

// รับข้อมูลจาก POST
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['device_id']) || !is_numeric($data['device_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid device ID']);
    exit();
}

$device_id = (int)$data['device_id'];
$status = isset($data['status']) ? $data['status'] : 'online';

// อัพเดทสถานะและเวลาที่ active ล่าสุด
$sql = "UPDATE devices SET status = ?, last_active = NOW() WHERE device_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $status, $device_id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'อัพเดทสถานะสำเร็จ',
        'status' => $status,
        'device_id' => $device_id
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'อัพเดทสถานะไม่สำเร็จ'
    ]);
}

$stmt->close();
$conn->close();
?>