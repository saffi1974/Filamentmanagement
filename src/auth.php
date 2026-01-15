<?php
if (session_status() === PHP_SESSION_NONE) {
	// 🔒 Session-Lebensdauer verlängern (z. B. 12 Stunden)
	ini_set('session.gc_maxlifetime', 43200);   // 12 Stunden in Sekunden
	ini_set('session.cookie_lifetime', 43200);  // Cookie-Gültigkeit 12 Stunden

    session_start();
}


/**
 * Prüft, ob der Benutzer eingeloggt ist.
 * Falls nicht → Weiterleitung auf die Anmeldeseite.
 */
function require_login() {
    if (!isset($_SESSION['username'])) {
        header("Location: index.php?site=anmelden");
        exit;
    }
}

/**
 * Prüft, ob der Benutzer eine bestimmte Rolle hat.
 *
 * @param array $roles - erlaubte Rollen, z. B. ['superuser','admin']
 */
function require_role(array $roles) {
    require_login();
    if (!isset($_SESSION['rolle']) || !in_array($_SESSION['rolle'], $roles)) {
        die('<div class="info-box">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>Sie haben keine ausreichenden Benutzerrechte dafür!</span>
				<button type="button" class="btn-primary" onclick="history.back()">← Zurück</button>
            </div>');
    }
}

/**
 * Prüft, ob der eingeloggte Benutzername in einer erlaubten Liste enthalten ist.
 *
 * @param array $users - erlaubte Benutzernamen, z. B. ['Mike','Anna']
 */
function require_user(array $users) {
    require_login();
    if (!isset($_SESSION['username']) || !in_array($_SESSION['username'], $users)) {
        die('<div class="info-box">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>Für diesen Benutzer ist der Zugriff untersagt!</span>
				<button type="button" class="btn-primary" onclick="history.back()">← Zurück</button>
            </div>');
    }
}
?>
