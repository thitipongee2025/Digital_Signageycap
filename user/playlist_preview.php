<?php
// admin/device_playlist.php - ระบบเล่น Playlist หน้าจอ (ปรับปรุง)
include '../config.php';

$device_id = isset($_GET['device_id']) ? (int)$_GET['device_id'] : 0;

if ($device_id === 0) {
    header("Location: devices.php");
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

// 2. ดึง Content สำหรับ Playlist พร้อมข้อมูล upload_by
// เงื่อนไข:
// 1. เชื่อมกับ device_content ด้วย device_id
// 2. เวลาปัจจุบันอยู่ระหว่าง start_date และ end_date
// 3. ดึงข้อมูลผู้อัพโหลดด้วย
$current_time = date('Y-m-d H:i:s');

$playlist_sql = "
    SELECT 
        c.content_id,
        c.filename, 
        c.filepath, 
        c.content_type, 
        c.duration_seconds,
        c.upload_by,
        dc.display_order,
        u.fullname as uploader_name
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
        dc.display_order ASC
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
    <style>
        body, html {
            font-family: 'Sarabun', sans-serif;
            height: 100%;
            margin: 0;
            background-color: #343a40; 
            color: white;
            overflow: hidden; 
        }
        .playlist-container {
            width: 100vw;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .content-item {
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0;
            left: 0;
            display: none;
            justify-content: center;
            align-items: center;
            background-color: black;
            opacity: 0;
            transition: opacity 1s ease-in-out;
        }
        .content-item.active {
            display: flex;
            opacity: 1;
        }
        .content-item img, 
        .content-item video {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain; 
        }
        
        .info-overlay {
            position: fixed;
            top: 10px;
            left: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            z-index: 100;
            font-size: 0.8rem;
            transition: opacity 0.3s; 
        }
        
        .info-overlay-content {
            display: block;
            transition: opacity 0.3s;
        }
        
        :fullscreen .info-overlay-content, 
        :-webkit-full-screen .info-overlay-content,
        :-moz-full-screen .info-overlay-content,
        :-ms-full-screen .info-overlay-content {
            display: none; 
            opacity: 0;
        }

        #exit-fullscreen-btn {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 101;
        }

        .uploader-info {
            position: fixed;
            bottom: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.6);
            color: #aaa;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 0.75rem;
            z-index: 99;
        }
    </style>
