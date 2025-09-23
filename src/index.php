<?php
ob_start();              // Output-Buffering starten
session_start();
error_reporting(E_ALL);

include "config.php";
include "db.php";
include "header.php";
?>
<body>
<?php
include "menu.php";
?>



<main class="main-content">

<?php include "inhalt.php"; ?>

</main>

<script>
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('toggleBtn');

    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
    });
</script>
<script>
const toggles = document.querySelectorAll('.has-submenu > a');

toggles.forEach(toggle => {
    const parentItem = toggle.parentElement;

    toggle.addEventListener('click', (e) => {
        e.preventDefault();

        // Alle anderen Submenus schließen
        toggles.forEach(otherToggle => {
            if (otherToggle !== toggle) {
                otherToggle.parentElement.classList.remove('open');
            }
        });

        // Das angeklickte Submenu öffnen/schließen
        parentItem.classList.toggle('open');
    });
});
</script>

</body>
</html>

<?php
ob_end_flush(); // Alles gepufferte HTML wird ans Browser geschickt
?>
