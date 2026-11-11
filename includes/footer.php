<?php
/**
 * Smart CampusConnect - Unified Campus Management Portal
 * Common Portal Footer & Script Loaders
 * BCA 5th Semester Major Project 1
 */
?>
    <!-- Main Content Ends -->
    </div> <!-- .page-content -->

    <!-- Portal Footer -->
    <footer class="bg-white border-top py-3 px-4 mt-auto">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
            <div class="text-muted small">
                &copy; <?= date('Y') ?> <strong>Smart CampusConnect</strong> &bull; BCA 5th Semester Major Project 1
            </div>
            <div class="text-muted small">
                <span>Designed &amp; Developed with <i class="bi bi-heart-fill text-danger"></i> by Team (4 Members)</span>
            </div>
        </div>
    </footer>
</div> <!-- .main-wrapper -->

<!-- Bootstrap 5.3 Bundle JS with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Chart.js 4.4 CDN for Data Analytics & Dashboards -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<!-- Portal Core JS -->
<script src="<?= BASE_URL ?>assets/js/main.js"></script>

</body>
</html>
