<?php
// user/playlist_preview.php
include '../config.php';

$device_id = isset($_GET['device_id']) ? (int)$_GET['device_id'] : 0;

if ($device_id === 0) {
    header("Location: device_status.php");
    exit();
}

// 1. ดึงข้อมูลอุปกรณ์
$device_sql = "SELECT device_id, device_name, location FROM devices WHERE device_id = ?";
$device_stmt = $conn->prepare($device_sql);
$device_stmt->bind_param("i", $device_id);
$device_stmt->execute();
$device_result = $device_stmt->get_result();
$device_info = $device_result->fetch_assoc();
$device_stmt->close();

if (!$device_info) {
    die("ไม่พบอุปกรณ์ ID: " . $device_id);
}

// ⭐ เพิ่มส่วนนี้: อัพเดทสถานะเป็น online ทันทีที่เปิดหน้า
$update_status_sql = "UPDATE devices SET status = 'online', last_active = NOW() WHERE device_id = ?";
$update_stmt = $conn->prepare($update_status_sql);
$update_stmt->bind_param("i", $device_id);
$update_stmt->execute();
$update_stmt->close();

// ดึง Content สำหรับ Playlist ของอุปกรณ์เครื่องนี้โดยเฉพาะ
$current_time = date('Y-m-d H:i:s');

$playlist_sql = "
    SELECT 
        c.content_id,
        c.filename, 
        c.filepath, 
        c.content_type, 
        c.duration_seconds,
        c.upload_by,
        u.fullname as uploader_name,
        dc.display_order
    FROM 
        device_content dc
    JOIN 
        contents c ON dc.content_id = c.content_id
    JOIN 
        users u ON c.upload_by = u.user_id
    WHERE 
        dc.device_id = ? 
        AND (
            (c.start_date IS NULL OR c.start_date <= ?)
            AND (c.end_date IS NULL OR c.end_date >= ?)
        )
    ORDER BY 
        dc.display_order ASC, c.content_id DESC
";

