document.addEventListener("DOMContentLoaded", function() {
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.querySelector('.sidebar-toggle');
    
    // สร้าง Overlay
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);

    function toggleMenu() {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
        
        // เปลี่ยนไอคอน
        const icon = toggleBtn.querySelector('i');
        if (sidebar.classList.contains('active')) {
            icon.className = 'bi bi-x-lg';
        } else {
            icon.className = 'bi bi-list';
        }
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', toggleMenu);
    }

    overlay.addEventListener('click', toggleMenu);

    // ปิดเมนูอัตโนมัติเมื่อขยายจอ
    window.addEventListener('resize', function() {
        if (window.innerWidth > 720) {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            if(toggleBtn) toggleBtn.querySelector('i').className = 'bi bi-list';
        }
    });
});