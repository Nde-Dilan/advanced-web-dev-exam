<?php
// Determine base path
$base_path = '';
if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) {
    $base_path = '../';
}
?>
<footer class="bg-dark text-light mt-5 py-4">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h5>EventBook</h5>
                <p>Your premier destination for event booking and management.</p>
            </div>
            <div class="col-md-6 text-end">
                <p>&copy; 2025 EventBook. All rights reserved.</p>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/qrious@4.0.2/dist/qrious.min.js"></script>
<!-- Custom JS -->
<!-- <script src="assets/js/main.js"></script> -->
<script src="<?php echo $base_path; ?>assets/js/main.js"></script>
</body>
</html>