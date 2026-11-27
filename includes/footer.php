</main>

<footer class="site-footer">
    <div class="container footer-inner">
        <div>
            <div class="footer-brand">
                <span class="brand-emblem"><?php echo iconSvg("voice"); ?></span>
                <strong><?php echo e($site["app_name"]); ?></strong>
            </div>
            <p><?php echo e($site["university_name"]); ?></p>
            <p><?php echo e($site["motto"]); ?></p>
        </div>
        <div class="footer-links">
            <h2 class="footer-heading">Explore</h2>
            <a href="<?php echo BASE_URL; ?>/index.php">Home</a>
            <a href="<?php echo BASE_URL; ?>/news.php">Latest news</a>
            <?php if (!isset($_SESSION["user_id"])) { ?>
                <a href="<?php echo BASE_URL; ?>/auth/login.php">Log in</a>
                <a href="<?php echo BASE_URL; ?>/auth/register.php">Register</a>
            <?php } ?>
        </div>
        <?php if ($site["contact_email"] != "" || $site["contact_phone"] != "" || $site["contact_address"] != "") { ?>
            <div class="footer-contact">
                <h2 class="footer-heading">Contact</h2>
                <?php if ($site["contact_email"] != "") { ?>
                    <p><?php echo iconSvg("mail"); ?><a href="mailto:<?php echo e($site["contact_email"]); ?>"><?php echo e($site["contact_email"]); ?></a></p>
                <?php } ?>
                <?php if ($site["contact_phone"] != "") { ?>
                    <p><?php echo iconSvg("phone"); ?><a href="tel:<?php echo e($site["contact_phone"]); ?>"><?php echo e($site["contact_phone"]); ?></a></p>
                <?php } ?>
                <?php if ($site["contact_address"] != "") { ?>
                    <p><?php echo iconSvg("pin"); ?><?php echo e($site["contact_address"]); ?></p>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
</footer>

<script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
</body>
</html>
