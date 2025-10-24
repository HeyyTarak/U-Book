    </main>

    <footer class="footer">
        <div class="footer-container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
            <p>College Event Ticket Booking System</p>
        </div>
    </footer>

    <script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
    <?php if (isset($page_js)): ?>
    <script src="<?php echo BASE_URL; ?>/assets/js/<?php echo $page_js; ?>"></script>
    <?php endif; ?>
</body>
</html>
<?php ob_end_flush(); ?>