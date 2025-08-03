// Hostel Check-In/Out System - Main JavaScript File

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

// Main initialization function
function initializeApp() {
    // Initialize Bootstrap tooltips
    initializeTooltips();
    
    // Initialize form validation
    initializeFormValidation();
    
    // Initialize CSRF tokens
    initializeCSRF();
    
    // Initialize auto-logout
    initializeAutoLogout();
    
    // Initialize layout components
    initializeLayout();
    
    // Initialize QR scanner if on scanner page
    if (document.getElementById('qr-scanner')) {
        initializeQRScanner();
    }
    
    // Initialize real-time clock
    initializeClock();
    
    console.log('Hostel System initialized successfully');
}

// Initialize Bootstrap tooltips
function initializeTooltips() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Initialize layout components
function initializeLayout() {
    // Initialize sidebar toggle for mobile
    initializeSidebarToggle();
    
    // Initialize responsive navigation
    initializeResponsiveNav();
    
    // Initialize page transitions
    initializePageTransitions();
    
    // Handle window resize
    window.addEventListener('resize', handleWindowResize);
}

// Initialize sidebar toggle functionality
function initializeSidebarToggle() {
    const sidebarToggle = document.querySelector('[data-bs-toggle="offcanvas"]');
    const sidebar = document.getElementById('sidebar');
    const desktopSidebarToggle = document.getElementById('sidebarToggle');
    const desktopSidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    
    // Desktop sidebar toggle
    if (desktopSidebarToggle && desktopSidebar) {
        desktopSidebarToggle.addEventListener('click', () => {
            desktopSidebar.classList.toggle('collapsed');
            if (mainContent) {
                if (desktopSidebar.classList.contains('collapsed')) {
                    mainContent.classList.remove('with-sidebar');
                    mainContent.classList.add('without-sidebar');
                } else {
                    mainContent.classList.add('with-sidebar');
                    mainContent.classList.remove('without-sidebar');
                }
            }
        });
    }
    
    // Mobile sidebar functionality
    if (sidebarToggle && sidebar) {
        // Auto-close sidebar on navigation (mobile)
        const navLinks = sidebar.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 992) {
                    const offcanvas = bootstrap.Offcanvas.getInstance(sidebar);
                    if (offcanvas) {
                        offcanvas.hide();
                    }
                }
            });
        });
    }
    
    // Initialize logout confirmation
    initializeLogoutConfirmation();
}

// Initialize responsive navigation
function initializeResponsiveNav() {
    // Handle active navigation states
    const currentPage = new URLSearchParams(window.location.search).get('page') || 'dashboard';
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href && href.includes(`page=${currentPage}`)) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
}

// Initialize page transitions
function initializePageTransitions() {
    // Add smooth transitions for page navigation
    const navLinks = document.querySelectorAll('.nav-link[href*="page="]');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Add loading state
            showPageLoading();
        });
    });
}

// Handle window resize events
function handleWindowResize() {
    // Close mobile sidebar on desktop resize
    if (window.innerWidth >= 992) {
        const sidebar = document.getElementById('sidebar');
        if (sidebar) {
            const offcanvas = bootstrap.Offcanvas.getInstance(sidebar);
            if (offcanvas) {
                offcanvas.hide();
            }
        }
    }
}

// Show page loading state
function showPageLoading() {
    const mainContent = document.querySelector('.main-content');
    if (mainContent) {
        mainContent.style.opacity = '0.7';
        mainContent.style.pointerEvents = 'none';
    }
}

// Hide page loading state
function hidePageLoading() {
    const mainContent = document.querySelector('.main-content');
    if (mainContent) {
        mainContent.style.opacity = '1';
        mainContent.style.pointerEvents = 'auto';
    }
}

// Quick QR Scanner function for sidebar
function openQRScanner() {
    // Redirect to scanner page or open scanner modal
    const baseUrl = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ? 
        window.location.origin + window.location.pathname.replace('/index.php', '') : 
        window.location.origin;
    
    window.location.href = `${baseUrl}?page=scanner`;
}

