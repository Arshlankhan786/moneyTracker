</main>
<nav class="mobile-nav" aria-label="Mobile navigation">
  <?php foreach ([['dashboard','Home','dashboard.php','bi-house'],['activity','Activity','activity.php','bi-receipt'],['insights','Insights','insights.php','bi-stars'],['categories','Categories','categories.php','bi-tags'],['settings','Settings','settings.php','bi-gear']] as $item): ?>
    <a class="mobile-nav-link <?= $activePage === $item[0] ? 'active' : '' ?>" href="<?= $item[2] ?>"><i class="bi <?= $item[3] ?>"></i><span><?= $item[1] ?></span></a>
  <?php endforeach; ?>
</nav>
<div id="toast-region" class="toast-region" aria-live="polite"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="assets/js/app.js"></script>
</body></html>