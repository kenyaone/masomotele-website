/**
 * MTTI Exam Auto-Fill Script
 * Add this to each exam file to auto-fill student data from the portal
 */

document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    
    // Get student data from URL
    const studentName = urlParams.get('student_name');
    const admissionNumber = urlParams.get('admission_number');
    const siteUrl = urlParams.get('site_url');
    const scheduleId = urlParams.get('schedule_id');
    
    // Store schedule_id globally for submission
    window.MTTI_SCHEDULE_ID = scheduleId;
    
    // Auto-fill form fields
    if (studentName) {
        const nameField = document.getElementById('studentName');
        if (nameField) {
            nameField.value = studentName;
            nameField.readOnly = true;
            nameField.style.backgroundColor = '#f0fdf4';
        }
    }
    
    if (admissionNumber) {
        const admField = document.getElementById('admissionNo');
        if (admField) {
            admField.value = admissionNumber;
            admField.readOnly = true;
            admField.style.backgroundColor = '#f0fdf4';
        }
    }
    
    if (siteUrl) {
        const urlField = document.getElementById('siteUrl');
        if (urlField) {
            urlField.value = siteUrl;
        }
    }
    
    // Add "Back to Portal" button if came from portal
    if (studentName && admissionNumber) {
        const backBtn = document.createElement('button');
        backBtn.textContent = '← Back to Portal';
        backBtn.style.cssText = 'position:fixed;top:10px;left:10px;z-index:1000;padding:10px 20px;background:#2563eb;color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:600;';
        backBtn.onclick = function() {
            if (confirm('Are you sure you want to leave? Your progress will be lost.')) {
                window.location.href = 'student-portal.html';
            }
        };
        document.body.appendChild(backBtn);
        
        // Hide MIS configuration section
        const configSection = document.querySelector('.config-section');
        if (configSection) {
            configSection.style.display = 'none';
        }
    }
});

// Patch the syncToMIS function to include schedule_id
(function() {
    const originalFetch = window.fetch;
    window.fetch = function(url, options) {
        if (url && url.includes('exam-results') && options && options.body) {
            try {
                const body = JSON.parse(options.body);
                if (window.MTTI_SCHEDULE_ID) {
                    body.schedule_id = window.MTTI_SCHEDULE_ID;
                    options.body = JSON.stringify(body);
                }
            } catch (e) {}
        }
        return originalFetch.apply(this, arguments);
    };
})();
