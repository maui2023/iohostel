<?php
// Prevent direct access
if (!defined('HOSTEL_APP')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Direct access not permitted');
}

// Set page title
$pageTitle = 'Layout Test';

// Include header
include __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="bi bi-speedometer2 me-2"></i>
                    Layout Test Page
                </h1>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle me-1"></i>
                        Test Button
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-gradient rounded-circle p-3">
                                <i class="bi bi-people text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-title mb-1">Total Students</h6>
                            <h4 class="mb-0 text-primary">150</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-gradient rounded-circle p-3">
                                <i class="bi bi-box-arrow-in-right text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-title mb-1">Checked In</h6>
                            <h4 class="mb-0 text-success">89</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-gradient rounded-circle p-3">
                                <i class="bi bi-box-arrow-right text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-title mb-1">Checked Out</h6>
                            <h4 class="mb-0 text-warning">61</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-gradient rounded-circle p-3">
                                <i class="bi bi-clock-history text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-title mb-1">Today's Logs</h6>
                            <h4 class="mb-0 text-info">24</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-activity me-2"></i>
                        Recent Activity
                    </h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex align-items-center px-0">
                            <div class="flex-shrink-0">
                                <div class="bg-success rounded-circle p-2">
                                    <i class="bi bi-box-arrow-in-right text-white small"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Ahmad bin Ali</h6>
                                        <p class="mb-0 text-muted small">Checked in</p>
                                    </div>
                                    <small class="text-muted">2 minutes ago</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="list-group-item d-flex align-items-center px-0">
                            <div class="flex-shrink-0">
                                <div class="bg-warning rounded-circle p-2">
                                    <i class="bi bi-box-arrow-right text-white small"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Siti Nurhaliza</h6>
                                        <p class="mb-0 text-muted small">Checked out</p>
                                    </div>
                                    <small class="text-muted">5 minutes ago</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="list-group-item d-flex align-items-center px-0">
                            <div class="flex-shrink-0">
                                <div class="bg-success rounded-circle p-2">
                                    <i class="bi bi-box-arrow-in-right text-white small"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Muhammad Faiz</h6>
                                        <p class="mb-0 text-muted small">Checked in</p>
                                    </div>
                                    <small class="text-muted">8 minutes ago</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        System Info
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted">Layout Components</small>
                        <div class="mt-1">
                            <span class="badge bg-success me-1">Header</span>
                            <span class="badge bg-success me-1">Sidebar</span>
                            <span class="badge bg-success">Footer</span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted">Responsive Design</small>
                        <div class="mt-1">
                            <span class="badge bg-primary me-1">Desktop</span>
                            <span class="badge bg-primary me-1">Tablet</span>
                            <span class="badge bg-primary">Mobile</span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted">JavaScript Features</small>
                        <div class="mt-1">
                            <span class="badge bg-info me-1">Navigation</span>
                            <span class="badge bg-info me-1">Tooltips</span>
                            <span class="badge bg-info">Clock</span>
                        </div>
                    </div>
                    
                    <div class="alert alert-success alert-sm mb-0">
                        <i class="bi bi-check-circle me-1"></i>
                        <small>Layout test successful!</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include __DIR__ . '/../layouts/footer.php';
?>