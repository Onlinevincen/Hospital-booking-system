</main>

<footer class="site-footer mt-5">
    <div class="container py-5">
        <div class="row gy-4">
            <div class="col-lg-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="brand-icon"><i class="bi bi-heart-pulse-fill"></i></span>
                    <span class="fw-bold text-white fs-5"><?= sanitize(APP_NAME) ?></span>
                </div>
                <p class="small mb-3" style="max-width:320px;">
                    Quality healthcare made simple — book appointments, track your medical
                    history, and manage prescriptions all in one secure place.
                </p>
                <div class="d-flex gap-2">
                    <a href="#" class="social-icon"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="social-icon"><i class="bi bi-twitter-x"></i></a>
                    <a href="#" class="social-icon"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="social-icon"><i class="bi bi-linkedin"></i></a>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <h6>Quick Links</h6>
                <ul class="list-unstyled d-flex flex-column gap-2">
                    <li><a href="<?= BASE_URL ?>/index.php">Home</a></li>
                    <li><a href="<?= BASE_URL ?>/register.php">Register</a></li>
                    <li><a href="<?= BASE_URL ?>/login.php">Login</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-3">
                <h6>Support</h6>
                <ul class="list-unstyled d-flex flex-column gap-2">
                    <li><a href="#">Help Center</a></li>
                    <li><a href="#">Contact Us</a></li>
                    <li><a href="#">FAQs</a></li>
                </ul>
            </div>
            <div class="col-lg-3">
                <h6>Contact</h6>
                <ul class="list-unstyled d-flex flex-column gap-2 small">
                    <li><i class="bi bi-geo-alt me-2"></i>123 Wellness Ave, Health City</li>
                    <li><i class="bi bi-telephone me-2"></i>+1 (555) 123-4567</li>
                    <li><i class="bi bi-envelope me-2"></i>support@hospitalsystem.com</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="footer-bottom py-3">
        <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
            <p class="small mb-0">&copy; <?= date('Y') ?> <?= sanitize(APP_NAME) ?>. All rights reserved.</p>
            <div class="d-flex gap-3 small">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS Bundle (navbar toggle, dropdowns, dismissible alerts) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
