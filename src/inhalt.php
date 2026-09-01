<?php if (($environment ?? 'production') === 'test'): ?>
    <div class="env-banner">⚠ TESTSYSTEM ⚠</div>
<?php endif; ?>

<?php
if(isset($_GET['site']) AND isset($dateien[$_GET['site']])) {
include $dateien[$_GET['site']];
} else {
include $dateien['start'];
}
?>