$playlist_stmt = $conn->prepare($playlist_sql);
$playlist_stmt->bind_param("iss", $device_id, $current_time, $current_time);
$playlist_stmt->execute();
$playlist_result = $playlist_stmt->get_result();
$playlist_items = $playlist_result->fetch_all(MYSQLI_ASSOC);
$playlist_stmt->close();
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Playlist: <?php echo htmlspecialchars($device_info['device_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/playlist_preview.css">
</head>

<body>
    <div id="app-container" style="background-color: #000;">

        <button id="exit-fullscreen-btn" class="btn btn-sm btn-warning">
            <i class="bi bi-fullscreen-exit"></i> ออกจากเต็มจอ
        </button>

        <div id="info-overlay" class="info-overlay">
            <div id="info-overlay-content">
                <strong>📺 <?php echo htmlspecialchars($device_info['device_name']); ?></strong>
                <span>| <?php echo htmlspecialchars($device_info['location']); ?></span><br>
                <small>จำนวน Content สำหรับเครื่องนี้: <?php echo count($playlist_items); ?> รายการ</small>

                <div class="mt-2">
                    <button id="fullscreen-btn" class="btn btn-sm btn-outline-warning">
                        <i class="bi bi-arrows-fullscreen"></i> เต็มจอ
                    </button>
                    <a href="device_status.php" class="btn btn-sm btn-outline-light ms-1">
                        <i class="bi bi-x-circle"></i> ปิด
                    </a>
                </div>
            </div>
        </div>

        <div class="playlist-container">
            <?php if (empty($playlist_items)): ?>
            <div class="no-content">
                <h2 class="text-danger">❌ ไม่พบ Content ในเครื่องนี้</h2>
                <p class="text-white-50">กรุณาเพิ่ม Content สำหรับอุปกรณ์เครื่องนี้</p>
                <a href="my_content.php" class="btn btn-primary mt-3">
                    <i class="bi bi-plus-circle"></i> เพิ่ม Content
                </a>
            </div>
            <?php else: ?>
            <?php foreach ($playlist_items as $index => $item): ?>
            <div class="content-item" data-index="<?php echo $index; ?>"
                data-type="<?php echo $item['content_type']; ?>"
                data-duration="<?php echo $item['duration_seconds']; ?>">

                <?php $file_path = '../assets/uploads/' . $item['filepath']; ?>

                <?php if ($item['content_type'] === 'image'): ?>
                <img src="<?php echo $file_path; ?>" class="content-image" alt="content">
                <?php elseif ($item['content_type'] === 'video'): ?>
                <video id="video-<?php echo $index; ?>" src="<?php echo $file_path; ?>" muted playsinline
                    class="content-video"></video>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
    // ตรวจสอบว่ามี elements ก่อนทำงาน
    const appContainer = document.getElementById('app-container');
    const infoOverlay = document.getElementById('info-overlay');
    const items = document.querySelectorAll('.content-item');
    const fullscreenBtn = document.getElementById('fullscreen-btn');
    const exitFullscreenBtn = document.getElementById('exit-fullscreen-btn');

    let currentIndex = 0;
    let timeout;

    // ⭐ เพิ่มส่วนนี้: ส่งสัญญาณ heartbeat เพื่อรักษาสถานะ online
    const deviceId = <?php echo $device_id; ?>;

    function updateDeviceStatus() {
        fetch('update_device_heartbeat.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                device_id: deviceId
            })
        }).catch(err => console.log('Heartbeat error:', err));
    }

    // ส่ง heartbeat ทันทีเมื่อโหลดหน้า
    updateDeviceStatus();

    // ส่ง heartbeat ทุก 30 วินาที
    const heartbeatInterval = setInterval(updateDeviceStatus, 30000);

    // ตั้งสถานะเป็น offline เมื่อปิดหน้า
    window.addEventListener('beforeunload', function() {
        navigator.sendBeacon('update_device_heartbeat.php',
            JSON.stringify({
                device_id: deviceId,
                status: 'offline'
            })
        );
    });

    // ตรวจสอบว่ามี content หรือไม่
    if (items.length === 0) {
        console.warn('ไม่มี content items ที่จะแสดง');
        // ซ่อนปุ่มที่ไม่จำเป็น
        if (exitFullscreenBtn) exitFullscreenBtn.style.display = 'none';
        if (fullscreenBtn) fullscreenBtn.style.display = 'none';
    } else {
        // มี content ให้ทำงานตามปกติ

        // ฟังก์ชันตรวจจับขนาดภาพ/วิดีโอ
        function detectOrientation() {
            items.forEach(item => {
                const media = item.querySelector('img, video');
                if (media) {
                    if (media.tagName === 'IMG') {
                        if (media.complete) {
                            checkOrientation(media, item);
                        } else {
                            media.onload = () => checkOrientation(media, item);
                        }
                    } else {
                        media.onloadedmetadata = () => checkOrientation(media, item);
                    }
                }
            });
        }

        function checkOrientation(media, item) {
            const width = media.videoWidth || media.naturalWidth;
            const height = media.videoHeight || media.naturalHeight;
            if (width > height) {
                item.classList.add('landscape');
            } else {
                item.classList.add('portrait');
            }
        }

        detectOrientation();

        // Fullscreen controls
        if (fullscreenBtn) {
            fullscreenBtn.addEventListener('click', () => {
                if (appContainer.requestFullscreen) {
                    appContainer.requestFullscreen();
                } else if (appContainer.webkitRequestFullscreen) {
                    appContainer.webkitRequestFullscreen();
                } else if (appContainer.msRequestFullscreen) {
                    appContainer.msRequestFullscreen();
                }
            });
        }

        if (exitFullscreenBtn) {
            exitFullscreenBtn.addEventListener('click', () => {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                } else if (document.msExitFullscreen) {
                    document.msExitFullscreen();
                }
            });
        }

        document.addEventListener('fullscreenchange', handleFullscreenChange);
        document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
        document.addEventListener('msfullscreenchange', handleFullscreenChange);

        function handleFullscreenChange() {
            const isFS = !!(document.fullscreenElement || document.webkitFullscreenElement || document
                .msFullscreenElement);
            if (exitFullscreenBtn) {
                exitFullscreenBtn.style.display = 'none';
            }
            if (infoOverlay) {
                infoOverlay.style.opacity = isFS ? '0' : '1';
            }
        }

        if (appContainer) {
            appContainer.addEventListener('dblclick', () => {
                if (document.fullscreenElement || document.webkitFullscreenElement) {
                    if (exitFullscreenBtn) {
                        exitFullscreenBtn.style.display = (exitFullscreenBtn.style.display === 'none') ?
                            'block' : 'none';
                    }
                }
            });
        }

        function showContent(index) {
            // ตรวจสอบ index ให้อยู่ในช่วงที่ถูกต้อง
            if (index < 0 || index >= items.length) {
                console.error('Invalid index:', index);
                return;
            }

            items.forEach(item => {
                item.classList.remove('active');
                const v = item.querySelector('video');
                if (v) {
                    v.pause();
                    v.currentTime = 0;
                }
            });

            const current = items[index];
            if (!current) {
                console.error('Current item not found at index:', index);
                return;
            }

            current.classList.add('active');
            const type = current.dataset.type;
            let duration = parseInt(current.dataset.duration) * 1000 || 10000;

            if (type === 'video') {
                const video = current.querySelector('video');
                if (video) {
                    video.play().catch(err => {
                        console.warn('Video play error:', err);
                    });

                    if (parseInt(current.dataset.duration) === 0) {
                        video.onended = nextContent;
                    } else {
                        timeout = setTimeout(nextContent, duration);
                    }
                }
            } else {
                timeout = setTimeout(nextContent, duration);
            }
        }

        function nextContent() {
            if (items.length === 0) return;

            currentIndex = (currentIndex + 1) % items.length;
            clearTimeout(timeout);
            showContent(currentIndex);
        }

        // เริ่มแสดง content
        showContent(0);
    }
    </script>
</body>

</html>
<?php $conn->close(); ?>