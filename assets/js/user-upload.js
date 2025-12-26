// assets/js/user-upload.js

document.addEventListener('DOMContentLoaded', function() {
    const devicesSelect = document.getElementById('devices');
    
    if (!devicesSelect) return;

    // Handle device selection changes
    devicesSelect.addEventListener('change', function() {
        const allDevicesOption = this.querySelector('option[value="all_devices"]');
        
        if (!allDevicesOption) return;

        // Check if any specific device is selected (not "all_devices")
        const specificDeviceSelected = Array.from(this.options).some(option => 
            option.selected && option.value !== 'all_devices' && option.value !== ''
        );
        
        // If "all_devices" is selected AND specific devices are selected
        // Deselect "all_devices" (user wants to choose specific devices)
        if (allDevicesOption.selected && specificDeviceSelected) {
            allDevicesOption.selected = false;
        }
        
        // If nothing is selected, automatically select "all_devices"
        const nothingSelected = Array.from(this.options).every(option => 
            !option.selected || option.value === ''
        );
        
        if (nothingSelected) {
            allDevicesOption.selected = true;
        }
    });

    // Handle form submission validation
    const uploadForm = document.querySelector('form[action="upload.php"]');
    
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            const selectedDevices = Array.from(devicesSelect.selectedOptions);
            
            // Check if at least one device is selected
            if (selectedDevices.length === 0) {
                e.preventDefault();
                alert('กรุณาเลือกอุปกรณ์อย่างน้อย 1 เครื่อง');
                devicesSelect.focus();
                return false;
            }
            
            // Optional: Show confirmation if uploading to all devices
            const allDevicesSelected = selectedDevices.some(opt => opt.value === 'all_devices');
            if (allDevicesSelected) {
                const totalDevices = devicesSelect.options.length - 1; // Exclude "all_devices" option
                const confirmMsg = `คุณต้องการอัพโหลด Content ไปยังอุปกรณ์ทั้งหมด (${totalDevices} เครื่อง) ใช่หรือไม่?`;
                
                if (!confirm(confirmMsg)) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    }

    // File input preview (optional enhancement)
    const fileInput = document.getElementById('content_file');
    
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            
            if (file) {
                const fileSize = (file.size / 1024 / 1024).toFixed(2); // MB
                const fileName = file.name;
                const fileType = file.type;
                
                console.log('File selected:', fileName);
                console.log('File size:', fileSize, 'MB');
                console.log('File type:', fileType);
                
                // Validate file size (e.g., max 50MB)
                const maxSize = 50; // MB
                if (fileSize > maxSize) {
                    alert(`ไฟล์มีขนาดใหญ่เกินไป (${fileSize} MB)\nขนาดไฟล์สูงสุดที่อนุญาต: ${maxSize} MB`);
                    this.value = ''; // Clear the input
                    return;
                }
                
                // Auto-adjust duration for video files
                const durationInput = document.getElementById('duration_seconds');
                if (durationInput && fileType.startsWith('video/')) {
                    durationInput.value = 0;
                    durationInput.readOnly = true;
                    durationInput.style.backgroundColor = '#e9ecef';
                } else if (durationInput) {
                    durationInput.readOnly = false;
                    durationInput.style.backgroundColor = '';
                    if (durationInput.value == 0) {
                        durationInput.value = 10;
                    }
                }
            }
        });
    }

    // Date/Time validation helper
    const startDateInput = document.getElementById('start_date_only');
    const endDateInput = document.getElementById('end_date_only');
    
    if (startDateInput && endDateInput) {
        // Validate end date is after start date
        function validateDates() {
            const startDate = startDateInput.value;
            const endDate = endDateInput.value;
            
            if (startDate && endDate && startDate > endDate) {
                endDateInput.setCustomValidity('วันที่สิ้นสุดต้องมาหลังวันที่เริ่มต้น');
            } else {
                endDateInput.setCustomValidity('');
            }
        }
        
        startDateInput.addEventListener('change', validateDates);
        endDateInput.addEventListener('change', validateDates);
    }

    // Auto-scroll to error message if exists
    const alertElement = document.querySelector('.alert');
    if (alertElement) {
        setTimeout(function() {
            alertElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 100);
    }
});