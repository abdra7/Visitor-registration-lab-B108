document.addEventListener('DOMContentLoaded', function() {
    // Determine current page based on URL
    const currentPath = window.location.pathname;
    const pageName = currentPath.split('/').pop();
    
    // Initialize common functionality
    initializeCommon();
    
    // Initialize page-specific functionality
    if (pageName === 'visits.php') {
        initializeVisitsPage();
    } else if (pageName === 'admins.php') {
        initializeAdminsPage();
    } else if (pageName === 'statistics.php') {
        initializeStatisticsPage();
    } else if (pageName === 'dashboard.php') {
        initializeDashboardPage();
    }
});

/**
 * Common functionality for all pages
 */
function initializeCommon() {
    // Add active class to current navigation item
    const currentPath = window.location.pathname;
    const pageName = currentPath.split('/').pop();
    
    document.querySelectorAll('nav a').forEach(link => {
        if (link.getAttribute('href') === pageName) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
    
    // Add fade-in animation for main content
    const mainContent = document.querySelector('main');
    if (mainContent) {
        mainContent.style.opacity = '0';
        mainContent.style.transition = 'opacity 0.3s ease-in-out';
        setTimeout(() => {
            mainContent.style.opacity = '1';
        }, 100);
    }
    
    // Add logout confirmation
    const logoutLink = document.querySelector('a[href="logout.php"]');
    if (logoutLink) {
        logoutLink.addEventListener('click', function(e) {
            e.preventDefault();
            confirmLogout();
        });
    }
}

/**
 * Confirm logout action
 */
function confirmLogout() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'تأكيد الخروج',
            text: 'هل أنت متأكد أنك تريد تسجيل الخروج؟',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'نعم، تسجيل الخروج',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'logout.php';
            }
        });
    } else {
        // Fallback if SweetAlert is not available
        if (confirm('هل أنت متأكد أنك تريد تسجيل الخروج؟')) {
            window.location.href = 'logout.php';
        }
    }
}

function initializeVisitsPage() {
    const academicNumberField = document.getElementById('academic_number');
    
    if (academicNumberField) {
        let typingTimer;
        const delay = 3400; 

        academicNumberField.addEventListener('input', () => {
            clearTimeout(typingTimer); // يلغي المؤقت كل مايكتب المتدرب
            typingTimer = setTimeout(() => {
                fetchStudentName(); // ينفذ من بعد توقف الكتابه ب 3400 ملي ثاية
            }, delay);
        });

        academicNumberField.focus();
    }

}

/**
 * Fetch student name based on academic number
 */
function fetchStudentName() {
    const academicNumberField = document.getElementById('academic_number');
    const studentNameField = document.getElementById('student_name');
    
    if (!academicNumberField || !studentNameField) return;
    
    const academicId = academicNumberField.value.trim();
    if (academicId === "") return;
    
    // Show loading indicator
    studentNameField.value = "يتم البحث...";
    
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "visits.php", true);
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.onload = function() {
        const response = this.responseText.trim();
        
        if (response) {
            // Student found in database - populate the field and show success notification
            studentNameField.value = response;
            
            // Show notification that student was found
            showNotification(`تم العثور على الطالب: ${response}`, 'success');
            
            // Add a visual indicator to show the student was found in the database
            studentNameField.classList.add('student-found');
            setTimeout(() => {
                studentNameField.classList.remove('student-found');
            }, 2000);
            
            // Focus on the specialization dropdown
            const specializationField = document.getElementById('specialization');
            if (specializationField) specializationField.focus();
            
            // Check if the student is already checked in
            checkIfStudentActive(academicId);
        } else {
            // No student found with this academic number
            studentNameField.value = "";
            studentNameField.classList.remove('student-found');
            
            // Focus on student name field for manual entry
            studentNameField.focus();
        }
    };
    xhr.onerror = function() {
        studentNameField.value = "";
        showNotification('خطأ في الاتصال بالخادم', 'error');
    };
    xhr.send("academic_id=" + encodeURIComponent(academicId));
}