</head>
<body>
    <div id="app-container">
        
        <button id="exit-fullscreen-btn" class="btn btn-sm btn-outline-warning text-white" style="display:none;">
            <i class="bi bi-fullscreen-exit"></i> ออกจากเต็มจอ
        </button>

        <div id="info-overlay" class="info-overlay">
            
            <div id="info-overlay-content" class="info-overlay-content">
                <strong>📺 <?php echo htmlspecialchars($device_info['device_name']); ?></strong> 
                (<?php echo htmlspecialchars($device_info['location']); ?>)<br>
                จำนวน Content: <span id="content-count"><?php echo count($playlist_items); ?></span> รายการ
                
                <button id="fullscreen-btn" class="btn btn-sm btn-outline-warning ms-2 me-1">
                    <i class="bi bi-arrows-fullscreen"></i> เต็มจอ
                </button>
                
                <a href="devices.php" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-x-circle"></i> ปิด
                </a>
            </div>
            
        </div>

        <!-- แสดงผู้อัพโหลด Content ปัจจุบัน -->
        <div id="uploader-info" class="uploader-info" style="display:none;">
            อัพโหลดโดย: <span id="uploader-name">-</span>
        </div>

        <div class="playlist-container">
            <?php if (empty($playlist_items)): ?>
                <h2 class="text-danger">❌ ไม่พบ Content ที่กำลังเล่นในขณะนี้</h2>
            <?php endif; ?>

            <?php foreach ($playlist_items as $index => $item): ?>
                <div class="content-item" 
                     data-index="<?php echo $index; ?>" 
                     data-type="<?php echo $item['content_type']; ?>" 
                     data-duration="<?php echo $item['duration_seconds']; ?>"
                     data-uploader="<?php echo htmlspecialchars($item['uploader_name']); ?>">
                    
                    <?php $file_path = '../assets/uploads/' . $item['filepath']; ?>
                    
                    <?php if ($item['content_type'] === 'image'): ?>
                        <img src="<?php echo $file_path; ?>" alt="<?php echo htmlspecialchars($item['filename']); ?>">
                    <?php elseif ($item['content_type'] === 'video'): ?>
                        <video id="video-<?php echo $index; ?>" src="<?php echo $file_path; ?>" autoplay muted playsinline></video>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div> 

    <script>
        const appContainer = document.getElementById('app-container');
        const infoOverlay = document.getElementById('info-overlay');
        const uploaderInfo = document.getElementById('uploader-info');
        const uploaderName = document.getElementById('uploader-name');
        const items = document.querySelectorAll('.content-item');
        const fullscreenBtn = document.getElementById('fullscreen-btn');
        const exitFullscreenBtn = document.getElementById('exit-fullscreen-btn');
        let currentIndex = 0;
        let timeout;

        // --- Fullscreen Logic ---
        function enterFullScreen(element) {
            if (element.requestFullscreen) {
                element.requestFullscreen();
            } else if (element.mozRequestFullScreen) {
                element.mozRequestFullScreen();
            } else if (element.webkitRequestFullscreen) {
                element.webkitRequestFullscreen();
            } else if (element.msRequestFullscreen) {
                element.msRequestFullscreen();
            }
        }

        function exitFullScreen() {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.mozCancelFullScreen) {
                document.mozCancelFullScreen();
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            } else if (document.msExitFullscreen) {
                document.msExitFullscreen();
            }
        }

        fullscreenBtn.addEventListener('click', () => {
            enterFullScreen(appContainer);
        });

        exitFullscreenBtn.addEventListener('click', () => {
            exitFullScreen();
        });

        function handleFullscreenChange() {
            const isFullscreen = document.fullscreenElement || 
                                 document.webkitFullscreenElement || 
                                 document.mozFullScreenElement || 
                                 document.msFullscreenElement;
            
            if (isFullscreen) {
                infoOverlay.style.opacity = 0; 
                exitFullscreenBtn.style.display = 'block';
            } else {
                infoOverlay.style.opacity = 1;
                exitFullscreenBtn.style.display = 'none';
            }
        }

        document.addEventListener('fullscreenchange', handleFullscreenChange);
        document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
        document.addEventListener('mozfullscreenchange', handleFullscreenChange);
        document.addEventListener('msfullscreenchange', handleFullscreenChange);

        // --- Playlist Playback Logic ---
        if (items.length > 0) {
            
            function showContent(index) {
                // ซ่อนทั้งหมด
                items.forEach((item, i) => {
                    item.classList.remove('active');
                    if (item.querySelector('video')) {
                        item.querySelector('video').pause();
                        item.querySelector('video').currentTime = 0;
                    }
                });

                // แสดง Content ปัจจุบัน
                const currentItem = items[index];
                currentItem.classList.add('active');

                // แสดงชื่อผู้อัพโหลด
                const uploaderText = currentItem.dataset.uploader;
                uploaderName.textContent = uploaderText || 'ไม่ระบุ';
                uploaderInfo.style.display = 'block';

                const type = currentItem.dataset.type;
                let duration = parseInt(currentItem.dataset.duration) * 1000;

                if (type === 'video') {
                    const videoElement = currentItem.querySelector('video');
                    
                    if (duration === 0 || isNaN(duration)) {
                        videoElement.onended = nextContent;
                        duration = null; 
                    } else {
                        videoElement.onended = null;
                        timeout = setTimeout(nextContent, duration);
                    }
                    
                    videoElement.play().catch(e => {
                        console.log("Video playback failed.", e);
                        if (!duration) {
                            timeout = setTimeout(nextContent, 5000); 
                        }
                    });

                } else {
                    // ภาพนิ่ง
                    if (duration === 0 || isNaN(duration)) {
                        duration = 10000; 
                    }
                    timeout = setTimeout(nextContent, duration);
                }
            }

            function nextContent() {
                currentIndex = (currentIndex + 1) % items.length;
                clearTimeout(timeout);
                showContent(currentIndex);
            }

            // เริ่มต้น
            showContent(currentIndex);
        } else {
            uploaderInfo.style.display = 'none';
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>