// Form validation
function initializeFormValidation() {
    // Bootstrap form validation
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
    
    // Custom validation for login forms
    var loginForms = document.querySelectorAll('.login-form');
    loginForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            var email = form.querySelector('input[type="email"]');
            var password = form.querySelector('input[type="password"]');
            
            if (email && !isValidEmail(email.value)) {
                e.preventDefault();
                showAlert('Please enter a valid email address', 'danger');
                return;
            }
            
            if (password && password.value.length < 6) {
                e.preventDefault();
                showAlert('Password must be at least 6 characters long', 'danger');
                return;
            }
        });
    });
}

// CSRF token handling
function initializeCSRF() {
    // Add CSRF token to all forms
    var forms = document.querySelectorAll('form');
    forms.forEach(function(form) {
        if (!form.querySelector('input[name="csrf_token"]')) {
            var csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = getCSRFToken();
            form.appendChild(csrfInput);
        }
    });
}

// Auto-logout functionality
function initializeAutoLogout() {
    var lastActivity = Date.now();
    var timeout = 30 * 60 * 1000; // 30 minutes
    
    // Track user activity
    document.addEventListener('click', updateActivity);
    document.addEventListener('keypress', updateActivity);
    document.addEventListener('scroll', updateActivity);
    
    function updateActivity() {
        lastActivity = Date.now();
    }
    
    // Check for inactivity every minute
    setInterval(function() {
        if (Date.now() - lastActivity > timeout) {
            showAlert('Session expired due to inactivity. Redirecting to login...', 'warning');
            setTimeout(function() {
                window.location.href = window.location.origin + '?page=logout';
            }, 3000);
        }
    }, 60000);
}

// QR Scanner functionality
function initializeQRScanner() {
    // This would integrate with a QR scanning library
    // For now, we'll create a manual input fallback
    var scannerContainer = document.getElementById('qr-scanner');
    if (scannerContainer) {
        scannerContainer.innerHTML = `
            <div class="scanner-frame">
                <div class="text-center">
                    <i class="bi bi-qr-code-scan" style="font-size: 4rem; color: var(--hostel-secondary);"></i>
                    <p class="mt-3">QR Scanner will be implemented here</p>
                    <button type="button" class="btn btn-primary" onclick="showManualInput()">Manual Input</button>
                </div>
            </div>
        `;
    }
}

// Manual QR code input
function showManualInput() {
    var modal = new bootstrap.Modal(document.getElementById('manualInputModal') || createManualInputModal());
    modal.show();
}