/**
 * Check if student is already active in the system
 */
function checkIfStudentActive(academicId) {
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "visits.php", true);
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.onload = function() {
        const response = this.responseText.trim();
        if (response === "active") {
            // Student is already checked in
            showNotification('هذا الطالب مسجل حالياً في المعمل!', 'warning');
            
            // Highlight the student in the active visits table if present
            highlightActiveStudent(academicId);
        }
    };
    xhr.send("check_active=1&academic_id=" + encodeURIComponent(academicId));
}

/**
 * Highlight a student in the active visits table
 */
function highlightActiveStudent(academicId) {
    const activeTable = document.querySelector('.card:nth-of-type(2) table');
    if (!activeTable) return;
    
    const rows = activeTable.querySelectorAll('tbody tr');
    rows.forEach(row => {
        const academicCell = row.querySelector('td:nth-child(2)');
        if (academicCell && academicCell.textContent.trim() === academicId) {
            // Highlight the row
            row.classList.add('highlight-row');
            setTimeout(() => {
                row.classList.remove('highlight-row');
            }, 5000);
            
            // Scroll to the row
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
}

/**
 * Confirm checkout for a student
 */
function confirmCheckout(visitId, studentName) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'تأكيد الخروج',
            html: `هل أنت متأكد أنك تريد تسجيل خروج الطالب <strong>${studentName}</strong>؟`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'نعم، سجل الخروج',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '?check_out=' + visitId;
            }
        });
    } else {
        // Fallback if SweetAlert is not available
        if (confirm(`هل أنت متأكد أنك تريد تسجيل خروج الطالب ${studentName}؟`)) {
            window.location.href = '?check_out=' + visitId;
        }
    }
}


/**
 * Admins Page Functionality
 */
