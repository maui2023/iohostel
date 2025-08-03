<?php
// Prevent direct access
if (!defined('HOSTEL_APP')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Direct access not permitted');
}

// Get hostel name from environment
$hostelName = $_ENV['HOSTEL_NAME'] ?? 'Hostel Management';
$currentYear = date('Y');
?>
    
    <!-- Footer -->
    <footer class="footer bg-light border-top mt-auto">
        <div class="container-fluid">
            <div class="row align-items-center py-3">
                <div class="col-md-6">
                    <span class="text-muted">
                        &copy; <?= $currentYear ?> <?= htmlspecialchars($hostelName) ?>. Powered by Sabily Enterprise. All rights reserved.
                    </span>
                </div>
                <div class="col-md-6 text-md-end">
                    <span class="text-muted small">
                        <i class="bi bi-shield-check me-1"></i>
                        Secure Check-in/Check-out System
                    </span>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Loading Overlay -->
    <div id="loading-overlay" class="loading-overlay d-none">
        <div class="loading-spinner">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div class="mt-2">Please wait...</div>
        </div>
    </div>
    
    <!-- Bootstrap 5.3 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- QR Code Scanner (if needed) -->
    <?php if (isset($includeQRScanner) && $includeQRScanner): ?>
    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/minified/html5-qrcode.min.js"></script>
    <?php endif; ?>
    
    <!-- Custom JavaScript -->
    <script src="<?= $_ENV['BASE_URL'] ?>/assets/js/main.js"></script>
    
    <!-- Page-specific JavaScript -->
    <?php if (isset($pageJS) && !empty($pageJS)): ?>
        <?php foreach ($pageJS as $jsFile): ?>
            <script src="<?= $_ENV['BASE_URL'] ?>/assets/js/<?= htmlspecialchars($jsFile) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Inline JavaScript -->
    <?php if (isset($inlineJS) && !empty($inlineJS)): ?>
    <script>
        <?= $inlineJS ?>
    </script>
    <?php endif; ?>
    
    <!-- Auto-logout warning for authenticated users -->
    <?php if (isAuthenticated()): ?>
    <script>
        // Initialize auto-logout functionality
        if (typeof initAutoLogout === 'function') {
            initAutoLogout(<?= ($_ENV['TOKEN_EXPIRY_MINUTES'] ?? 30) * 60 * 1000 ?>); // Convert to milliseconds
        }
        
        // Initialize real-time clock
        if (typeof initClock === 'function') {
            initClock();
        }
        
        // Initialize tooltips
        if (typeof initTooltips === 'function') {
            initTooltips();
        }
    </script>
    <?php endif; ?>
    
</body>
</html>