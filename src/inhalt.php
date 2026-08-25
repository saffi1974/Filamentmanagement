<div class="env-banner">⚠ TESTSYSTEM ⚠</div>

<?php
if(isset($_GET['site']) AND isset($dateien[$_GET['site']])) {
include $dateien[$_GET['site']];
} else {
include $dateien['start'];
}
?>