function initializeAdminsPage() {
    const modal = document.getElementById('editModal');
    if (!modal) return;
    
    const closeBtn = modal.querySelector('.close');
    const editButtons = document.querySelectorAll('.btn-edit');
    
    // Setup edit buttons
    editButtons.forEach(button => {
        button.addEventListener('click', () => {
            const id = button.getAttribute('data-id');
            const username = button.getAttribute('data-username');
            
            document.getElementById('edit_admin_id').value = id;
            document.getElementById('edit_username').value = username;
            
            // Show modal with animation
            modal.style.display = 'flex';
            modal.style.opacity = '0';
            setTimeout(() => {
                modal.style.opacity = '1';
            }, 10);
        });
    });
    
    // Setup close button
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            closeModal(modal);
        });
    }
    
    // Close modal when clicking outside
    window.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal(modal);
        }
    });
    
    // Setup delete confirmation for admins
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const href = this.getAttribute('href');
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'تأكيد الحذف',
                    html: `
                        <div class="delete-admin-warning">
                            <p>هل أنت متأكد أنك تريد حذف هذا المسؤول؟</p>
                            <p><strong>تحذير:</strong> سيتم حذف جميع سجلات الزيارات المرتبطة بهذا المسؤول بشكل نهائي.</p>
                            <p>لا يمكن التراجع عن هذا الإجراء.</p>
                        </div>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'نعم، قم بالحذف',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = href;
                    }
                });
            } else {
                // Fallback if SweetAlert is not available
                if (confirm('هل أنت متأكد أنك تريد حذف هذا المسؤول؟\n\nتحذير: سيتم حذف جميع سجلات الزيارات المرتبطة بهذا المسؤول بشكل نهائي.\n\nلا يمكن التراجع عن هذا الإجراء.')) {
                    window.location.href = href;
                }
            }
        });
    });
    
    // Password strength meter
    const passwordInput = document.querySelector('input[name="new_password"]');
    if (passwordInput) {
        const strengthIndicator = document.createElement('div');
        strengthIndicator.className = 'password-strength';
        passwordInput.parentNode.insertBefore(strengthIndicator, passwordInput.nextSibling);
        
        passwordInput.addEventListener('input', function() {
            const strength = checkPasswordStrength(this.value);
            displayPasswordStrength(strength, strengthIndicator);
        });
    }
}

/**
 * Close modal with animation
 */
function closeModal(modal) {
    modal.style.opacity = '0';
    setTimeout(() => {
        modal.style.display = 'none';
    }, 300);
}

/**
 * Check password strength
 */
function checkPasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 8) strength += 1;
    if (password.match(/[a-z]+/)) strength += 1;
    if (password.match(/[A-Z]+/)) strength += 1;
    if (password.match(/[0-9]+/)) strength += 1;
    if (password.match(/[^a-zA-Z0-9]+/)) strength += 1;
    
    return strength;
}

/**
 * Display password strength
 */
function displayPasswordStrength(strength, indicator) {
    let text = '';
    let className = '';
    
    switch(strength) {
        case 0:
        case 1:
            text = 'ضعيفة';
            className = 'weak';
            break;
        case 2:
        case 3:
            text = 'متوسطة';
            className = 'medium';
            break;
        case 4:
        case 5:
            text = 'قوية';
            className = 'strong';
            break;
    }
    
    indicator.textContent = `قوة كلمة المرور: ${text}`;
    indicator.className = 'password-strength ' + className;
}

/**
 * Statistics Page Functionality
 */
function initializeStatisticsPage() {
    // Add dynamic effects to stat cards
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        // Add staggered animation delay
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('animated');
        
        // Add hover effect
        card.addEventListener('mouseenter', function() {
            this.classList.add('highlight');
        });
        
        card.addEventListener('mouseleave', function() {
            this.classList.remove('highlight');
        });
    });
}

/**
 * Dashboard Page Functionality
 */
function initializeDashboardPage() {
    // Auto-refresh dashboard stats every minute
    setInterval(function() {
        // This would require AJAX endpoints in dashboard.php
        // For now, we'll just refresh parts of the page if possible
        refreshDashboardStats();
    }, 60000);
}

/**
 * Refresh dashboard statistics without page reload
 */
function refreshDashboardStats() {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'dashboard.php?ajax=stats', true);
    xhr.onload = function() {
        if (this.status === 200) {
            // This would require adding an AJAX endpoint in dashboard.php
            // Implementation would update only the necessary parts
        }
    };
    xhr.send();
}

/**
 * Show notification using SweetAlert or fallback to alert
 */
function showNotification(message, type = 'info') {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: type === 'error' ? 'خطأ' : 'معلومات',
            text: message,
            icon: type,
            timer: 3000,
            timerProgressBar: true,
            toast: true,
            position: 'top-end',
            showConfirmButton: false
        });
    } else {
        // Fallback if SweetAlert is not available
        alert(message);
    }
}

/**
 * Debounce function to limit how often a function can be called
 */
function debounce(func, wait, immediate) {
    let timeout;
    return function() {
        const context = this, args = arguments;
        const later = function() {
            timeout = null;
            if (!immediate) func.apply(context, args);
        };
        const callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(context, args);
    };
}

/**
 * Add form validation to all forms
 */
(function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                    
                    // Create error message if it doesn't exist
                    if (!field.nextElementSibling || !field.nextElementSibling.classList.contains('error-message')) {
                        const errorMsg = document.createElement('div');
                        errorMsg.className = 'error-message';
                        errorMsg.textContent = 'هذا الحقل مطلوب';
                        field.parentNode.insertBefore(errorMsg, field.nextSibling);
                    }
                } else {
                    field.classList.remove('error');
                    
                    // Remove error message if it exists
                    if (field.nextElementSibling && field.nextElementSibling.classList.contains('error-message')) {
                        field.nextElementSibling.remove();
                    }
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                // Focus the first invalid field
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.focus();
                        return false;
                    }
                });
            }
        });
    });
})();