function createManualInputModal() {
    var modalHTML = `
        <div class="modal fade" id="manualInputModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Manual Student ID Input</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="manualInputForm">
                            <div class="mb-3">
                                <label for="studentCode" class="form-label">Student QR Code or ID</label>
                                <input type="text" class="form-control" id="studentCode" required>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="processManualInput()">Process</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    return document.getElementById('manualInputModal');
}

function processManualInput() {
    var code = document.getElementById('studentCode').value;
    if (code) {
        window.location.href = `/scan?code=${encodeURIComponent(code)}`;
    }
}

// Real-time clock
function initializeClock() {
    var clockElement = document.getElementById('current-time');
    if (clockElement) {
        updateClock();
        setInterval(updateClock, 1000);
    }
    
    function updateClock() {
        var now = new Date();
        var timeString = now.toLocaleString('en-MY', {
            timeZone: 'Asia/Kuala_Lumpur',
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
        clockElement.textContent = timeString;
    }
}

// Utility functions
function isValidEmail(email) {
    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function getCSRFToken() {
    var metaTag = document.querySelector('meta[name="csrf-token"]');
    return metaTag ? metaTag.getAttribute('content') : '';
}

function showAlert(message, type = 'info', duration = 5000) {
    var alertContainer = document.getElementById('alert-container') || createAlertContainer();
    
    var alertHTML = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    alertContainer.insertAdjacentHTML('beforeend', alertHTML);
    
    // Auto-dismiss after duration
    if (duration > 0) {
        setTimeout(function() {
            var alerts = alertContainer.querySelectorAll('.alert');
            if (alerts.length > 0) {
                var alert = new bootstrap.Alert(alerts[0]);
                alert.close();
            }
        }, duration);
    }
}

function createAlertContainer() {
    var container = document.createElement('div');
    container.id = 'alert-container';
    container.className = 'position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}

function showLoading(element, text = 'Loading...') {
    if (element) {
        element.innerHTML = `
            <span class="loading-spinner"></span>
            <span class="ms-2">${text}</span>
        `;
        element.disabled = true;
    }
}

function hideLoading(element, originalText = 'Submit') {
    if (element) {
        element.innerHTML = originalText;
        element.disabled = false;
    }
}

// AJAX helper function
function makeRequest(url, options = {}) {
    const defaultOptions = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': getCSRFToken()
        }
    };
    
    const finalOptions = { ...defaultOptions, ...options };
    
    return fetch(url, finalOptions)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .catch(error => {
            console.error('Request failed:', error);
            showAlert('An error occurred. Please try again.', 'danger');
            throw error;
        });
}

// Check-in/Check-out functions
function processCheckIn(studentId) {
    var button = event.target;
    var originalText = button.innerHTML;
    
    showLoading(button, 'Processing Check-In...');
    
    makeRequest('/api/checkin', {
        method: 'POST',
        body: JSON.stringify({ student_id: studentId, action: 'in' })
    })
    .then(data => {
        if (data.success) {
            showAlert('Check-in successful!', 'success');
            // Refresh page or update UI
            setTimeout(() => location.reload(), 2000);
        } else {
            showAlert(data.message || 'Check-in failed', 'danger');
        }
    })
    .finally(() => {
        hideLoading(button, originalText);
    });
}

function processCheckOut(studentId) {
    var button = event.target;
    var originalText = button.innerHTML;
    
    showLoading(button, 'Processing Check-Out...');
    
    makeRequest('/api/checkout', {
        method: 'POST',
        body: JSON.stringify({ student_id: studentId, action: 'out' })
    })
    .then(data => {
        if (data.success) {
            showAlert('Check-out successful!', 'success');
            // Refresh page or update UI
            setTimeout(() => location.reload(), 2000);
        } else {
            showAlert(data.message || 'Check-out failed', 'danger');
        }
    })
    .finally(() => {
        hideLoading(button, originalText);
    });
}

// Initialize logout confirmation
function initializeLogoutConfirmation() {
    // Handle logout buttons with href (both old logout.php and new ?page=logout)
    const logoutLinks = document.querySelectorAll('a[href*="logout.php"], a[href*="page=logout"]');
    logoutLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (confirm('Are you sure you want to logout?')) {
                showPageLoading();
                window.location.href = this.href;
            }
        });
    });
    
    // Handle logout buttons with onclick events (both old logout.php and new ?page=logout)
    const logoutButtons = document.querySelectorAll('button[onclick*="logout.php"], button[onclick*="page=logout"]');
    logoutButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (confirm('Are you sure you want to logout?')) {
                showPageLoading();
                
                // Extract URL from onclick attribute
                const onclickValue = this.getAttribute('onclick');
                const urlMatch = onclickValue.match(/location\.href\s*=\s*['"](.*?)['"]/);
                
                if (urlMatch) {
                    window.location.href = urlMatch[1];
                } else {
                    // Fallback to new logout URL
                    window.location.href = window.location.origin + '?page=logout';
                }
            }
        });
    });
}

// Global exports for external use
window.HostelSystem = {
    showAlert,
    showLoading,
    hideLoading,
    makeRequest,
    processCheckIn,
    processCheckOut,
    showManualInput,
    initializeLogoutConfirmation
};