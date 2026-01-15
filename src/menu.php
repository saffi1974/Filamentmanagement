<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$site = $_GET['site'] ?? "start";
?>
<aside class="sidebar" id="sidebar">
    <div class="content">
        <div class="logo">
            <a href="index.php" id="logo">
                <img src="images/rolle2.png" alt="Logo" class="sidebar-logo">
            </a>
        </div>

        <nav class="nav">
            <ul>
                <li>
                    <a href="index.php"><i class="fa-solid fa-home"></i><span>Dashboard</span></a>
                </li>

                <!-- Aufträge & Rechnungen -->
                <li class="has-submenu">
                    <a href="#"><i class="fa-solid fa-list-check"></i><span>Aufträge & Rechnungen</span></a>
                    <ul class="submenu">
                        <li><a href="index.php?site=auftraege"><i class="fa-solid fa-list"></i>Auftragsliste</a></li>
                        <li><a href="index.php?site=auftraege_anlegen"><i class="fa-solid fa-plus"></i>Neuer Auftrag</a></li>
                        <li><a href="index.php?site=rechnungen"><i class="fa-solid fa-file-invoice-dollar"></i>Rechnungen</a></li>
                    </ul>
                </li>

                <!-- Projekte -->
                <li class="has-submenu">
                    <a href="#"><i class="fa-solid fa-sliders"></i><span>Vorlagen</span></a>
                    <ul class="submenu">
                        <li><a href="index.php?site=projekte"><i class="fa-solid fa-list"></i>Vorlagenliste</a></li>
                        <li><a href="index.php?site=projekte_anlegen"><i class="fa-solid fa-plus"></i>Vorlage erstellen</a></li>
						<li><a href="index.php?site=kosten_vorschlag"><i class="fa-solid fa-plus"></i>Kostenvorschlag erstellen</a></li>
                    </ul>
                </li>

                <!-- Lager -->
                <li class="has-submenu">
                    <a href="#"><i class="fa-solid fa-warehouse"></i><span>Lager</span></a>
                    <ul class="submenu">
                        <li><a href="index.php?site=spulen"><i class="fa-solid fa-record-vinyl"></i>Spulen</a></li>
						<li><a href="index.php?site=lager_suche"><i class="fa-solid fa-magnifying-glass"></i> Lager durchsuchen</a></li>
                        <li><a href="index.php?site=buchungen"><i class="fa-solid fa-plus"></i>Wareneingang</a></li>
                        <li><a href="index.php?site=lagerbewegungen"><i class="fa-solid fa-arrows-left-right"></i>Warenbewegungen</a></li>
                    </ul>
                </li>

                <!-- Materialverwaltung -->
                <li class="has-submenu">
                    <a href="#"><i class="fa-solid fa-boxes-stacked"></i><span>Materialstammdaten</span></a>
                    <ul class="submenu">
                        <li><a href="index.php?site=filamente"><i class="fa-solid fa-circle-nodes"></i>Filamente</a></li>
                        <li><a href="index.php?site=material"><i class="fa-solid fa-layer-group"></i>Materialarten</a></li>
                        <li><a href="index.php?site=hersteller"><i class="fa-solid fa-industry"></i>Hersteller</a></li>
                    </ul>
                </li>

                <!-- Stammdaten -->
                <li class="has-submenu">
                    <a href="#"><i class="fa-solid fa-gear"></i><span>Stammdaten</span></a>
                    <ul class="submenu">
                        <li><a href="index.php?site=kunden"><i class="fa-solid fa-handshake"></i>Kunden</a></li>
                        <li><a href="index.php?site=drucker"><i class="fa-solid fa-print"></i>Drucker</a></li>
						<li><a href="index.php?site=betriebskosten"><i class="fa-solid fa-money-bills"></i>Betriebskosten</a></li>
						<li><a href="index.php?site=firmendaten"><i class="fa-solid fa-building"></i>Firmendaten</a></li>
						<li><a href="index.php?site=user_registrieren"><i class="fa-solid fa-user-plus"></i>Benutzer hinzufügen</a></li>
						<li><a href="index.php?site=user_rechte"><i class="fa-solid fa-person-walking-dashed-line-arrow-right"></i>Benutzerrechte verwalten</a></li>
                    </ul>
                </li>
            </ul>
			<hr class="menu-separator">
	
            <?php if(isset($_SESSION['username'])): ?>
            <ul class="user-info">
                <li><a href="#"><i class="fa-solid fa-user"></i><span>Hallo <?= htmlspecialchars($_SESSION['username']) ?></span></a></li>
                <li><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Abmelden</span></a></li>
            </ul>
            <?php else: ?>
            <ul>
                <li><a href="index.php?site=anmelden"><i class="fa-solid fa-right-to-bracket"></i><span>Anmelden</span></a></li>
            </ul>
            <?php endif; ?>

            <?php if (isset($_SESSION['rolle']) && in_array($_SESSION['rolle'], ['superuser','admin', 'user'])): ?>
			
			<hr class="menu-separator">
			
            <ul>
                <li><a href="index.php?site=backup"><i class="fa-solid fa-database"></i><span>Backup erstellen</span></a></li>
            </ul>
            <?php endif; ?>

        </nav>
    </div>

    <button class="toggle-btn" id="toggleBtn">
        <i class="fas fa-bars"></i><span>Einklappen</span>
    </button>
</aside>
