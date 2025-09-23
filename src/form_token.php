<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generiert ein neues Token und speichert es in der Session.
 * @return string Das erzeugte Token
 */
function generate_form_token() {
    $token = bin2hex(random_bytes(16));
    $_SESSION['form_token'] = $token;
    return $token;
}

/**
 * Prüft, ob das übergebene Token gültig ist.
 * @param string $token
 * @return bool
 */
function validate_form_token($token) {
    if (!isset($_SESSION['form_token'])) {
        return false;
    }
    $isValid = hash_equals($_SESSION['form_token'], $token);
    unset($_SESSION['form_token']); // Token sofort verbrauchen
    return $isValid;
}
